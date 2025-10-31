# WaafiPay HPP API Backend

A FastAPI backend that integrates with WaafiPay's Hosted Payment Page (HPP) services.

## Features

- ✅ **Purchase** (HPP_PURCHASE) - Initiate payment transactions
- ✅ **Refund/Withdraw** (HPP_REFUNDPURCHASE) - Process refunds or withdrawals
- ✅ **Transaction Info** (HPP_GETTRANINFO) - Retrieve transaction details
- ✅ **API Hosted Payment Docs Page Url** https://docs.waafipay.com/hpp-api

## Setup

1. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

2. **Configure environment variables:**
   - Copy `.env` file and update with your actual WaafiPay credentials:
     - `MERCHANT_UID`: Your merchant unique identifier
     - `STORE_ID`: Your store identifier
     - `HPP_KEY`: Your HPP key
     - `BASE_URL`: API endpoint (defaults to sandbox)

3. **Run the server:**
   ```bash
   uvicorn main:app --reload
   ```

   The API will be available at `http://localhost:8000`

## API Endpoints

### 1. Initiate Purchase
**POST** `/purchase`

Request body:
```json
{
  "referenceId": "order-123",
  "amount": 100.00,
  "currency": "USD",
  "description": "Payment for order",
  "successUrl": "http://localhost:3000/api/hpp/success",
  "failureUrl": "http://localhost:3000/api/hpp/failure",
  "paymentMethod": "MWALLET_ACCOUNT"
}
```

### 2. Refund Purchase
**POST** `/refund`

Request body:
```json
{
  "transactionId": 12345,
  "amount": 50.00,
  "description": "Order refund"
}
```

### 3. Get Transaction Info
**POST** `/transaction-info`

Request body:
```json
{
  "referenceId": "order-123"
}
```

## Documentation

Once the server is running, visit `http://localhost:8000/docs` for interactive API documentation.
# Waafi-Api
