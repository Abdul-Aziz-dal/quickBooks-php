<?php

require_once(__DIR__ . '/../data-service.php');
require_once(__DIR__ . '/../configuration/get-access-token.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$authService = new QuickBooksAuthService();
$accessToken = $authService->getAccessToken();


if (!isset($accessToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing token']);
    exit;
}

 
$dataService->updateOAuth2Token($accessToken);

$invoiceId = $_GET['id'] ?? null;

if (!$invoiceId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing invoice ID']);
    exit;
}

try {
    $invoice = $dataService->FindById('Invoice', $invoiceId);
    if (!$invoice || !isset($invoice->Id)) {
        throw new Exception("Invoice not found");
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Invoice ID');
    $sheet->setCellValue('B1', 'Date');
    $sheet->setCellValue('C1', 'Customer Ref');
    $sheet->setCellValue('D1', 'Total Amount');

    $sheet->setCellValue('A2', $invoice->Id);
    $sheet->setCellValue('B2', $invoice->TxnDate);
    $sheet->setCellValue('C2', $invoice->CustomerRef);
    $sheet->setCellValue('D2', $invoice->TotalAmt);

    

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=invoice_{$invoiceId}.xlsx");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Excel export failed',
        'message' => $e->getMessage()
    ]);
}
