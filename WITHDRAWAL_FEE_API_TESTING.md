# 💰 Withdrawal Fee API Testing Guide

## 🎓 Junior Developer Learning: Complete API Integration Guide

This guide provides everything you need to integrate the withdrawal fee system into your frontend. Each endpoint includes detailed examples and explanations.

---

## 📋 Base Information

**Base URL:** `http://your-domain.com/api/v1`
**Authentication:** Bearer Token (required for all endpoints)
**Content-Type:** `application/json`

### Authentication Header
```
Authorization: Bearer YOUR_SANCTUM_TOKEN_HERE
```

---

## 🔍 1. Calculate Fee (Preview) - GET `/withdrawal-fees/calculate`

**Purpose:** Calculate fees BEFORE user confirms withdrawal (for preview)

### Request Parameters (Query String)
```
amount=100.50&method=mobile_money&network=MTN
```

### Frontend Integration Example (JavaScript)
```javascript
// Calculate fee for withdrawal preview
async function calculateWithdrawalFee(amount, method, network = null) {
    try {
        const params = new URLSearchParams({
            amount: amount,
            method: method
        });
        
        if (network && method === 'mobile_money') {
            params.append('network', network);
        }

        const response = await fetch(`/api/v1/withdrawal-fees/calculate?${params}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            // Show fee breakdown to user
            displayFeeBreakdown(data.data);
        } else {
            showError(data.message);
        }
    } catch (error) {
        console.error('Fee calculation failed:', error);
    }
}

function displayFeeBreakdown(feeData) {
    document.getElementById('gross-amount').textContent = `GHS ${feeData.gross_amount}`;
    document.getElementById('fee-amount').textContent = `GHS ${feeData.fee_amount}`;
    document.getElementById('net-amount').textContent = `GHS ${feeData.net_amount}`;
    document.getElementById('fee-percentage').textContent = `${feeData.fee_percentage}%`;
}
```

### Successful Response Example
```json
{
    "success": true,
    "message": "Fee calculated successfully",
    "data": {
        "gross_amount": 100.50,
        "fee_amount": 2.51,
        "net_amount": 97.99,
        "fee_percentage": 2.5,
        "currency": "GHS",
        "breakdown": {
            "description": "Mobile Money withdrawal fee: 2.5% of amount",
            "method": "mobile_money",
            "network": "MTN"
        }
    }
}
```

### Error Response Example
```json
{
    "success": false,
    "message": "Invalid input data",
    "errors": {
        "amount": ["The amount must be at least 1."],
        "method": ["The selected method is invalid."]
    }
}
```

---

## 💾 2. Record Fee (Store) - POST `/withdrawal-fees/record`

**Purpose:** Store fee information when withdrawal is actually processed

### Request Body (JSON)
```json
{
    "withdrawal_id": 123,
    "amount": 100.50,
    "method": "mobile_money",
    "network": "MTN",
    "metadata": {
        "transaction_ref": "TXN_123456789",
        "notes": "Withdrawal to MTN Mobile Money"
    }
}
```

### Frontend Integration Example (JavaScript)
```javascript
// Record fee when withdrawal is processed
async function recordWithdrawalFee(withdrawalData) {
    try {
        const response = await fetch('/api/v1/withdrawal-fees/record', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                withdrawal_id: withdrawalData.id,
                amount: withdrawalData.amount,
                method: withdrawalData.method,
                network: withdrawalData.network,
                metadata: {
                    transaction_ref: withdrawalData.reference,
                    processed_by: 'user_withdrawal_system'
                }
            })
        });

        const data = await response.json();
        
        if (data.success) {
            console.log('Fee recorded successfully:', data.data);
            return data.data.fee_id; // Use this for tracking
        } else {
            console.error('Fee recording failed:', data.message);
        }
    } catch (error) {
        console.error('Fee recording error:', error);
    }
}
```

### Successful Response Example
```json
{
    "success": true,
    "message": "Fee recorded successfully",
    "data": {
        "fee_id": 45,
        "gross_amount": 100.50,
        "fee_amount": 2.51,
        "net_amount": 97.99,
        "currency": "GHS",
        "status": "completed",
        "recorded_at": "2025-01-28T10:30:00.000000Z"
    }
}
```

---

## 📊 3. Get User Fee History - GET `/withdrawal-fees/history`

**Purpose:** Show user their withdrawal fee history with pagination

### Request Parameters (Query String)
```
per_page=10&page=1&method=mobile_money
```

### Frontend Integration Example (JavaScript)
```javascript
// Get user's withdrawal fee history
async function loadWithdrawalFeeHistory(page = 1, perPage = 15, method = null) {
    try {
        const params = new URLSearchParams({
            page: page,
            per_page: perPage
        });
        
        if (method) {
            params.append('method', method);
        }

        const response = await fetch(`/api/v1/withdrawal-fees/history?${params}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            displayFeeHistory(data.data, data.pagination);
        }
    } catch (error) {
        console.error('Failed to load fee history:', error);
    }
}

function displayFeeHistory(fees, pagination) {
    const tableBody = document.getElementById('fee-history-table');
    tableBody.innerHTML = '';
    
    fees.forEach(fee => {
        const row = `
            <tr>
                <td>${new Date(fee.created_at).toLocaleDateString()}</td>
                <td>GHS ${fee.gross_amount}</td>
                <td>GHS ${fee.fee_amount}</td>
                <td>GHS ${fee.net_amount}</td>
                <td>${fee.withdrawal_method}</td>
                <td><span class="badge badge-${fee.status}">${fee.status}</span></td>
            </tr>
        `;
        tableBody.innerHTML += row;
    });
    
    // Update pagination
    updatePagination(pagination);
}
```

### Successful Response Example
```json
{
    "success": true,
    "message": "Fee history retrieved successfully",
    "data": [
        {
            "id": 45,
            "gross_amount": 100.50,
            "fee_amount": 2.51,
            "net_amount": 97.99,
            "withdrawal_method": "mobile_money",
            "network": "MTN",
            "status": "completed",
            "created_at": "2025-01-28T10:30:00.000000Z",
            "withdrawal": {
                "id": 123,
                "reference": "WD_123456789"
            }
        },
        {
            "id": 44,
            "gross_amount": 250.00,
            "fee_amount": 12.50,
            "net_amount": 237.50,
            "withdrawal_method": "bank_transfer",
            "network": null,
            "status": "completed",
            "created_at": "2025-01-27T15:45:00.000000Z",
            "withdrawal": {
                "id": 122,
                "reference": "WD_123456788"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 25,
        "last_page": 2
    }
}
```

---

## 📈 4. Get Fee Statistics - GET `/withdrawal-fees/statistics`

**Purpose:** Show user their withdrawal fee statistics and trends

### Request Parameters (Query String)
```
start_date=2025-01-01&end_date=2025-01-28
```

### Frontend Integration Example (JavaScript)
```javascript
// Get withdrawal fee statistics
async function loadFeeStatistics(startDate = null, endDate = null) {
    try {
        const params = new URLSearchParams();
        
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);

        const response = await fetch(`/api/v1/withdrawal-fees/statistics?${params}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            displayStatistics(data.data);
        }
    } catch (error) {
        console.error('Failed to load statistics:', error);
    }
}

