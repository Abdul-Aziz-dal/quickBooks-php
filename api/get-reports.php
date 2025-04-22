<?php
try {

require_once(__DIR__ . '/../data-service.php');
require_once(__DIR__ . '/../configuration/get-access-token.php');
$authService = new QuickBooksAuthService();
$accessToken = $authService->getAccessToken();


if (!isset($accessToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Access token missing']);
    exit;
}

$dataService->updateOAuth2Token($accessToken);

$items = $dataService->FindAll('Item');
$itemNames = [];
foreach ($items as $item) {
    $itemNames[$item->Id] = $item->Name;
}

$invoices = $dataService->FindAll('Invoice');

$salesReport = [];

foreach ($invoices as $invoice) {
    if (!isset($invoice->Line) || !is_array($invoice->Line)) {
        continue;
    }

    foreach ($invoice->Line as $line) {
        if (
            isset($line->DetailType) &&
            $line->DetailType === 'SalesItemLineDetail' &&
            isset($line->SalesItemLineDetail)
        ) {
            $itemRefObj = $line->SalesItemLineDetail->ItemRef ?? null;
            $itemId = is_object($itemRefObj) ? $itemRefObj->value : ($itemRefObj ?? 'Unknown');
            $itemName = $itemNames[$itemId] ?? 'Unknown Item';
            $qty = $line->SalesItemLineDetail->Qty ?? 0;
            $amount = $line->Amount ?? 0;

            if (!isset($salesReport[$itemId])) {
                $salesReport[$itemId] = [
                    'item_name' => 0,
                    'quantity' => 0,
                    'total_sales' => 0
                ];
            }
            $salesReport[$itemId]['item_name'] = $itemName;
            $salesReport[$itemId]['quantity'] += $qty;
            $salesReport[$itemId]['total_sales'] += $amount;
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'data' => $salesReport]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}