<?php
/**
 * WaafiPay HPP API PHP Integration By Jamal
 * 
 * Features in this file:
 * - Handles purchase, withdraw/refund, and transaction info.
 * - Loads credentials from environment variables.
 * - Unified POST request helper using cURL.
 * - Returns proper HTTP codes and JSON errors.
 * - Fully commented for clarity.
 */

// Load environment variables
$MERCHANT_UID = getenv('MERCHANT_UID');
$STORE_ID     = getenv('STORE_ID');
$HPP_KEY      = getenv('HPP_KEY');
$BASE_URL     = getenv('BASE_URL') ?: 'https://sandbox.waafipay.net/asm';

/**
 * Helper function to send POST requests to WaafiPay
 *
 * @param string $url     API endpoint
 * @param array  $payload JSON payload to send
 * @return array          Response as associative array
 */
function sendRequest($url, $payload) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        http_response_code($httpcode);
        echo json_encode(['error' => 'WaafiPay service error']);
        exit;
    }

    return json_decode($response, true);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = strtolower($data['action'] ?? '');

    switch ($action) {
        // Purchase request
        case 'purchase':
            $payload = [
                "schemaVersion" => "1.0",
                "requestId" => uniqid(),
                "timestamp" => date("Y-m-d H:i:s"),
                "channelName" => "WEB",
                "serviceName" => "HPP_PURCHASE",
                "serviceParams" => [
                    "merchantUid" => $MERCHANT_UID,
                    "storeId" => $STORE_ID,
                    "hppKey" => $HPP_KEY,
                    "paymentMethod" => $data['paymentMethod'] ?? 'MWALLET_ACCOUNT',
                    "hppSuccessCallbackUrl" => $data['successUrl'] ?? 'http://localhost:3000/api/hpp/success',
                    "hppFailureCallbackUrl" => $data['failureUrl'] ?? 'http://localhost:3000/api/hpp/failure',
                    "hppRespDataFormat" => 1,
                    "transactionInfo" => [
                        "referenceId" => $data['referenceId'],
                        "amount" => $data['amount'],
                        "currency" => $data['currency'] ?? 'USD',
                        "description" => $data['description'] ?? 'Payment for order'
                    ]
                ]
            ];
            echo json_encode(sendRequest($BASE_URL, $payload));
            break;

        // Withdraw or refund request
        case 'withdraw':
        case 'refund':
            $payload = [
                "schemaVersion" => "1.0",
                "requestId" => uniqid(),
                "timestamp" => date("Y-m-d H:i:s"),
                "channelName" => "WEB",
                "serviceName" => "HPP_REFUNDPURCHASE",
                "serviceParams" => [
                    "merchantUid" => $MERCHANT_UID,
                    "storeId" => $STORE_ID,
                    "hppKey" => $HPP_KEY,
                    "amount" => $data['amount'],
                    "transactionId" => $data['transactionId'],
                    "description" => $data['description'] ?? 'Order refund'
                ]
            ];
            echo json_encode(sendRequest($BASE_URL, $payload));
            break;

        // Get transaction info
        case 'info':
        case 'transaction-info':
            $payload = [
                "schemaVersion" => "1.0",
                "requestId" => uniqid(),
                "timestamp" => date("Y-m-d H:i:s"),
                "channelName" => "WEB",
                "serviceName" => "HPP_GETTRANINFO",
                "serviceParams" => [
                    "merchantUid" => $MERCHANT_UID,
                    "storeId" => $STORE_ID,
                    "hppKey" => $HPP_KEY,
                    "referenceId" => $data['referenceId']
                ]
            ];
            echo json_encode(sendRequest($BASE_URL, $payload));
            break;

        // Invalid action
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action. Use "purchase", "withdraw", "refund", or "info".']);
            break;
    }

} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
}
