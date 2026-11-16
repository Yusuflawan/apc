<?php

require 'vendor/autoload.php';

require_once './app/config/Database.php';
require_once './app/models/Events.php';
require_once './app/helpers/PublitioPhotoUploader.php';

class EventController
{
    private $eventsModel;

    public function __construct()
    {

        $database = new Database();
        $db = $database->connect();

        $this->eventsModel = new Events($db);
    }

    public function getAllEvents()
    {
        $response = $this->eventsModel->getAllEvents();

        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        return successResponse($response["message"], $response["data"]);
    }

    public function createEvent()
    {
        $description = isset($_POST["description"]) && is_string($_POST["description"])
            ? trim($_POST["description"])
            : "";
        $date = isset($_POST["date"]) && is_string($_POST["date"])
            ? trim($_POST["date"])
            : "";

        if ($description === "" || strlen($description) > 5000) {
            return errorResponse("Event description is required and must be 5000 characters or fewer", 400);
        }

        $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            !$eventDate ||
            ($dateErrors !== false && ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)) ||
            $eventDate->format('Y-m-d') !== $date
        ) {
            return errorResponse("Enter a valid event date", 400);
        }
        if ($date > date('Y-m-d')) {
            return errorResponse("Past events cannot have a future date", 400);
        }

        $upload = PublitioPhotoUploader::upload($_FILES["photo"] ?? null, "event");
        if ($upload["status"] === "error") {
            return errorResponse($upload["message"], $upload["error_code"]);
        }

        $photoUrl = $upload["photo_url"];
        $response = $this->eventsModel->createEvent($photoUrl, $description, $date);
        if ($response["status"] === "error") {
            return errorResponse($response["message"], $response["error_code"] ?? 500);
        }

        return successResponse($response["message"], $response["data"]);
    }

}