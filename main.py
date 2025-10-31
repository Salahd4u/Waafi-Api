from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from uuid import uuid4
from datetime import datetime
import requests, os
from dotenv import load_dotenv

load_dotenv()

app = FastAPI(title="WaafiPay HPP API")

# Load environment variables
MERCHANT_UID = os.getenv("MERCHANT_UID")
STORE_ID = os.getenv("STORE_ID")
HPP_KEY = os.getenv("HPP_KEY")
BASE_URL = os.getenv("BASE_URL", "https://sandbox.waafipay.net/asm")

# ============================
# 📦 1. PURCHASE ENDPOINT
# ============================

class PurchaseRequest(BaseModel):
    referenceId: str
    amount: float
    currency: str = "USD"
    description: str = "Payment for order"
    successUrl: str = "http://localhost:3000/api/hpp/success"
    failureUrl: str = "http://localhost:3000/api/hpp/failure"
    paymentMethod: str = "MWALLET_ACCOUNT"

@app.post("/purchase")
def initiate_purchase(data: PurchaseRequest):
    payload = {
        "schemaVersion": "1.0",
        "requestId": str(uuid4()),
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "channelName": "WEB",
        "serviceName": "HPP_PURCHASE",
        "serviceParams": {
            "merchantUid": MERCHANT_UID,
            "storeId": STORE_ID,
            "hppKey": HPP_KEY,
            "paymentMethod": data.paymentMethod,
            "hppSuccessCallbackUrl": data.successUrl,
            "hppFailureCallbackUrl": data.failureUrl,
            "hppRespDataFormat": 1,
            "transactionInfo": {
                "referenceId": data.referenceId,
                "amount": data.amount,
                "currency": data.currency,
                "description": data.description
            }
        }
    }

    res = requests.post(BASE_URL, json=payload)
    if res.status_code != 200:
        raise HTTPException(status_code=res.status_code, detail="WaafiPay service error")

    return res.json()

# ============================
# 💸 2. REFUND / WITHDRAW ENDPOINT
# ============================

class RefundRequest(BaseModel):
    transactionId: int
    amount: float
    description: str = "Order refund"

@app.post("/refund")
def refund_purchase(data: RefundRequest):
    payload = {
        "schemaVersion": "1.0",
        "requestId": str(uuid4()),
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "channelName": "WEB",
        "serviceName": "HPP_REFUNDPURCHASE",
        "serviceParams": {
            "merchantUid": MERCHANT_UID,
            "storeId": STORE_ID,
            "hppKey": HPP_KEY,
            "amount": data.amount,
            "transactionId": data.transactionId,
            "description": data.description
        }
    }

    res = requests.post(BASE_URL, json=payload)
    if res.status_code != 200:
        raise HTTPException(status_code=res.status_code, detail="WaafiPay refund error")

    return res.json()

# ============================
# 🔍 3. TRANSACTION INFO ENDPOINT
# ============================

class TransactionInfoRequest(BaseModel):
    referenceId: str

@app.post("/transaction-info")
def get_transaction_info(data: TransactionInfoRequest):
    payload = {
        "schemaVersion": "1.0",
        "requestId": str(uuid4()),
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "channelName": "WEB",
        "serviceName": "HPP_GETTRANINFO",
        "serviceParams": {
            "merchantUid": MERCHANT_UID,
            "storeId": STORE_ID,
            "hppKey": HPP_KEY,
            "referenceId": data.referenceId
        }
    }

    res = requests.post(BASE_URL, json=payload)
    if res.status_code != 200:
        raise HTTPException(status_code=res.status_code, detail="WaafiPay query error")

    return res.json()
