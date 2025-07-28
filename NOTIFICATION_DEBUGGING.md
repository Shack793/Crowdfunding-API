# Notification API Debugging Guide

## Current Issue: 404 Error on GET /api/notifications

### Step 1: Test Basic Route Access
```bash
# Test the new debug endpoint first
GET /api/v1/notifications/test
Authorization: Bearer YOUR_TOKEN
```

Expected Response:
```json
{
  "success": true,
  "message": "Notification controller is working",
  "timestamp": "2025-07-28T...",
  "user_authenticated": true,
  "user_id": 123
}
```

### Step 2: Test Full URL Path
The routes are under `/api/v1/` prefix, so the full URL should be:
```
GET http://localhost/api/v1/notifications
```

NOT:
```
GET http://localhost/api/notifications  ❌
```

### Step 3: Check Authentication
Ensure you're including the Bearer token:
```bash
curl -X GET "http://localhost/api/v1/notifications" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

### Step 4: Check Laravel Logs
Check the logs to see detailed debugging information:
```bash
# Windows PowerShell
Get-Content "c:\laragon\www\Crowdfunding1\crowddonation\storage\logs\laravel.log" -Tail 50

# Or view the file directly
# storage/logs/laravel.log
```

### Step 5: Verify Route Registration
Run this command to confirm routes are registered:
```bash
php artisan route:list --path=notifications
```

Expected Output:
```
GET|HEAD  api/v1/notifications/test ..................................... NotificationController@test
GET|HEAD  api/v1/notifications ......................................... NotificationController@index
GET|HEAD  api/v1/notifications/unread-count ............................ NotificationController@getUnreadCount
PUT       api/v1/notifications/{id}/read .............................. NotificationController@markAsRead
PUT       api/v1/notifications/mark-all-read .......................... NotificationController@markAllAsRead
DELETE    api/v1/notifications/{id} ................................... NotificationController@destroy
DELETE    api/v1/notifications/read ................................... NotificationController@deleteRead
```

### Step 6: Test with Postman Collection

Import this collection for testing:

```json
{
  "info": {
    "name": "Notification Debugging",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{access_token}}",
        "type": "string"
      }
    ]
  },
  "item": [
    {
      "name": "Test Notification Controller",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/v1/notifications/test",
          "host": ["{{base_url}}"],
          "path": ["api", "v1", "notifications", "test"]
        }
      }
    },
    {
      "name": "Get All Notifications",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/v1/notifications",
          "host": ["{{base_url}}"],
          "path": ["api", "v1", "notifications"]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost"
    },
    {
      "key": "access_token",
      "value": "YOUR_ACCESS_TOKEN_HERE"
    }
  ]
}
```

### Step 7: Common Issues and Solutions

#### Issue 1: Wrong URL Path
- ❌ `/api/notifications`
- ✅ `/api/v1/notifications`

#### Issue 2: Missing Authentication
- Ensure Bearer token is included in Authorization header
- Check if token is valid and not expired

#### Issue 3: Route Cache Issues
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

#### Issue 4: Laravel Application Not Running
- Check if Laragon is running
- Verify Apache/Nginx is serving the application
- Test basic Laravel route: `GET /api/v1/categories`

### Step 8: Debug Output Analysis

When you call the API, check the Laravel logs for these entries:

#### Successful Request Log:
```
[timestamp] local.INFO: NotificationController@index called {"user_id":123,"user_authenticated":true,...}
[timestamp] local.INFO: User details in notifications {"user_id":123,"user_email":"user@example.com",...}
[timestamp] local.INFO: Notifications retrieved successfully {"total_count":5,"user_id":123}
```

#### Failed Request Log:
```
[timestamp] local.ERROR: User not authenticated in notifications index {"headers":{...},"bearer_token_present":"no"}
```

#### 404 Error Indicators:
- No logs appearing at all = Route not found or Laravel not processing the request
- Logs appearing but with errors = Controller is reached but has issues

### Step 9: Test Sequence

1. First test: `GET /api/v1/notifications/test`
2. If test works: `GET /api/v1/notifications`
3. If both fail: Check URL, authentication, and Laravel setup
4. If test works but notifications fails: Check user authentication and database

### Step 10: Manual Database Check

If needed, manually check if notifications exist:
```sql
SELECT * FROM notifications WHERE notifiable_id = YOUR_USER_ID LIMIT 5;
```

This debugging guide should help identify exactly where the 404 error is coming from!
