# Postman API Testing Guide

## 📋 Base URL
```
http://localhost/Crowdfunding1/crowddonation/public/api/v1
```

## 🔐 Authentication Setup

### 1. Login to Get Bearer Token
**Method:** `POST`  
**Endpoint:** `/login`  
**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "email": "your-email@example.com",
    "password": "your-password"
}
```

**Response:** Copy the `token` value from the response.

### 2. Set Authorization Header
For all authenticated requests, add this header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📧 Email Verification Endpoints (NEW!)

### 1. Send Verification Code
**Method:** `POST`  
**Endpoint:** `/withdrawal/send-verification-code`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Verification code sent successfully",
    "masked_email": "us**@example.com",
    "verification_token": "unique-token-string",
    "expires_in_minutes": 15
}
```

### 2. Verify Code
**Method:** `POST`  
**Endpoint:** `/withdrawal/verify-code`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "code": "123456"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Code verified successfully",
    "verification_token": "token-for-withdrawal",
    "verified": true
}
```

### 3. Resend Verification Code
**Method:** `POST`  
**Endpoint:** `/withdrawal/resend-verification-code`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "New verification code sent",
    "masked_email": "us**@example.com",
    "verification_token": "new-token-string",
    "expires_in_minutes": 15
}
```

### 4. Check Verification Status
**Method:** `GET`  
**Endpoint:** `/withdrawal/verification-status?email=user@example.com`  
**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Expected Response:**
```json
{
    "has_active_code": true,
    "expires_at": "2025-08-27T15:30:00Z",
    "can_resend": false,
    "next_resend_at": "2025-08-27T14:32:00Z"
}
```

---

## 🔑 Authentication Endpoints

### 1. Register
**Method:** `POST`  
**Endpoint:** `/register`  
**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### 2. Login
**Method:** `POST`  
**Endpoint:** `/login`  
**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

### 3. Update Profile
**Method:** `PUT`  
**Endpoint:** `/user/update`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "name": "Updated Name",
    "email": "newemail@example.com"
}
```

### 4. Update Password
**Method:** `PUT`  
**Endpoint:** `/user/update-password`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "current_password": "oldpassword",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

---

## 🎯 Campaign Endpoints

### 1. Create Campaign
**Method:** `POST`  
**Endpoint:** `/campaigns`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "title": "My Campaign",
    "description": "Campaign description",
    "goal_amount": 1000.00,
    "category_id": 1,
    "end_date": "2025-12-31"
}
```

### 2. Update Campaign
**Method:** `PUT`  
**Endpoint:** `/campaigns/{slug}`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "title": "Updated Campaign Title",
    "description": "Updated description",
    "goal_amount": 2000.00
}
```

### 3. Approve Campaign
**Method:** `POST`  
**Endpoint:** `/campaigns/{slug}/approve`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "admin_note": "Campaign approved"
}
```

### 4. Reject Campaign
**Method:** `POST`  
**Endpoint:** `/campaigns/{slug}/reject`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "rejection_reason": "Does not meet requirements"
}
```

---

## 💰 Contribution & Donation Endpoints

### 1. Guest Donation
**Method:** `POST`  
**Endpoint:** `/campaigns/{campaignSlug}/donate/guest`  
**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "amount": 50.00,
    "donor_name": "Anonymous Donor",
    "donor_email": "donor@example.com",
    "payment_method_id": 1
}
```

### 2. Authenticated Donation
**Method:** `POST`  
**Endpoint:** `/campaigns/{slug}/donate`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "amount": 100.00,
    "payment_method_id": 1,
    "is_anonymous": false
}
```

### 3. Create Contribution
**Method:** `POST`  
**Endpoint:** `/campaigns/{slug}/contributions`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "amount": 75.00,
    "payment_method_id": 1,
    "reward_id": 1
}
```

---

## 💳 Payment Endpoints

### 1. Credit Wallet
**Method:** `POST`  
**Endpoint:** `/payments/credit-wallet`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "amount": 500.00,
    "payment_method": "card"
}
```

### 2. Debit Wallet
**Method:** `POST`  
**Endpoint:** `/payments/debit-wallet`  
**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "amount": 100.00,
    "recipient_wallet": "wallet-address",
    "description": "Payment description"
}
```

---

## 🏆 Reward Endpoints

### 1. Create Reward
**Method:** `POST`  
**Endpoint:** `/dashboard/rewards`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "campaign_id": 1,
    "title": "Early Bird Reward",
    "description": "Special reward for early donors",
    "amount": 50.00,
    "quantity": 10
}
```

### 2. Update Reward
**Method:** `PUT`  
**Endpoint:** `/dashboard/rewards/{id}`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "title": "Updated Reward Title",
    "description": "Updated description",
    "amount": 75.00,
    "quantity": 15
}
```

---

## 💬 Comment & Notification Endpoints

### 1. Create Comment
**Method:** `POST`  
**Endpoint:** `/comments`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "campaign_id": 1,
    "content": "Great campaign!",
    "parent_id": null
}
```

### 2. Subscribe to Campaign
**Method:** `POST`  
**Endpoint:** `/subscribe`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "campaign_id": 1,
    "email": "subscriber@example.com"
}
```

### 3. Mark Notification as Read
**Method:** `PUT`  
**Endpoint:** `/notifications/{id}/read`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "read": true
}
```

---

## 🚀 Boost Endpoints

### 1. Boost Campaign
**Method:** `POST`  
**Endpoint:** `/campaigns/{campaign}/boost`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "boost_plan_id": 1,
    "duration_days": 7
}
```

### 2. Boost Campaign (Alternative)
**Method:** `POST`  
**Endpoint:** `/boost-campaign/{campaignId}`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "boost_plan_id": 1,
    "duration": 7
}
```

---

## 🏦 Wallet Endpoints

### 1. Update Wallet After Withdrawal
**Method:** `POST`  
**Endpoint:** `/wallet/update-after-withdrawal`  
**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Request Body:**
```json
{
    "amount": 100.00,
    "transaction_ref": "TXN123456",
    "description": "Withdrawal processed"
}
```

---

## 🛠️ Testing Tips

### 1. Environment Variables
Create a Postman environment with:
- `base_url`: `http://localhost/Crowdfunding1/crowddonation/public/api/v1`
- `token`: (will be set after login)

### 2. Common Headers
Create a header preset:
```
Content-Type: application/json
Authorization: Bearer {{token}}
```

### 3. Test Scripts
Add this to login request to automatically set token:
```javascript
if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.environment.set("token", response.token);
}
```

### 4. Error Handling
- **401 Unauthorized**: Check if token is valid
- **422 Validation Error**: Check required fields
- **500 Server Error**: Check Laravel logs

### 5. Rate Limiting
Some endpoints have rate limiting. If you get 429 errors, wait before retrying.

---

## 📋 Quick Test Flow

1. **Register/Login** → Get token
2. **Send Verification Code** → Check email for code
3. **Verify Code** → Get verification token
4. **Create Campaign** → Test campaign management
5. **Make Donation** → Test contribution flow
6. **Create Withdrawal** → Test withdrawal (with verification)

This covers all major JSON endpoints in your API!
