<?php

require_once(__DIR__ . '/../data-service.php');
require_once(__DIR__ . '/../configuration/get-access-token.php');

$authService = new QuickBooksAuthService();
$accessToken = $authService->getAccessToken();

if (!isset($accessToken)) {
    echo json_encode(['error' => 'Missing token']);
    exit;
}

$dataService->updateOAuth2Token($accessToken);

$invoices = $dataService->Query("SELECT * FROM Invoice ORDER BY Id DESC");

if (!$invoices || count($invoices) === 0) {
    echo json_encode(['message' => 'No invoices found']);
    exit;
}

$invoiceData = [];

foreach ($invoices as $invoice) {
    $invoiceData[] = [
        'id' => $invoice->Id ?? '',
        'docNumber' => $invoice->DocNumber ?? '',
        'customerRef' => $invoice->CustomerRef->name ?? '',
        'totalAmount' => $invoice->TotalAmt ?? '',
        'balance' => $invoice->Balance ?? '',
        'status' => $invoice->TxnStatus ?? '',
        'txnDate' => $invoice->TxnDate ?? '',
        'dueDate' => $invoice->DueDate ?? '',
        'billingEmail' => $invoice->BillEmail->Address ?? '',
        'billingAddress' => !empty($invoice->BillAddr) ? implode(', ', array_filter([
            $invoice->BillAddr->Line1 ?? '',
            $invoice->BillAddr->City ?? '',
            $invoice->BillAddr->CountrySubDivisionCode ?? '',
            $invoice->BillAddr->PostalCode ?? '',
        ])) : '',
        'downloadLink' => 'api/invoice-excel-download.php?id=' . $invoice->Id,
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $invoiceData
], JSON_PRETTY_PRINT);
