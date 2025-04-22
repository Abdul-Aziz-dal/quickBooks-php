<?php

header('Content-Type: application/json');

function getCompanyInfo()
{
    require_once(__DIR__ . '/../data-service.php');
    require_once(__DIR__ . '/../configuration/get-access-token.php');

    try {
        $authService = new QuickBooksAuthService();
        $accessToken = $authService->getAccessToken();

        if (!isset($accessToken)) {
            http_response_code(401);
            return json_encode(['error' => '❌ Access Token not found or expired']);
        }

        $dataService->updateOAuth2Token($accessToken);

        $companyInfo = $dataService->getCompanyInfo();

        if (!$companyInfo) {
            http_response_code(500);
            return json_encode(['error' => '❌ API call failed or no response']);
        }

        return json_encode([
            'success' => true,
            'company_name' => $companyInfo->CompanyName ?? '',
            'address' => [
                'line1' => $companyInfo->CompanyAddr->Line1 ?? '',
                'city' => $companyInfo->CompanyAddr->City ?? '',
                'postal_code' => $companyInfo->CompanyAddr->PostalCode ?? ''
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        return json_encode([
            'error' => '❌ An unexpected error occurred',
            'message' => $e->getMessage()
        ]);
    }
}

echo getCompanyInfo();

?>
