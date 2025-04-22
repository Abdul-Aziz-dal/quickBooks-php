<?php

require_once(__DIR__ . '/../data-service.php');
require_once(__DIR__ . '/../configuration/get-access-token.php');

use QuickBooksOnline\API\Facades\Invoice;
$authService = new QuickBooksAuthService();
$accessToken = $authService->getAccessToken();

header('Content-Type: application/json');

try {
    if (!isset($accessToken)) {
        http_response_code(401);
        echo json_encode(['error' => 'Access token not found. Please authenticate.']);
        exit;
    }

    $customer_id=$_POST['customerId']??null;
    $item_id    =$_POST['itemId']    ??null;
    $quantity   =$_POST['quantity']  ??null;
    $amount     =$_POST['amount']    ??null;


    if (is_invalid($customer_id) || is_invalid($item_id) || is_invalid($quantity)|| is_invalid($amount)) {
        http_response_code(401);
        echo json_encode(['error' => 'Fill all fields!']);
        exit;
    }

    $dataService->updateOAuth2Token($accessToken);

    $customers = $dataService->FindAll('Customer', $customer_id);
    $items = $dataService->FindAll('Item', $item_id);
 
    if (!$customers || !$items) {
        $error = $dataService->getLastError();
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing customer or item data',
            'details' => [
                'status_code' => $error->getHttpStatusCode(),
                'helper_message' => $error->getOAuthHelperError(),
                'response_body' => $error->getResponseBody()
            ]
        ]);
        exit;
    }

    $customer = $customers[0];
    $item = $items[0];

    $theInvoice = Invoice::create([
        "CustomerRef" => [ "value" => $customer->Id ],
        "Line" => [
            [
                "Amount" => $amount,
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => [
                        "value" => $item->Id,
                        "name" => $item->Name
                    ],
                    "Qty" => $quantity
                ]
            ]
        ],
        "BillEmail" => [
            "Address" => $customer->PrimaryEmailAddr->Address ?? "test@example.com"
        ]
    ]);

    $result = $dataService->Add($theInvoice);

    if ($error = $dataService->getLastError()) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Failed to create invoice',
            'details' => [
                'status_code' => $error->getHttpStatusCode(),
                'helper_message' => $error->getOAuthHelperError(),
                'response_body' => $error->getResponseBody()
            ]
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Invoice created successfully!',
        'invoice_id' => $result->Id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}

function is_invalid($value) {
    return !isset($value) || empty($value);
}
