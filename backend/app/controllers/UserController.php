<?php

require 'vendor/autoload.php';

require_once './app/config/Database.php';
require_once './app/models/Users.php';

class UserController
{
    private $usersModel;

    public function __construct()
    {

        $database = new Database();
        $db = $database->connect();

        $this->usersModel = new Users($db);
    }

    public function getCashiers()
    {
        $response = $this->usersModel->getCashiers();

        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        return successResponse($response["message"], $response["data"]);
    }

    public function getCustomers()
    {
        $response = $this->usersModel->getCustomers();

        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        return successResponse($response["message"], $response["data"]);
    }

    public function getMembers()
    {
        $response = $this->usersModel->getMembers();

        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        $members = $response["data"];
        return successResponse($response["message"], [
            "count" => count($members),
            "members" => $members
        ]);
    }

    public function getStates()
    {
        $response = $this->usersModel->getStates();

        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        return successResponse($response["message"], $response["data"]);
    }
    
    public function getLGAsByStateId($id)
    {
        $response = $this->usersModel->getLGAsByStateId($id);

        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        return successResponse($response["message"], $response["data"]);
    }

}