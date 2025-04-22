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

    $items = $dataService->Query("SELECT * FROM Item ORDER BY Id DESC");
    
    if ($error = $dataService->getLastError()) {
        http_response_code(500);
        echo json_encode(['error' => '❌ API Error', 'details' => $error->getResponseBody()]);
        exit;
    }

    if (!$items || !is_array($items)) {
        http_response_code(404);
        echo json_encode(['message' => '⚠️ No items found or invalid response.']);
        exit;
    }

    $response = [];

    foreach ($items as $item) {
        $response[] = [
            'id' => $item->Id ?? '',
            'name' => $item->Name ?? '',
            'type' => $item->Type ?? '',
            'unit_price' => $item->UnitPrice ?? 0,
            'description' => $item->Description ?? '',
            'qty_on_hand' => $item->QtyOnHand ?? 0
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
