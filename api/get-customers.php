<?php

require_once(__DIR__ . '/../data-service.php');
require_once(__DIR__ . '/../configuration/get-access-token.php');

header('Content-Type: application/json');

try {
    $authService = new QuickBooksAuthService();
    $accessToken = $authService->getAccessToken();

    if (!isset($accessToken)) {
        http_response_code(401);
        echo json_encode(['error' => '❌ Access Token not found. Please authenticate first.']);
        exit;
    }

    $dataService->updateOAuth2Token($accessToken);

    $customers = $dataService->Query("SELECT * FROM Customer ORDER BY Id DESC");

    if ($error = $dataService->getLastError()) {
        http_response_code(401);
        echo json_encode(['error' => '❌ API Error', 'details' => $error->getResponseBody()]);
        exit;
    }

    if (!$customers || !is_array($customers)) {
        echo json_encode(['message' => '⚠️ No customers found or invalid response.']);
        exit;
    }

    $response = [];

    foreach ($customers as $cust) {
        $response[] = [
            'id' => $cust->Id ?? '',
            'name' => $cust->DisplayName ?? '',
            'email' => $cust->PrimaryEmailAddr->Address ?? 'N/A',
            'phone' => $cust->PrimaryPhone->FreeFormNumber ?? 'N/A',
            'balance' => $cust->Balance ?? '0.00',
            'address' => isset($cust->BillAddr) ? implode(', ', array_filter([
                $cust->BillAddr->Line1 ?? '',
                $cust->BillAddr->City ?? '',
                $cust->BillAddr->Country ?? '',
            ])) : 'N/A'
        ];
    }

    echo json_encode(['data' => $response, 'message' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '❌ An unexpected error occurred.',
        'message' => $e->getMessage()
    ]);
}
