# API Settings Endpoints Test Guide

## Quick Test Commands

You can test the new API endpoints using these curl commands or Postman.

### 1. Get User Profile (to get current user info)
```bash
curl -X GET http://localhost/api/v1/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### 2. Test Profile Update
```bash
curl -X PUT http://localhost/api/v1/user/update \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Updated",
    "email": "john.updated@example.com",
    "phone": "+233987654321",
    "country": "Ghana"
  }'
```

### 3. Test Password Update
```bash
curl -X PUT http://localhost/api/v1/user/update-password \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "currentPassword": "current_password",
    "newPassword": "new_password123",
    "confirmPassword": "new_password123"
  }'
```

## React Component Updates

### Update your handleProfileUpdate function:
```javascript
const handleProfileUpdate = async (event) => {
  event.preventDefault();
  setIsUpdating(true);
  
  try {
    const formData = new FormData(event.target);
    const profileData = {
      firstName: formData.get('firstName'),
      lastName: formData.get('lastName'), 
      email: formData.get('email'),
      phone: formData.get('phone'),
      country: formData.get('country'),
    };

    const response = await fetch('/api/v1/user/update', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(profileData)
    });

    const data = await response.json();
    
    if (data.success) {
      toast({
        title: "Profile Updated",
        description: "Your profile information has been updated successfully.",
      });
      // Update user context with new data
      // updateUser(data.user);
    } else {
      toast({
        title: "Update Failed", 
        description: data.message || "Failed to update profile",
        variant: "destructive",
      });
    }
  } catch (error) {
    toast({
      title: "Update Failed",
      description: "Failed to update profile. Please try again.",
      variant: "destructive",
    });
  } finally {
    setIsUpdating(false);
  }
};
```

### Update your handlePasswordUpdate function:
```javascript
const handlePasswordUpdate = async (event) => {
  event.preventDefault();
  setIsUpdatingPassword(true);
  
  try {
    const formData = new FormData(event.target);
    const passwordData = {
      currentPassword: formData.get('currentPassword'),
      newPassword: formData.get('newPassword'),
      confirmPassword: formData.get('confirmPassword'),
    };

    const response = await fetch('/api/v1/user/update-password', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(passwordData)
    });

    const data = await response.json();
    
    if (data.success) {
      toast({
        title: "Password Updated",
        description: "Your password has been updated successfully.",
      });
      // Clear the form
      event.target.reset();
    } else {
      toast({
        title: "Update Failed",
        description: data.message || "Failed to update password",
        variant: "destructive",
      });
    }
  } catch (error) {
    toast({
      title: "Update Failed", 
      description: "Failed to update password. Please try again.",
      variant: "destructive",
    });
  } finally {
    setIsUpdatingPassword(false);
  }
};
```

### Update your form elements to include name attributes:

```jsx
{/* Profile Form */}
<form onSubmit={handleProfileUpdate}>
  <Input 
    name="firstName"
    id="firstName" 
    defaultValue={nameParts.firstName}
    placeholder="Enter your first name"
    disabled={loading}
  />
  <Input 
    name="lastName"
    id="lastName" 
    defaultValue={nameParts.lastName}
    placeholder="Enter your last name"
    disabled={loading}
  />
  <Input 
    name="email"
    id="email" 
    type="email" 
    defaultValue={user?.email || ""}
    placeholder="Enter your email address"
    disabled={loading}
  />
  <Input 
    name="phone"
    id="phone" 
    type="tel" 
    defaultValue={user?.phone || ""}
    placeholder="Enter your phone number"
    disabled={loading}
  />
  <Input 
    name="country"
    id="country" 
    defaultValue={user?.country || ""}
    placeholder="Enter your country"
    disabled={loading}
  />
  <Button type="submit" disabled={loading || isUpdating}>
    {isUpdating ? "Updating..." : "Save Changes"}
  </Button>
</form>

{/* Password Form */}
<form onSubmit={handlePasswordUpdate}>
  <Input 
    name="currentPassword"
    id="currentPassword" 
    type="password" 
    placeholder="Enter your current password"
    disabled={loading}
    required
  />
  <Input 
    name="newPassword"
    id="newPassword" 
    type="password" 
    placeholder="Enter a new password"
    disabled={loading}
    required
  />
  <Input 
    name="confirmPassword"
    id="confirmPassword" 
    type="password" 
    placeholder="Confirm your new password"
    disabled={loading}
    required
  />
  <Button type="submit" disabled={loading || isUpdatingPassword}>
    {isUpdatingPassword ? "Updating..." : "Update Password"}
  </Button>
</form>
```

## Testing Checklist

- [ ] Profile update with valid data
- [ ] Profile update with invalid email (already taken)
- [ ] Profile update with empty firstName/lastName
- [ ] Password update with correct current password
- [ ] Password update with incorrect current password
- [ ] Password update with mismatched confirm password
- [ ] Password update with short password (< 6 chars)
- [ ] Verify tokens are revoked after password change
- [ ] Check that profile info is properly updated in database
