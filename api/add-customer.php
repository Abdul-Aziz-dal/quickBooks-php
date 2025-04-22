<?php
require_once(__DIR__ . '/../data-service.php');
require_once(__DIR__ . '/../configuration/get-access-token.php');
use QuickBooksOnline\API\Facades\Customer;

$authService = new QuickBooksAuthService();
$accessToken = $authService->getAccessToken();

if (!isset($accessToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'Access token not found. Please authenticate.']);
    exit;
}

$dataService->updateOAuth2Token($accessToken);

$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? null;
$mobile = $_POST['mobile'] ?? null;
$company = $_POST['company'] ?? null;
$notes = $_POST['notes'] ?? null;
$balance = isset($_POST['balance']) ? floatval($_POST['balance']) : 0.0;

$billing_line1 = $_POST['billing_line1'] ?? null;
$billing_city = $_POST['billing_city'] ?? null;
$billing_state = $_POST['billing_state'] ?? null;
$billing_postal_code = $_POST['billing_postal_code'] ?? null;
$billing_country = $_POST['billing_country'] ?? null;

if (!$name || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing name or email']);
    exit;
}

try {
    $customer = Customer::create([
        "DisplayName" => $name,
        "CompanyName" => $company,
        "PrimaryEmailAddr" => [
            "Address" => $email
        ],
        "PrimaryPhone" => [
            "FreeFormNumber" => $phone
        ],
        "Mobile" => [
            "FreeFormNumber" => $mobile
        ],
        "BillAddr" => [
            "Line1" => $billing_line1,
            "City" => $billing_city,
            "CountrySubDivisionCode" => $billing_state, 
            "PostalCode" => $billing_postal_code,
            "Country" => $billing_country
        ],
        "Notes" => $notes,
        "Balance" => $balance 
    ]);

    $createdCustomer = $dataService->Add($customer);

    echo json_encode([
        'success' => true,
        'customerId' => $createdCustomer->Id,
        'name' => $createdCustomer->DisplayName
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
