# Campaign Update API Test

This document shows how to test the updated campaign API endpoint.

## Endpoint
```
PUT /api/v1/campaigns/{slug}
```

## Test Payload (matches your React form)
```json
{
  "user_id": 1,
  "category_id": 1,
  "title": "Updated Campaign Title",
  "slug": "updated-campaign-title",
  "description": "This is an updated description for the campaign with more details.",
  "goal_amount": 35000,
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "visibility": "public",
  "thumbnail": "campaign-image.jpg",
  "status": "active"
}

# With image upload (multipart/form-data)
user_id: 1
category_id: 1
title: Updated Campaign Title
slug: updated-campaign-title
description: This is an updated description for the campaign
goal_amount: 35000
start_date: 2024-01-01
end_date: 2024-12-31
visibility: public
thumbnail: campaign-image.jpg
image: [FILE]
_method: PUT
```

## Expected Response
```json
{
  "success": true,
  "message": "Campaign updated successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "category_id": 1,
    "title": "Updated Campaign Title",
    "slug": "updated-campaign-title",
    "description": "This is an updated description for the campaign",
    "goal_amount": "35000.00",
    "current_amount": "0.00",
    "start_date": "2024-01-01T00:00:00.000000Z",
    "end_date": "2024-12-31T00:00:00.000000Z",
    "status": "active",
    "visibility": "public",
    "thumbnail": "campaign-image.jpg",
    "image_url": "/storage/campaigns/image123.jpg",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z",
    "category": {...},
    "user": {...}
  }
}
```

## Features Added:
1. ✅ Full field validation for all React form fields
2. ✅ Enhanced image upload (5MB max, multiple formats)
3. ✅ Proper error handling with detailed validation messages
4. ✅ Old image cleanup on new upload
5. ✅ Consistent JSON response format
6. ✅ Relationship loading for complete data
7. ✅ Support for Laravel method spoofing (_method: PUT)
