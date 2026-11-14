<?php

function jsonResponse($status, $message, $data = null, $extra = [], $code = 200)
{
    http_response_code($code);

    $response = [
        "status" => $status,
        "message" => $message,
        "data" => $data
    ];

    // merge any extra keys like pagination, meta, etc.
    if (!empty($extra) && is_array($extra)) {
        $response = array_merge($response, $extra);
    }

    header('Content-Type: application/json; charset=utf-8');

    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    if ($json === false) {
        $fallback = [
            "status" => "error",
            "message" => "Response encoding failed",
            "data" => null
        ];
        $json = json_encode($fallback, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    echo $json;
    exit;
}

function successResponse($message, $data = null, $extra = [])
{
    jsonResponse("success", $message, $data, $extra, 200);
}

function errorResponse($message, $code = 400)
{
    jsonResponse("error", $message, null, [], $code);
}