function displayStatistics(stats) {
    // Update summary cards
    document.getElementById('total-transactions').textContent = stats.summary.total_transactions;
    document.getElementById('total-fees-paid').textContent = `GHS ${stats.summary.total_fees_paid}`;
    document.getElementById('average-fee').textContent = `GHS ${stats.summary.average_fee}`;
    
    // Create chart for method breakdown
    createMethodBreakdownChart(stats.by_method);
}

function createMethodBreakdownChart(methodData) {
    // Example using Chart.js
    const ctx = document.getElementById('method-chart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: methodData.map(item => item.method),
            datasets: [{
                data: methodData.map(item => item.total_fees),
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        }
    });
}
```

### Successful Response Example
```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "period": {
            "start_date": "2025-01-01T00:00:00.000000Z",
            "end_date": "2025-01-28T23:59:59.000000Z"
        },
        "summary": {
            "total_transactions": 15,
            "total_gross_amount": 2500.75,
            "total_fees_paid": 65.25,
            "total_net_amount": 2435.50,
            "average_fee": 4.35,
            "minimum_fee": 1.50,
            "maximum_fee": 12.50
        },
        "by_method": [
            {
                "method": "mobile_money",
                "transaction_count": 10,
                "total_fees": 45.75
            },
            {
                "method": "bank_transfer",
                "transaction_count": 5,
                "total_fees": 19.50
            }
        ]
    }
}
```

---

## 🚀 Complete Frontend Integration Example

### HTML Structure
```html
<!-- Withdrawal Fee Calculator -->
<div class="withdrawal-fee-calculator">
    <h3>💰 Withdrawal Fee Calculator</h3>
    
    <form id="fee-calculator-form">
        <div class="form-group">
            <label>Amount (GHS)</label>
            <input type="number" id="amount" step="0.01" min="1" max="10000" required>
        </div>
        
        <div class="form-group">
            <label>Withdrawal Method</label>
            <select id="method" required>
                <option value="">Select Method</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
            </select>
        </div>
        
        <div class="form-group" id="network-group" style="display: none;">
            <label>Mobile Network</label>
            <select id="network">
                <option value="">Select Network</option>
                <option value="MTN">MTN</option>
                <option value="Vodafone">Vodafone</option>
                <option value="AirtelTigo">AirtelTigo</option>
            </select>
        </div>
        
        <button type="submit">Calculate Fee</button>
    </form>
    
    <!-- Fee Breakdown Display -->
    <div id="fee-breakdown" style="display: none;">
        <h4>📊 Fee Breakdown</h4>
        <div class="fee-details">
            <p>Gross Amount: <span id="gross-amount"></span></p>
            <p>Fee Amount: <span id="fee-amount"></span></p>
            <p>Net Amount: <span id="net-amount"></span></p>
            <p>Fee Rate: <span id="fee-percentage"></span></p>
        </div>
    </div>
