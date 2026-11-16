<?php

class Events
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // get all events
    public function getAllEvents()
    {
        try {
            $query = "SELECT * FROM events";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["status" => "success", "message" => "Events retrieved successfully.", "data" => $events];
        } catch (PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
        }
    }

    public function createEvent($photoUrl, $description, $date)
    {
        try {
            $query = "INSERT INTO events (photo_url, description, `date`)
                      VALUES (:photo_url, :description, :date)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":photo_url", $photoUrl, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":date", $date, PDO::PARAM_STR);
            $stmt->execute();

            return [
                "status" => "success",
                "message" => "Event created successfully.",
                "data" => [
                    "photo_url" => $photoUrl,
                    "description" => $description,
                    "date" => $date
                ]
            ];
        } catch (PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
        }
    }

}

