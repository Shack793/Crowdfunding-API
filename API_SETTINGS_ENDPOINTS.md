# Settings API Endpoints Documentation

This document outlines the API endpoints for the Settings page functionality.

## Endpoints Overview

### 1. Profile Information Update
- **URL**: `PUT /api/v1/user/update`
- **Authentication**: Required (Bearer Token)
- **Purpose**: Update user profile information

### 2. Password Update
- **URL**: `PUT /api/v1/user/update-password`
- **Authentication**: Required (Bearer Token)
- **Purpose**: Update user password with current password verification

---

## 1. Profile Information Update

### Endpoint
```
PUT /api/v1/user/update
```

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Body (All fields optional)
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john.doe@example.com",
  "phone": "+233123456789",
  "country": "Ghana"
}
```

### Alternative (using single name field)
```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+233123456789",
  "country": "Ghana"
}
```

### Response Success (200)
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+233123456789",
    "country": "Ghana",
    "role": "individual",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z"
  },
  "message": "Profile updated successfully"
}
```

### Response Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

## 2. Password Update

### Endpoint
```
PUT /api/v1/user/update-password
```

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

### Request Body (All fields required)
```json
{
  "currentPassword": "current_password",
  "newPassword": "new_password",
  "confirmPassword": "new_password"
}
```

### Response Success (200)
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

### Response Error - Validation (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "confirmPassword": ["The confirm password and new password must match."]
  }
}
```

### Response Error - Wrong Current Password (422)
```json
{
  "success": false,
  "message": "Current password is incorrect",
  "errors": {
    "currentPassword": ["The current password is incorrect"]
  }
}
```

---

## React Integration Example

### Profile Update Function
```javascript
const updateProfile = async (profileData) => {
  try {
    const response = await fetch('/api/v1/user/update', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
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
      // Update user context
      updateUserContext(data.user);
    } else {
      toast({
        title: "Update Failed",
        description: data.message,
        variant: "destructive",
      });
    }
  } catch (error) {
    toast({
      title: "Update Failed",
      description: "Failed to update profile. Please try again.",
      variant: "destructive",
    });
  }
};
```

### Password Update Function
```javascript
const updatePassword = async (passwordData) => {
  try {
    const response = await fetch('/api/v1/user/update-password', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
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
      // Clear form
      clearPasswordForm();
    } else {
      toast({
        title: "Update Failed",
        description: data.message,
        variant: "destructive",
      });
    }
  } catch (error) {
    toast({
      title: "Update Failed",
      description: "Failed to update password. Please try again.",
      variant: "destructive",
    });
  }
};
```

### Updated React Component Functions

Replace the placeholder functions in your React component with these:

```javascript
// Handle profile update
const handleProfileUpdate = async (event) => {
  event.preventDefault();
  setIsUpdating(true);
  
  const formData = new FormData(event.target);
  const profileData = {
    firstName: formData.get('firstName'),
    lastName: formData.get('lastName'),
    email: formData.get('email'),
    phone: formData.get('phone'),
    country: formData.get('country'),
  };

  await updateProfile(profileData);
  setIsUpdating(false);
};

// Handle password update
const handlePasswordUpdate = async (event) => {
  event.preventDefault();
  setIsUpdatingPassword(true);
  
  const formData = new FormData(event.target);
  const passwordData = {
    currentPassword: formData.get('currentPassword'),
    newPassword: formData.get('newPassword'),
    confirmPassword: formData.get('confirmPassword'),
  };

  await updatePassword(passwordData);
  setIsUpdatingPassword(false);
};
```

---

## Security Features

1. **Current Password Verification**: Password updates require current password verification
2. **Token Revocation**: After password change, all other sessions are logged out for security
3. **Input Validation**: All inputs are properly validated server-side
4. **Email Uniqueness**: Email updates check for uniqueness across all users
5. **Logging**: All profile and password updates are logged for audit purposes

---

## Notes

- The `firstName` and `lastName` fields are automatically combined into a single `name` field in the database
- Phone and country fields can be set to null/empty
- Email updates check for uniqueness excluding the current user
- Password must be at least 6 characters
- All endpoints return consistent JSON responses with `success` boolean and appropriate messages
