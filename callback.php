<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/database-connection/databaseClass.php');

use QuickBooksOnline\API\DataService\DataService;

session_start();

function processCode()
{

    $config = include('config.php');
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create SDK instance
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "development"
    ));

    $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
    $parseUrl = parseAuthRedirectUrl(htmlspecialchars_decode($_SERVER['QUERY_STRING']));


    $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);
    $dataService->updateOAuth2Token($accessToken);

  
    $accessTokenValue  = $accessToken->getAccessToken();
    $access_token_expires_in = (new DateTime($accessToken->getAccessTokenExpiresAt()))->format('Y-m-d H:i:s');

    $refreshTokenValue = $accessToken->getRefreshToken();
    $refresh_token_expires_in = (new DateTime($accessToken->getRefreshTokenExpiresAt()))->format('Y-m-d H:i:s');
    $_SESSION['sessionAccessToken'] = $accessToken;
    $stmt = $conn->prepare("SELECT user_id FROM users LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $existing_user_id = $row['user_id'];

        $stmt = $conn->prepare("UPDATE users SET refresh_token=?, refresh_token_expire_at=?, access_token=?, access_token_expire_at=?, access_token_json=? WHERE user_id=?");
        $stmt->bind_param("sssssi", $refreshTokenValue, $refresh_token_expires_in, $accessTokenValue, $access_token_expires_in, serialize($accessToken), $existing_user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO users (refresh_token, refresh_token_expire_at, access_token, access_token_expire_at, access_token_json) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $refreshTokenValue, $refresh_token_expires_in, $accessTokenValue, $access_token_expires_in, serialize($accessToken));
        $stmt->execute();
    }

      $db->close();
   }

function parseAuthRedirectUrl($url)
{
    parse_str($url,$qsArray);
    return array(
        'code' => $qsArray['code'],
        'realmId' => $qsArray['realmId']
    );
}

$result = processCode();

?>
