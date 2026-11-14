<?php
/**
 * HeaderFunctions.php
 * 
 * Handles all the common HTTP headers such as CORS and Content-Type.
 */

function setDefaultHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    // header("Content-Type: application/json; charset=UTF-8");
}

/**
 * Handle preflight OPTIONS requests.
 */
function handlePreflight() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
