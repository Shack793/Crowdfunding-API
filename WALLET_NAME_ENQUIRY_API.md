# Wallet Name Enquiry API

## Endpoint
```
POST /api/v1/wallet/name-enquiry
```

## Authentication
Requires authentication (Sanctum token)

## Description
This endpoint serves as a proxy to the UniWallet API to fetch the wallet holder's name based on their mobile number and network. This is needed because the UniWallet API doesn't accept direct calls from browsers due to CORS restrictions.

## Request Payload
```json
{
    "msisdn": "0544174142",
    "network": "MTN"
}
```

### Parameters
- `msisdn` (required, string): Mobile number in any format (with or without country code)
- `network` (required, string): Mobile network provider. Must be one of: `MTN`, `VODAFONE`, `AIRTELTIGO`

## Response

### Success Response (200)
```json
{
    "success": true,
    "data": {
        "name": "SOLOMON AIDOO JNR",
        "msisdn": "233544174142",
        "network": "MTN",
        "responseMessage": "Operation Successful"
    }
}
```

### Error Response (400)
```json
{
    "success": false,
    "message": "Name enquiry failed",
    "responseCode": "02"
}
```

### Validation Error (422)
```json
{
    "success": false,
    "message": "Invalid input data",
    "errors": {
        "msisdn": ["The msisdn field is required."],
        "network": ["The selected network is invalid."]
    }
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "An error occurred while fetching wallet holder name",
    "error": "Connection timeout"
}
```

## Frontend Integration

### JavaScript Example
```javascript
const getWalletHolderName = async (msisdn, network) => {
    try {
        const response = await fetch('/api/v1/wallet/name-enquiry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${userToken}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                msisdn: msisdn,
                network: network
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // Populate the full name field with the returned name
            document.getElementById('fullNameField').value = data.data.name;
            console.log('Wallet holder name:', data.data.name);
        } else {
            console.error('Name enquiry failed:', data.message);
            alert('Could not fetch wallet holder name: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while fetching wallet holder name');
    }
};

// Usage example
getWalletHolderName('0544174142', 'MTN');
```

### React Example
```javascript
const [fullName, setFullName] = useState('');
const [loading, setLoading] = useState(false);

const fetchWalletHolderName = async (msisdn, network) => {
    setLoading(true);
    try {
        const response = await apiClient.post('/wallet/name-enquiry', {
            msisdn,
            network
        });

        if (response.data.success) {
            setFullName(response.data.data.name);
            toast.success('Wallet holder name retrieved successfully');
        } else {
            toast.error(response.data.message);
        }
    } catch (error) {
        toast.error('Failed to fetch wallet holder name');
        console.error('Name enquiry error:', error);
    } finally {
        setLoading(false);
    }
};
```

## Phone Number Format Handling
The API automatically handles different phone number formats:
- `0544174142` → `233544174142`
- `233544174142` → `233544174142`
- `+233544174142` → `233544174142`
- `544174142` → `233544174142`

## Network Mapping
- **MTN**: 024, 025, 053, 054, 055, 059
- **VODAFONE**: 020, 050
- **AIRTELTIGO**: 027, 057, 026

## Notes
- The endpoint includes comprehensive logging for debugging
- Timeout is set to 30 seconds for the external API call
- All requests and responses are logged for troubleshooting
- The static values (productId, merchantId, apiKey) are hardcoded as provided
