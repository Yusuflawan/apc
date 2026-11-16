<?php

class PublitioPhotoUploader
{
    public static function upload($photo, $purpose)
    {
        if (!in_array($purpose, ["event", "membership"], true)) {
            return self::failure("Invalid photo upload purpose", 500);
        }

        $photoLabel = $purpose === "event" ? "event photo" : "membership photo";
        if (!is_array($photo)) {
            return self::failure(
                $purpose === "event" ? "An event photo is required" : "A membership photo is required",
                400
            );
        }

        $temporaryPath = $photo["tmp_name"] ?? "";
        if (
            ($photo["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_string($temporaryPath) ||
            !is_uploaded_file($temporaryPath)
        ) {
            return self::failure("The " . $photoLabel . " upload failed", 400);
        }

        $maxFileSize = 5 * 1024 * 1024;
        $fileSize = $photo["size"] ?? 0;
        if ($fileSize < 1 || $fileSize > $maxFileSize) {
            return self::failure("The " . $photoLabel . " must be 5 MB or smaller", 400);
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryPath);
        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];
        if (!isset($allowedTypes[$mimeType]) || getimagesize($temporaryPath) === false) {
            return self::failure("Choose a valid JPG, PNG, or WebP image", 400);
        }

        $apiKey = $_ENV["PUBLITIO_API_KEY"] ?? "";
        $apiSecret = $_ENV["PUBLITIO_API_SECRET"] ?? "";
        if ($apiKey === "" || $apiSecret === "") {
            return self::failure("Photo upload is not configured on the server", 503);
        }
        if (!function_exists("curl_init")) {
            return self::failure("Photo upload is unavailable on the server", 503);
        }

        $timestamp = (string) time();
        $nonce = str_pad((string) random_int(0, 99999999), 8, "0", STR_PAD_LEFT);
        $signature = sha1($timestamp . $nonce . $apiSecret);
        $requestedPublicId = $purpose . "-photo-" . strtoupper(bin2hex(random_bytes(4)));
        $query = http_build_query([
            "api_key" => $apiKey,
            "api_timestamp" => $timestamp,
            "api_nonce" => $nonce,
            "api_signature" => $signature
        ]);

        $curlFile = curl_file_create(
            $temporaryPath,
            $mimeType,
            $purpose . "-photo." . $allowedTypes[$mimeType]
        );
        $curl = curl_init("https://api.publit.io/v1/files/create?" . $query);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                "file" => $curlFile,
                "public_id" => $requestedPublicId,
                "privacy" => "1",
                "option_ad" => "0",
                "title" => "PBAT " . $photoLabel
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
            return self::failure("Unable to upload the " . $photoLabel . " to Publitio", 502);
        }

        $uploadResult = json_decode($responseBody, true);
        if (!is_array($uploadResult)) {
            return self::failure("Publitio did not return a valid image URL", 502);
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
            return self::failure("Publitio did not confirm the requested photo upload", 502);
        }

        $fileData = $uploadData["file"] ?? null;
        $urlCandidates = [
            $uploadData["url_preview"] ?? null,
            $uploadData["url"] ?? null,
            is_array($fileData) ? ($fileData["url_preview"] ?? null) : null,
            is_array($fileData) ? ($fileData["url"] ?? null) : null
        ];
        foreach ($urlCandidates as $urlCandidate) {
            if (is_string($urlCandidate) && self::isPublitioPhotoUrl($urlCandidate)) {
                return ["status" => "success", "photo_url" => $urlCandidate];
            }
        }

        return self::failure("Publitio uploaded the photo but did not return a usable image URL", 502);
    }

    public static function isPublitioPhotoUrl($url)
    {
        if (!is_string($url) || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        if (
            !is_array($parts) ||
            !isset($parts["scheme"], $parts["host"]) ||
            strtolower($parts["scheme"]) !== "https"
        ) {
            return false;
        }

        $host = strtolower($parts["host"]);
        return $host === "media.publit.io" ||
            (strlen($host) > strlen(".publit.io") && substr($host, -strlen(".publit.io")) === ".publit.io");
    }

    private static function failure($message, $code)
    {
        return ["status" => "error", "message" => $message, "error_code" => $code];
    }
}
