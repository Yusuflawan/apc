<?php

require 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require_once './app/config/Database.php';
require_once './app/models/Users.php';

class AuthController
{
    private $secretKey;

    private $usersModel;
    private $notificationController;
    private $auditLogsModel;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET_KEY'];

        $database = new Database();
        $db = $database->connect();

        $this->usersModel = new Users($db);
    }

    // public function getProfile()
    // {
    //     $user = AuthMiddleware::user();

    //     if (!$user) {
    //         return errorResponse("Unauthorized access", 401);
    //     }

    //     return successResponse("Profile retrieved successfully", [
    //         "user" => $user
    //     ]);
    // }

    public function register()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $requiredFields = ["name", "email", "phone", "role", "gender", "state_id", "lga_id", "password", "account_name", "account_number", "bank_name", "photo_url"];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return errorResponse("Field '{$field}' is required");
            }
        }

        $name = trim($data["name"]);
        $email = trim($data["email"]);
        $role = trim($data["role"]);
        $phone = trim($data["phone"]);
        $gender = trim($data["gender"]);
        $accountNumber = trim($data["account_number"]);
        $accountName = trim($data["account_name"]);
        $bankName = trim($data["bank_name"]);
        $stateId = trim($data["state_id"]);
        $lgaId = trim($data["lga_id"]);
        $photoUrl = trim($data["photo_url"]);
        $password = password_hash($data["password"], PASSWORD_BCRYPT);

        if (!$this->isPublitioPhotoUrl($photoUrl)) {
            return errorResponse("A valid Publitio photo URL is required");
        }

        $checkEmail = $this->usersModel->getUserByEmail($email);
        if ($checkEmail["status"] === "success") {
            return errorResponse("This member already exists", 403);
        }

        $response = $this->usersModel->register($name, $email, $role, $phone, $gender, $accountNumber, $accountName, $bankName, $stateId, $lgaId, $password, $photoUrl);
        if ($response["status"] != "success") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        $registeredUser = $this->usersModel->getUserByEmail($email);
        // if ($registeredUser["status"] !== "success") {
        //     return errorResponse("Unable to fetch registered user details", 500);
        // }

        $userData = $registeredUser["data"];
        // Base payload for all users
        $payload = [
            'iat' => time(),
            'exp' => time() + (60 * 60 * 4), // 4 hours
            'id' => $userData["id"],
            'name' => $userData["name"],
            'email' => $userData["email"],
            'phone' => $userData["phone"],
            'gender' => $userData["gender"],
            'membership_id' => $userData["membership_id"],
            'photo_url' => $userData["photo_url"],
            'account_number' => $userData["account_number"],
            'account_name' => $userData["account_name"],
            'bank_name' => $userData["bank_name"],
            'state_name' => $userData["state_name"],
            'lga_name' => $userData["lga_name"],
            'role' => $userData["role"],
            'status' => $userData["status"]
        ];

        $token = JWT::encode($payload, $this->secretKey, 'HS256');

        return successResponse($response["message"], [
            "token" => $token,
            "user" => [
                "id" => $userData["id"],
                "name" => $userData["name"],
                "email" => $userData["email"],
                "phone" => $userData["phone"],
                "gender" => $userData["gender"],
                'membership_id' => $userData["membership_id"],
                "photo_url" => $userData["photo_url"],
                "account_number" => $userData["account_number"],
                "account_name" => $userData["account_name"],
                "bank_name" => $userData["bank_name"],
                "state_name" => $userData["state_name"],
                "lga_name" => $userData["lga_name"],
                "role" => $userData["role"],
                "status" => $userData["status"]
            ]
        ]);
    }

    public function uploadPhoto()
    {
        if (!isset($_FILES["photo"]) || !is_array($_FILES["photo"])) {
            return errorResponse("A membership photo is required");
        }

        $photo = $_FILES["photo"];
        if ($photo["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($photo["tmp_name"])) {
            return errorResponse("The membership photo upload failed");
        }

        $maxFileSize = 5 * 1024 * 1024;
        if ($photo["size"] < 1 || $photo["size"] > $maxFileSize) {
            return errorResponse("The membership photo must be 5 MB or smaller");
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($photo["tmp_name"]);
        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];
        if (!isset($allowedTypes[$mimeType]) || getimagesize($photo["tmp_name"]) === false) {
            return errorResponse("Choose a valid JPG, PNG, or WebP image");
        }

        $apiKey = $_ENV["PUBLITIO_API_KEY"] ?? "";
        $apiSecret = $_ENV["PUBLITIO_API_SECRET"] ?? "";
        if ($apiKey === "" || $apiSecret === "") {
            return errorResponse("Photo upload is not configured on the server", 503);
        }
        if (!function_exists("curl_init")) {
            return errorResponse("Photo upload is unavailable on the server", 503);
        }

        $timestamp = (string) time();
        $nonce = str_pad((string) random_int(0, 99999999), 8, "0", STR_PAD_LEFT);
        $signature = sha1($timestamp . $nonce . $apiSecret);
        $requestedPublicId = "membership-photo-" . strtoupper(bin2hex(random_bytes(4)));
        $query = http_build_query([
            "api_key" => $apiKey,
            "api_timestamp" => $timestamp,
            "api_nonce" => $nonce,
            "api_signature" => $signature
        ]);

        $curlFile = curl_file_create(
            $photo["tmp_name"],
            $mimeType,
            "membership-photo." . $allowedTypes[$mimeType]
        );
        $curl = curl_init("https://api.publit.io/v1/files/create?" . $query);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                "file" => $curlFile,
                "public_id" => $requestedPublicId,
                "privacy" => "1",
                "option_ad" => "0",
                "title" => "PBAT membership photo"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60
        ]);

        $responseBody = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlFailed = $responseBody === false;
        curl_close($curl);

        if ($curlFailed || $httpStatus < 200 || $httpStatus >= 300) {
            return errorResponse("Unable to upload the membership photo to Publitio", 502);
        }

        $uploadResult = json_decode($responseBody, true);
        if (!is_array($uploadResult)) {
            return errorResponse("Publitio did not return a valid image URL", 502);
        }

        $uploadData = isset($uploadResult["data"]) && is_array($uploadResult["data"])
            ? $uploadResult["data"]
            : $uploadResult;
        $publicId = $uploadData["public_id"] ?? "";
        $extension = strtolower((string) ($uploadData["extension"] ?? ""));
        if (
            !is_string($publicId) ||
            !preg_match('/^[A-Za-z0-9_-]+$/', $publicId) ||
            $publicId !== $requestedPublicId ||
            !in_array($extension, ["jpg", "jpeg", "png", "webp"], true)
        ) {
            return errorResponse("Publitio did not confirm the membership photo public ID", 502);
        }

        $urlCandidates = [
            $uploadData["url_preview"] ?? null,
            $uploadData["url"] ?? null,
            $uploadData["file"]["url_preview"] ?? null,
            $uploadData["file"]["url"] ?? null
        ];
        $photoUrl = "";
        foreach ($urlCandidates as $urlCandidate) {
            if (is_string($urlCandidate) && $this->isPublitioPhotoUrl($urlCandidate)) {
                $photoUrl = $urlCandidate;
                break;
            }
        }

        if (!$this->isPublitioPhotoUrl($photoUrl)) {
            return errorResponse("Publitio uploaded the image but did not return a usable image URL", 502);
        }

        return successResponse("Photo uploaded successfully", ["photo_url" => $photoUrl]);
    }

    private function isPublitioMediaDomain($host)
    {
        $host = strtolower($host);
        return $host === "media.publit.io" ||
            (strlen($host) > strlen(".publit.io") && substr($host, -strlen(".publit.io")) === ".publit.io");
    }

    private function isPublitioPhotoUrl($url)
    {
        if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        return is_array($parts)
            && isset($parts["scheme"], $parts["host"])
            && strtolower($parts["scheme"]) === "https"
            && $this->isPublitioMediaDomain($parts["host"]);
    }

    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data["email"]) || empty($data["password"])) {
            return errorResponse("All fields are required", 400);
        }

        $email = $data["email"];
        $password = $data["password"];

        $user = $this->usersModel->getUserByEmail($email);

        if ($user["status"] != "success") {
            return errorResponse($user["message"], $user["error_code"] ?? 401);
        }

        $userData = $user["data"];

        if (!password_verify($password, $userData["password_hash"])) {
            return errorResponse("Incorrect password", 401);
        }

        if ($userData["status"] !== "active") {
            return errorResponse("Your account is inactive. Please contact support.", 403);
        }

        // Base payload for all users
        $payload = [
            'iat' => time(),
            'exp' => time() + (60 * 60 * 4), // 4 hours
            'id' => $userData["id"],
            'name' => $userData["name"],
            'email' => $userData["email"],
            'phone' => $userData["phone"],
            'gender' => $userData["gender"],
            'membership_id' => $userData["membership_id"],
            'photo_url' => $userData["photo_url"],
            'account_number' => $userData["account_number"],
            'account_name' => $userData["account_name"],
            'bank_name' => $userData["bank_name"],
            'state_name' => $userData["state_name"],
            'lga_name' => $userData["lga_name"],
            'role' => $userData["role"],
            'status' => $userData["status"]
        ];

        $token = JWT::encode($payload, $this->secretKey, 'HS256');

        return successResponse("Login successful", [
            "token" => $token,
            "user" => [
                "id" => $userData["id"],
                "name" => $userData["name"],
                "email" => $userData["email"],
                "phone" => $userData["phone"],
                "gender" => $userData["gender"],
                'membership_id' => $userData["membership_id"],
                "photo_url" => $userData["photo_url"],
                "account_number" => $userData["account_number"],
                "account_name" => $userData["account_name"],
                "bank_name" => $userData["bank_name"],
                "state_name" => $userData["state_name"],
                "lga_name" => $userData["lga_name"],
                "role" => $userData["role"],
                "status" => $userData["status"]
            ]
        ]);
    }


    // public function updateCashierProfile($cashierId)
    // {
    //     $user = AuthMiddleware::user();

    //     if (!$user) {
    //         return errorResponse("Unauthorized access", 401);
    //     }

    //     $data = json_decode(file_get_contents("php://input"), true) ?: [];

    //     $userId = isset($user->user_id) ? (int)$user->user_id : (isset($user->id) ? (int)$user->id : null);
    //     if (!$userId) {
    //         return errorResponse("Invalid token payload", 401);
    //     }

    //     $name = isset($data["name"]) ? trim($data["name"]) : (isset($user->name) ? $user->name : null);
    //     $email = isset($data["email"]) ? trim($data["email"]) : (isset($user->email) ? $user->email : null);

    //     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //         return errorResponse("Invalid email format", 400);
    //     }

    //     $response = $this->usersModel->updateCashierProfile($cashierId, $name, $email);

    //     if ($response["status"] === "error") {
    //         return errorResponse($response["message"], $response["error_code"] ?? 500);
    //     }

    //     return successResponse($response["message"]);
    // }

    
    // public function updateCashierStatus($cashierId)
    // {
    //     $user = AuthMiddleware::user();

    //     if (!$user) {
    //         return errorResponse("Unauthorized access", 401);
    //     }

    //     $data = json_decode(file_get_contents("php://input"), true) ?: [];

    //     $userId = isset($user->user_id) ? (int)$user->user_id : (isset($user->id) ? (int)$user->id : null);
    //     if (!$userId) {
    //         return errorResponse("Invalid token payload", 401);
    //     }

    //     $newStatus = $data["status"];

    //     $response = $this->usersModel->updateCashierStatus($cashierId, $newStatus);

    //     if ($response["status"] === "error") {
    //         return errorResponse($response["message"], $response["error_code"] ?? 500);
    //     }

    //     return successResponse($response["message"]);
    // }

    // public function updateProfile()
    // {
    //     $user = AuthMiddleware::user();

    //     if (!$user) {
    //         return errorResponse("Unauthorized access", 401);
    //     }

    //     $data = json_decode(file_get_contents("php://input"), true) ?: [];

    //     $userId = (isset($user->id) ? (int)$user->id : null);
    //     if (!$userId) {
    //         return errorResponse("Invalid token payload", 401);
    //     }

    //     $name = $data["name"];
    //     $email = $data["email"];

    //     $response = $this->usersModel->updateProfile($userId, $name, $email);

    //     if ($response["status"] === "error") {
    //         return errorResponse($response["message"], $response["error_code"] ?? 500);
    //     }

    //     $userProfile = $this->usersModel->getUserByEmail($email);

    //     if ($userProfile["status"] === "error") {
    //         return errorResponse($userProfile["message"], $userProfile["error_code"] ?? 500);
    //     }

    //     $accessPayload = [
    //         'iat' => time(),
    //         'exp' => time() + (60 * 30),
    //         'type' => 'access',
    //         'id' => $userProfile["data"]["id"],
    //         'name' => $userProfile["data"]["name"],
    //         'email' => $userProfile["data"]["email"],
    //         'role' => $userProfile["data"]["role"],
    //         'status' => $userProfile["data"]["status"]
    //     ];

    //     $refreshPayload = [
    //         'iat' => time(),
    //         'exp' => time() + (60 * 60 * 24 * 7),
    //         'type' => 'refresh',
    //         'id' => $userProfile["data"]["id"],
    //         'name' => $userProfile["data"]["name"],
    //         'email' => $userProfile["data"]["email"],
    //         'role' => $userProfile["data"]["role"],
    //         'status' => $userProfile["data"]["status"]
    //     ];

    //     $token = JWT::encode($accessPayload, $this->secretKey, 'HS256');
    //     $refreshToken = JWT::encode($refreshPayload, $this->secretKey, 'HS256');

    //     return successResponse("Profile updated successfully", [
    //         "token" => $token,
    //         "refresh_token" => $refreshToken,
    //         "user" => [
    //             "id" => $userProfile["data"]["id"],
    //             "name" => $userProfile["data"]["name"],
    //             "email" => $userProfile["data"]["email"],
    //             "role" => $userProfile["data"]["role"],
    //             "status" => $userProfile["data"]["status"]
    //         ]
    //     ]);
    // }

    // public function updatePassword()
    // {
    //     $user = AuthMiddleware::user();

    //     if (!$user) {
    //         return errorResponse("Unauthorized access", 401);
    //     }

    //     $data = json_decode(file_get_contents("php://input"), true) ?: [];

    //     $userId = isset($user->user_id) ? (int)$user->user_id : (isset($user->id) ? (int)$user->id : null);
    //     if (!$userId) {
    //         return errorResponse("Invalid token payload", 401);
    //     }

    //     $requiredFields = [ "email", "existing_password", "new_password"];
    //     foreach ($requiredFields as $field) {
    //         if (empty($data[$field])) {
    //             return errorResponse("Field '{$field}' is required");
    //         }
    //     }

    //     $email = $data["email"];

    //     $userProfile = $this->usersModel->getUserByEmail($email);
    //     $existingPassword = $userProfile["data"]["password_hash"];

    //     if (!password_verify($data["existing_password"], $existingPassword)) {
    //         return errorResponse("Incorrect password", 401);
    //     }

    //     $newPassword = password_hash($data["new_password"], PASSWORD_BCRYPT);

    //     $response = $this->usersModel->updatePassword($userId, $newPassword);

    //     if ($response["status"] === "error") {
    //         return errorResponse($response["message"], $response["error_code"] ?? 500);
    //     }

    //     return successResponse($response["message"]);
    // }

}
 