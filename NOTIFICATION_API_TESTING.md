# Notification System API Testing Guide

## Base URL
```
http://localhost/api
```

## Authentication
All notification endpoints require authentication. Include the bearer token in headers:
```json
{
  "Authorization": "Bearer YOUR_ACCESS_TOKEN",
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

---

## 1. GET /notifications - Get All Notifications

### Basic Request
```http
GET /api/notifications
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### With Query Parameters
```http
GET /api/notifications?unread_only=1&type=contribution_received&limit=10
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Expected Response
```json
{
  "success": true,
  "data": [
    {
      "id": "9d4a5c7e-8b2f-4d3e-9c1a-5b8e7f2d4c6a",
      "type": "App\\Notifications\\ContributionReceived",
      "notifiable_type": "App\\Models\\User",
      "notifiable_id": 1,
      "data": {
        "type": "contribution_received",
        "title": "New Contribution Received",
        "message": "John Doe contributed $50.00 to your campaign 'Help Build School'",
        "data": {
          "contribution_id": 123,
          "campaign_id": 45,
          "campaign_title": "Help Build School",
          "amount": 50.00,
          "donor_name": "John Doe",
          "created_at": "2025-07-28T10:30:00.000000Z"
        }
      },
      "read_at": null,
      "created_at": "2025-07-28T10:30:15.000000Z",
      "updated_at": "2025-07-28T10:30:15.000000Z"
    },
    {
      "id": "8c3b4d6e-7a1e-3c2d-8b0a-4a7d6e1c3b5a",
      "type": "App\\Notifications\\WithdrawalProcessed",
      "notifiable_type": "App\\Models\\User",
      "notifiable_id": 1,
      "data": {
        "type": "withdrawal_processed",
        "title": "Withdrawal Completed",
        "message": "Your withdrawal of $100.00 has been successfully processed",
        "data": {
          "amount": 100.00,
          "transaction_id": "TXN_20250728_001",
          "status": "completed",
          "processed_at": "2025-07-28T09:15:00.000000Z"
        }
      },
      "read_at": "2025-07-28T11:00:00.000000Z",
      "created_at": "2025-07-28T09:15:30.000000Z",
      "updated_at": "2025-07-28T11:00:00.000000Z"
    }
  ]
}
```

---

## 2. GET /notifications/unread-count - Get Unread Count

### Request
```http
GET /api/notifications/unread-count
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Expected Response
```json
{
  "success": true,
  "unread_count": 5
}
```

---

## 3. PUT /notifications/{id}/read - Mark Specific Notification as Read

### Request
```http
PUT /api/notifications/9d4a5c7e-8b2f-4d3e-9c1a-5b8e7f2d4c6a/read
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Expected Response
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

## 4. PUT /notifications/mark-all-read - Mark All Notifications as Read

### Request
```http
PUT /api/notifications/mark-all-read
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Expected Response
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

---

## 5. DELETE /notifications/{id} - Delete Specific Notification

### Request
```http
DELETE /api/notifications/9d4a5c7e-8b2f-4d3e-9c1a-5b8e7f2d4c6a
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Expected Response
```json
{
  "success": true,
  "message": "Notification deleted successfully"
}
```

---

## 6. DELETE /notifications/read - Delete All Read Notifications

### Request
```http
DELETE /api/notifications/read
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Expected Response
```json
{
  "success": true,
  "message": "3 notifications deleted successfully"
}
```

---

## Testing Scenarios

### Scenario 1: Testing Contribution Notifications
1. Make a contribution using guest donation endpoint:
```http
POST /api/campaigns/{campaign_slug}/donate-guest
Content-Type: application/json

{
  "payment_method_id": 1,
  "amount": 25.50,
  "name": "Jane Smith",
  "email": "jane@example.com"
}
```

2. Check campaign owner's notifications:
```http
GET /api/notifications?type=contribution_received
Authorization: Bearer CAMPAIGN_OWNER_TOKEN
```

### Scenario 2: Testing Withdrawal Notifications
1. Process a withdrawal:
```http
POST /api/wallet/update-after-withdrawal
Authorization: Bearer USER_TOKEN
Content-Type: application/json

{
  "amount": "75.00",
  "transaction_id": "TXN_TEST_001",
  "status": "success"
}
```

2. Check user's notifications:
```http
GET /api/notifications?type=withdrawal_processed
Authorization: Bearer USER_TOKEN
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [Illuminate\\Notifications\\DatabaseNotification] 9d4a5c7e-8b2f-4d3e-9c1a-5b8e7f2d4c6a"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "limit": ["The limit must be a number."]
  }
}
```

---

## Testing with cURL Commands

### Get All Notifications
```bash
curl -X GET "http://localhost/api/notifications" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Get Unread Count
```bash
curl -X GET "http://localhost/api/notifications/unread-count" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Mark Notification as Read
```bash
curl -X PUT "http://localhost/api/notifications/NOTIFICATION_ID/read" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Mark All as Read
```bash
curl -X PUT "http://localhost/api/notifications/mark-all-read" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Delete Notification
```bash
curl -X DELETE "http://localhost/api/notifications/NOTIFICATION_ID" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Delete All Read Notifications
```bash
curl -X DELETE "http://localhost/api/notifications/read" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

---

## Postman Collection Import

```json
{
  "info": {
    "name": "Crowdfunding Notifications API",
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
      "name": "Get All Notifications",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/notifications",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications"]
        }
      }
    },
    {
      "name": "Get Unread Count",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/notifications/unread-count",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications", "unread-count"]
        }
      }
    },
    {
      "name": "Mark Notification as Read",
      "request": {
        "method": "PUT",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/notifications/{{notification_id}}/read",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications", "{{notification_id}}", "read"]
        }
      }
    },
    {
      "name": "Mark All as Read",
      "request": {
        "method": "PUT",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/notifications/mark-all-read",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications", "mark-all-read"]
        }
      }
    },
    {
      "name": "Delete Notification",
      "request": {
        "method": "DELETE",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/notifications/{{notification_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications", "{{notification_id}}"]
        }
      }
    },
    {
      "name": "Delete All Read",
      "request": {
        "method": "DELETE",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/notifications/read",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications", "read"]
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
    },
    {
      "key": "notification_id",
      "value": "NOTIFICATION_UUID_HERE"
    }
  ]
}
```

---

## Notes for Testing

1. **Authentication Required**: All endpoints require a valid bearer token
2. **UUID Format**: Notification IDs are UUIDs, not integers
3. **Notification Types**: Look for `contribution_received` and `withdrawal_processed` types
4. **Event Triggers**: Notifications are automatically created when contributions or withdrawals occur
5. **Email Testing**: Check your mail configuration to test email notifications
6. **Database**: Notifications are stored in Laravel's `notifications` table

Replace `YOUR_ACCESS_TOKEN` with actual authentication tokens from your user login endpoints.
