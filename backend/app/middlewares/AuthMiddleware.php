<?php

require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {

    private $secretKey;
    public static $user;

    public function __construct() {
        $this->secretKey = $_ENV['JWT_SECRET_KEY'];
    }

    private function getAuthorizationHeader() {
        if (isset($_SERVER['Authorization'])) {
            return trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return trim($headers['Authorization']);
            }
        }
        return null;
    }

    public function authenticate() {
        $authHeader = $this->getAuthorizationHeader();

        if (!$authHeader) {
            errorResponse('Authorization header not found', 401);
        }

        list($jwt) = sscanf($authHeader, 'Bearer %s');

        if (!$jwt) {
            errorResponse('Token not provided', 401);
        }

        try {
            $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
            $tokenType = $decoded->type ?? 'access';

            if ($tokenType !== 'access') {
                errorResponse('Invalid token type', 401);
            }

            self::$user = $decoded; // store user globally
        } catch (Exception $e) {
            errorResponse('Invalid token: ' . $e->getMessage(), 401);
        }
    }

    public static function user() {
        return self::$user ?? null;
    }
}
