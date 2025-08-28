# Email Verification Flow for Withdrawals - Implementation Analysis

## 📋 CURRENT CODEBASE ANALYSIS

### ✅ EXISTING FILES (Backend)

#### **Controllers:**
- `app/Http/Controllers/WithdrawalController.php` - Handles withdrawal creation
- `app/Http/Controllers/AuthController.php` - Handles authentication

#### **Models:**
- `app/Models/User.php` - User model with email field
- `app/Models/Withdrawal.php` - Withdrawal model
- `app/Models/Wallet.php` - Wallet model

#### **Notifications:**
- `app/Notifications/WithdrawalProcessed.php` - Existing withdrawal notification

#### **Routes:**
- `routes/api.php` - Contains `/withdrawals` POST endpoint

#### **Configuration:**
- `config/mail.php` - Mail configuration setup

---

## 🚀 NEW FILES TO BE CREATED

### **Backend Files:**

#### **1. EmailVerificationController.php**
```php
// Location: app/Http/Controllers/EmailVerificationController.php
// Purpose: Handle email verification for withdrawals
```

#### **2. WithdrawalVerificationCode.php (Model)**
```php
// Location: app/Models/WithdrawalVerificationCode.php
// Purpose: Store verification codes with expiration
```

#### **3. WithdrawalEmailVerification.php (Notification)**
```php
// Location: app/Notifications/WithdrawalEmailVerification.php
// Purpose: Send verification code via email
```

#### **4. Migration for verification_codes table**
```php
// Location: database/migrations/2024_01_27_000000_create_withdrawal_verification_codes_table.php
// Purpose: Database table for storing verification codes
```

### **Frontend Files (Assuming Vue.js/React):**

#### **5. EmailVerificationModal.vue (Component)**
```javascript
// Location: resources/js/components/EmailVerificationModal.vue
// Purpose: Modal for email verification form
```

#### **6. WithdrawalModal.vue (Component)**
```javascript
// Location: resources/js/components/WithdrawalModal.vue
// Purpose: Main withdrawal form modal
```

---

## 🔄 USER FLOW PSEUDOCODE

### **Backend Pseudocode:**

#### **EmailVerificationController.php**
```php
<?php
class EmailVerificationController extends Controller
{
    // 1. Send verification code
    public function sendVerificationCode(Request $request)
    {
        $user = $request->user();
        
        // Generate 6-digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code with expiration (15 minutes)
        WithdrawalVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
            'used' => false
        ]);
        
        // Send email notification
        $user->notify(new WithdrawalEmailVerification($code));
        
        return response()->json([
            'success' => true,
            'message' => 'Verification code sent',
            'email' => $this->maskEmail($user->email)
        ]);
    }
    
    // 2. Verify code
    public function verifyCode(Request $request)
    {
        $user = $request->user();
        $code = $request->input('code');
        
        $verification = WithdrawalVerificationCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code'
            ], 400);
        }
        
        // Mark code as used
        $verification->update(['used' => true]);
        
        // Generate temporary token for withdrawal
        $token = Str::random(64);
        
        // Store token in cache/session for 5 minutes
        Cache::put("withdrawal_verification_{$user->id}", $token, 300);
        
        return response()->json([
            'success' => true,
            'message' => 'Code verified successfully',
            'verification_token' => $token
        ]);
    }
    
    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        
        return $maskedName . '@' . $domain;
    }
}
```

#### **WithdrawalController.php (Modified)**
```php
<?php
class WithdrawalController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check for verification token
        $verificationToken = $request->input('verification_token');
        $cachedToken = Cache::get("withdrawal_verification_{$user->id}");
        
        if (!$verificationToken || $verificationToken !== $cachedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification required'
            ], 403);
        }
        
        // Clear the verification token
        Cache::forget("withdrawal_verification_{$user->id}");
        
        // Proceed with withdrawal creation...
        // ...existing withdrawal logic...
    }
}
```

#### **WithdrawalEmailVerification.php**
```php
<?php
class WithdrawalEmailVerification extends Notification
{
    public $verificationCode;
    
    public function __construct($code)
    {
        $this->verificationCode = $code;
    }
    
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Withdrawal Verification Code')
            ->greeting('Hello ' . $notifiable->name)
            ->line('You requested to withdraw funds from your account.')
            ->line('Your verification code is: **' . $this->verificationCode . '**')
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request this withdrawal, please ignore this email.')
            ->salutation('Best regards, ' . config('app.name'));
    }
}
```

### **Frontend Pseudocode (Vue.js Example):**

