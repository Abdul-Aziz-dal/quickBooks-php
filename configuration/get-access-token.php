<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once('../database-connection/databaseClass.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;

class QuickBooksAuthService {
    private $db;
    private $conn;
    private $config;
    private $userId = 1;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->config = include(__DIR__ . '/../config.php');
    }

    public function getAccessToken() {
        $user = $this->getUserTokens();
        if ($this->isExpired($user['access_token_expire_at'])) {
            return $this->refreshToken($user['refresh_token']);
        }
  
           $access_token=  unserialize($user['access_token_json']);
    return $access_token;
    }

    private function refreshToken($refreshToken) {
        $dataService = DataService::Configure([
            'auth_mode'       => 'oauth2',
            'ClientID'        => $this->config['client_id'],
            'ClientSecret'    => $this->config['client_secret'],
            'RedirectURI'     => $this->config['oauth_redirect_uri'],
            'refreshTokenKey' => $refreshToken,
            'QBORealmID'      => $this->config['realm_id'],
            'baseUrl'         => 'development'
        ]);

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $refreshedToken = $OAuth2LoginHelper->refreshToken();

        $this->updateTokens($refreshedToken);
        $_SESSION['sessionAccessToken'] = $refreshedToken;
        return $refreshedToken;
    }

    private function updateTokens($token) {
        $stmt = $this->conn->prepare(
            "UPDATE users SET access_token=?, access_token_expire_at=?, refresh_token=?, refresh_token_expire_at=? ,access_token_json = ?  WHERE user_id=?"
        );

        $accessToken = $token->getAccessToken();
        $refreshToken = $token->getRefreshToken();
        $accessTokenJson = serialize($token);
        
        $access_token_expires_in = (new DateTime($token->getAccessTokenExpiresAt()))->format('Y-m-d H:i:s');
        $refresh_token_expires_in = (new DateTime($token->getRefreshTokenExpiresAt()))->format('Y-m-d H:i:s');
        
        $stmt->bind_param(
            "sssssi",
            $accessToken,
            $access_token_expires_in,
            $refreshToken,
            $refresh_token_expires_in,
            $accessTokenJson,
            $this->userId
        );
        $stmt->execute();
    }

    private function getUserTokens() {
        $result = mysqli_query($this->conn, "SELECT * FROM users WHERE user_id = {$this->userId} LIMIT 1");
        return mysqli_fetch_assoc($result);
    }

    private function isExpired($dateTime) {
        return strtotime($dateTime) < time();
    }
}