</div>
```

### Complete JavaScript Integration
```javascript
class WithdrawalFeeManager {
    constructor(baseUrl, token) {
        this.baseUrl = baseUrl;
        this.token = token;
    }

    async makeRequest(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const defaultHeaders = {
            'Authorization': `Bearer ${this.token}`,
            'Accept': 'application/json'
        };

        if (options.method === 'POST') {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            ...options,
            headers: { ...defaultHeaders, ...options.headers }
        });

        return await response.json();
    }

    async calculateFee(amount, method, network = null) {
        const params = new URLSearchParams({ amount, method });
        if (network) params.append('network', network);
        
        return await this.makeRequest(`/withdrawal-fees/calculate?${params}`);
    }

    async recordFee(data) {
        return await this.makeRequest('/withdrawal-fees/record', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async getHistory(page = 1, perPage = 15, method = null) {
        const params = new URLSearchParams({ page, per_page: perPage });
        if (method) params.append('method', method);
        
        return await this.makeRequest(`/withdrawal-fees/history?${params}`);
    }

    async getStatistics(startDate = null, endDate = null) {
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        
        return await this.makeRequest(`/withdrawal-fees/statistics?${params}`);
    }
}

// Initialize the manager
const feeManager = new WithdrawalFeeManager('/api/v1', localStorage.getItem('token'));

// Form handling
document.getElementById('fee-calculator-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const amount = document.getElementById('amount').value;
    const method = document.getElementById('method').value;
    const network = document.getElementById('network').value;
    
    try {
        const result = await feeManager.calculateFee(amount, method, network);
        
        if (result.success) {
            // Display fee breakdown
            document.getElementById('gross-amount').textContent = `GHS ${result.data.gross_amount}`;
            document.getElementById('fee-amount').textContent = `GHS ${result.data.fee_amount}`;
            document.getElementById('net-amount').textContent = `GHS ${result.data.net_amount}`;
            document.getElementById('fee-percentage').textContent = `${result.data.fee_percentage}%`;
            document.getElementById('fee-breakdown').style.display = 'block';
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Fee calculation failed:', error);
        alert('Failed to calculate fee. Please try again.');
    }
});

// Show/hide network selection based on method
document.getElementById('method').addEventListener('change', (e) => {
    const networkGroup = document.getElementById('network-group');
    if (e.target.value === 'mobile_money') {
        networkGroup.style.display = 'block';
        document.getElementById('network').required = true;
    } else {
        networkGroup.style.display = 'none';
        document.getElementById('network').required = false;
        document.getElementById('network').value = '';
    }
});
```

---

## 🔧 Testing with Postman/Insomnia

### Environment Variables
```
BASE_URL = http://your-domain.com/api/v1
TOKEN = your_sanctum_token_here
```

### Test Scenarios

1. **Calculate Mobile Money Fee**
   - GET `{{BASE_URL}}/withdrawal-fees/calculate?amount=100&method=mobile_money&network=MTN`

2. **Calculate Bank Transfer Fee**
   - GET `{{BASE_URL}}/withdrawal-fees/calculate?amount=500&method=bank_transfer`

3. **Record Fee After Withdrawal**
   - POST `{{BASE_URL}}/withdrawal-fees/record`
   - Body: JSON with withdrawal details

4. **Get Fee History**
   - GET `{{BASE_URL}}/withdrawal-fees/history?per_page=10&page=1`

5. **Get Statistics**
   - GET `{{BASE_URL}}/withdrawal-fees/statistics?start_date=2025-01-01&end_date=2025-01-28`

---

## 🐛 Error Handling

### Common Error Codes
- `422` - Validation errors (invalid input)
- `401` - Authentication required/invalid token
- `403` - Insufficient permissions
- `500` - Server error

### Frontend Error Handling
```javascript
function handleApiError(error, response) {
    if (response.status === 422) {
        // Validation errors
        displayValidationErrors(response.data.errors);
    } else if (response.status === 401) {
        // Redirect to login
        window.location.href = '/login';
    } else {
        // Generic error
        showErrorMessage('Something went wrong. Please try again.');
    }
}
```

---

## 🎯 Integration Checklist

- [ ] Add authentication token to all requests
- [ ] Handle loading states in UI
- [ ] Display fee breakdown clearly to users
- [ ] Implement proper error handling
- [ ] Add form validation
- [ ] Test all endpoints with different scenarios
- [ ] Implement pagination for history
- [ ] Add date range filtering for statistics
- [ ] Style the UI components
- [ ] Test with actual withdrawal flow

---

## 📝 Notes for Junior Developers

1. **Always validate input** - Both frontend and backend validation
2. **Handle errors gracefully** - Don't let the app crash on API errors
3. **Use loading states** - Show users when operations are in progress
4. **Keep tokens secure** - Never log or expose authentication tokens
5. **Test edge cases** - What happens with very small/large amounts?
6. **Document your code** - Future you will thank present you
7. **Use meaningful variable names** - Code should be self-documenting

This API is designed to be simple to integrate while being robust and secure. Start with the calculation endpoint to show fees to users, then implement the recording when you actually process withdrawals.