#### **EmailVerificationModal.vue**
```javascript
<template>
  <div class="modal">
    <div class="modal-content">
      <h3>Email Verification Required</h3>
      <p>We've sent a verification code to: <strong>{{ maskedEmail }}</strong></p>
      
      <form @submit.prevent="verifyCode">
        <div class="code-inputs">
          <input v-for="(digit, index) in code" 
                 :key="index"
                 v-model="code[index]"
                 type="text"
                 maxlength="1"
                 @input="handleInput(index, $event)"
                 @keydown="handleKeydown(index, $event)" />
        </div>
        
        <div class="countdown" v-if="countdown > 0">
          Resend code in: {{ countdown }}s
        </div>
        
        <button type="button" 
                @click="resendCode" 
                :disabled="countdown > 0"
                class="resend-btn">
          Resend Code
        </button>
        
        <button type="submit" :disabled="code.join('').length !== 6">
          Verify Code
        </button>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      code: ['', '', '', '', '', ''],
      countdown: 15,
      countdownInterval: null,
      maskedEmail: ''
    }
  },
  
  mounted() {
    this.startCountdown()
    this.sendVerificationCode()
  },
  
  methods: {
    async sendVerificationCode() {
      try {
        const response = await axios.post('/api/v1/withdrawal/send-verification-code')
        this.maskedEmail = response.data.email
      } catch (error) {
        console.error('Failed to send verification code:', error)
      }
    },
    
    async verifyCode() {
      const verificationCode = this.code.join('')
      
      try {
        const response = await axios.post('/api/v1/withdrawal/verify-code', {
          code: verificationCode
        })
        
        // Emit success event to parent component
        this.$emit('verification-success', response.data.verification_token)
      } catch (error) {
        console.error('Verification failed:', error)
      }
    },
    
    async resendCode() {
      await this.sendVerificationCode()
      this.startCountdown()
    },
    
    startCountdown() {
      this.countdown = 15
      this.countdownInterval = setInterval(() => {
        this.countdown--
        if (this.countdown <= 0) {
          clearInterval(this.countdownInterval)
        }
      }, 1000)
    },
    
    handleInput(index, event) {
      const value = event.target.value
      if (value && index < 5) {
        this.$nextTick(() => {
          this.$refs[`input${index + 1}`]?.focus()
        })
      }
    },
    
    handleKeydown(index, event) {
      if (event.key === 'Backspace' && !this.code[index] && index > 0) {
        this.$refs[`input${index - 1}`]?.focus()
      }
    }
  }
}
</script>
```

#### **WithdrawalModal.vue**
```javascript
<template>
  <div class="modal">
    <div class="modal-content">
      <h3>Request Withdrawal</h3>
      
      <!-- Withdrawal Form -->
      <form @submit.prevent="requestWithdrawal">
        <input v-model="amount" type="number" placeholder="Amount" required />
        <button type="submit">Request Withdrawal</button>
      </form>
    </div>
  </div>
</template>

<script>
import EmailVerificationModal from './EmailVerificationModal.vue'

export default {
  components: {
    EmailVerificationModal
  },
  
  data() {
    return {
      amount: '',
      showEmailVerification: false
    }
  },
  
  methods: {
    requestWithdrawal() {
      // Show email verification modal first
      this.showEmailVerification = true
    },
    
    onVerificationSuccess(verificationToken) {
      // Now proceed with actual withdrawal
      this.processWithdrawal(verificationToken)
    },
    
    async processWithdrawal(verificationToken) {
      try {
        const response = await axios.post('/api/v1/withdrawals', {
          amount: this.amount,
          verification_token: verificationToken
        })
        
        console.log('Withdrawal successful:', response.data)
        this.showEmailVerification = false
        // Close modal and show success message
      } catch (error) {
        console.error('Withdrawal failed:', error)
      }
    }
  }
}
</script>
```

---

## 🛣️ COMPLETE USER FLOW

### **Step 1: User clicks "Request Withdrawal"**
```javascript
// Frontend: WithdrawalModal.vue
requestWithdrawal() {
  this.showEmailVerification = true
}
```

### **Step 2: Email Verification Modal Opens**
```javascript
// Frontend: EmailVerificationModal.vue
mounted() {
  this.sendVerificationCode()  // Automatically sends code
  this.startCountdown()        // Starts 15-second countdown
}
```

### **Step 3: Backend sends verification code**
```php
// Backend: EmailVerificationController.php
public function sendVerificationCode() {
  $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
  
  // Store in database
  WithdrawalVerificationCode::create([...]);
  
  // Send email
  $user->notify(new WithdrawalEmailVerification($code));
  
  return masked_email;
}
```

### **Step 4: User enters 6-digit code**
```javascript
// Frontend: EmailVerificationModal.vue
verifyCode() {
  axios.post('/api/v1/withdrawal/verify-code', {
    code: this.code.join('')
  }).then(response => {
    this.$emit('verification-success', response.data.verification_token)
  })
}
```

### **Step 5: Backend verifies code**
```php
// Backend: EmailVerificationController.php
public function verifyCode() {
  $verification = WithdrawalVerificationCode::where('code', $code)
    ->where('used', false)
    ->where('expires_at', '>', now())
    ->first();
    
  if ($verification) {
    $verification->update(['used' => true]);
    $token = Str::random(64);
    Cache::put("withdrawal_verification_{$user->id}", $token, 300);
    return $token;
  }
}
```

### **Step 6: Proceed to actual withdrawal**
```php
// Backend: WithdrawalController.php (modified)
public function store() {
  $verificationToken = $request->input('verification_token');
  $cachedToken = Cache::get("withdrawal_verification_{$user->id}");
  
  if ($verificationToken !== $cachedToken) {
    return 'Email verification required';
  }
  
  // Proceed with withdrawal...
}
```

---

## 📁 FILE CREATION SUMMARY

### **Files to Create:**
1. ✅ `app/Http/Controllers/EmailVerificationController.php`
2. ✅ `app/Models/WithdrawalVerificationCode.php`
3. ✅ `app/Notifications/WithdrawalEmailVerification.php`
4. ✅ `database/migrations/2024_01_27_000000_create_withdrawal_verification_codes_table.php`
5. ✅ `resources/js/components/EmailVerificationModal.vue`
6. ✅ `resources/js/components/WithdrawalModal.vue`

### **Files to Modify:**
1. ✅ `routes/api.php` - Add email verification routes
2. ✅ `app/Http/Controllers/WithdrawalController.php` - Add verification check

This implementation provides a secure, user-friendly email verification flow before allowing withdrawals, preventing unauthorized access while maintaining good UX with the countdown and resend functionality.
