<?php

class Users
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function register($name, $email, $role, $phone, $gender, $accountNumber, $accountName, $bankName, $stateId, $lgaId, $password, $photoUrl)
    {
        try {
            // create a membership ID for the user
            $membershipId = 'DTD-' . uniqid(); //EXAMPLE: DTD-5f2b5c8e7a1a3

            $query = "INSERT INTO users(name, email, role, phone, gender, account_number, account_name, bank_name, state_id, lga_id, password_hash, membership_id, photo_url) VALUES(:name, :email, :role, :phone, :gender, :account_number, :account_name, :bank_name, :state_id, :lga_id, :password_hash, :membership_id, :photo_url)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":role", $role);
            $stmt->bindParam(":phone", $phone);
            $stmt->bindParam(":gender", $gender);
            $stmt->bindParam(":account_number", $accountNumber);
            $stmt->bindParam(":account_name", $accountName);
            $stmt->bindParam(":bank_name", $bankName);
            $stmt->bindParam(":state_id", $stateId);
            $stmt->bindParam(":lga_id", $lgaId);
            $stmt->bindParam(":password_hash", $password);
            $stmt->bindParam(":membership_id", $membershipId);
            $stmt->bindParam(":photo_url", $photoUrl);
            $stmt->execute();

            return ["status" => "success", "message" => "Member created successfully."];
        } catch (PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
        }
    }
 
    public function getUserByEmail($email)
    {
        try {
            $sql = "SELECT
                        users.id,
                        users.name,
                        users.email,
                        users.phone,
                        users.gender,
                        users.account_number,
                        users.account_name,
                        users.bank_name,
                        users.role,
                        users.status,
                        users.membership_id,
                        users.photo_url,
                        users.password_hash,
                        states.state AS state_name,
                        lgas.lga_name AS lga_name
                    FROM users
                    LEFT JOIN states ON users.state_id = states.id
                    LEFT JOIN lgas ON users.lga_id = lgas.id
                    WHERE users.email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return ["status" => "error", "message" => "Incorrect email", "error_code" => 401];
            }

            return ["status" => "success", "message" => "success", "data" => $data];
        } catch (Exception $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
        }
    }

    public function getStates()
    {
        try {
            $query = "SELECT * FROM states";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["status" => "success", "message" => "success", "data" => $data];

        } catch (PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];

        }
    }

    public function getMembers()
    {
        try {
            $query = "SELECT
                        users.id,
                        users.name,
                        users.email,
                        users.phone,
                        users.gender,
                        users.membership_id,
                        users.photo_url,
                        users.status,
                        states.state AS state_name,
                        lgas.lga_name AS lga_name
                    FROM users
                    LEFT JOIN states ON users.state_id = states.id
                    LEFT JOIN lgas ON users.lga_id = lgas.id
                    WHERE users.role = :role
                    ORDER BY users.name ASC";
            $stmt = $this->db->prepare($query);
            $role = "member";
            $stmt->bindParam(":role", $role, PDO::PARAM_STR);
            $stmt->execute();
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["status" => "success", "message" => "Members retrieved successfully.", "data" => $members];
        } catch (PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
        }
    }

    public function getLGAsByStateId($id)
    {
        try {
            $query = "SELECT * FROM lgas WHERE state_id = :state_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":state_id", $id);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["status" => "success", "message" => "success", "data" => $data];

        } catch (PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];

        }
    }

    // public function getCustomers()
    // {
    //     try {
    //         $query = "SELECT * FROM customers";
    //         $stmt = $this->db->prepare($query);
    //         $stmt->execute();
    //         $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         return ["status" => "success", "message" => "success", "data" => $data];

    //     } catch (PDOException $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];

    //     }
    // }

    // public function createCustomer($name, $phone)
    // {
    //     try {
    //         $query = "INSERT INTO customers(name, phone) VALUES(:name, :phone)";
    //         $stmt = $this->db->prepare($query);
    //         $stmt->bindParam(":name", $name);
    //         $stmt->bindParam(":phone", $phone);
    //         $stmt->execute();

    //         $customerId = $this->db->lastInsertId();

    //         return ["status" => "success", "message" => "Customer created successfully.", "customer_id" => $customerId];
    //     } catch (PDOException $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
    //     }
    // }

    // public function updateCashierProfile($cashierId, $name, $email)
    // {
    //     try {
    //         $sql = "UPDATE users SET name = :name, email = :email WHERE id = :cashier_id";
    //         $stmt = $this->db->prepare($sql);
    //         $stmt->bindParam(":name", $name, PDO::PARAM_STR);
    //         $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    //         $stmt->bindParam(":cashier_id", $cashierId, PDO::PARAM_INT);
            
    //         if ($stmt->execute()) {
    //             return ["status" => "success", "message" => "User updated successfully"];
    //         }

    //     } catch (Exception $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
    //     }
    // }


    // public function updateCashierStatus($cashierId, $newStatus)
    // {
    //     try {
    //         $sql = "UPDATE users SET status = :status WHERE id = :cashier_id";
    //         $stmt = $this->db->prepare($sql);
    //         $stmt->bindParam(":status", $newStatus, PDO::PARAM_STR);
    //         $stmt->bindParam(":cashier_id", $cashierId, PDO::PARAM_INT);

    //         if ($newStatus == "active") {
    //             $status = "activated";
    //         } else if($newStatus == "inactive"){
    //             $status = "deactivated";
    //         }
            
    //         if ($stmt->execute()) {
    //             return ["status" => "success", "message" => "User $status successfully"];
    //         }

    //     } catch (Exception $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
    //     }
    // }

    // public function getCashiers()
    // {
    //     try {
    //         $query = "SELECT
    //         users.id,
    //         users.name,
    //         users.email,
    //         users.role,
    //         users.status,
    //         users.created_at
    //          FROM users WHERE role = :role";
    //         $stmt = $this->db->prepare($query);
    //         $role = "cashier";
    //         $stmt->bindParam(":role", $role, PDO::PARAM_STR);
    //         $stmt->execute();
    //         $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         return ["status" => "success", "message" => "success", "data" => $data];

    //     } catch (PDOException $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];

    //     }
    // }

    // public function updateProfile($userId, $name, $email)
    // {
    //     try {
    //         $query = "UPDATE users SET name = :name, email = :email WHERE id = :user_id";
    //         $stmt = $this->db->prepare($query);
    //         $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    //         $stmt->bindParam(":name", $name, PDO::PARAM_STR);
    //         $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    //         $stmt->execute();

    //         if ($stmt->rowCount() > 0) {
    //             return ["status" => "success", "message" => "Profile updated successfully"];
    //         } else {
    //             return ["status" => "error", "message" => "failed to update profile"];
    //         }
    //     } catch (PDOException $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];
    //     }
    // }

    // public function updatePassword($userId, $newPassword)
    // {
    //     try {
    //         $query = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
    //         $stmt = $this->db->prepare($query);
    //         $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    //         $stmt->bindParam(":password_hash", $newPassword, PDO::PARAM_STR);
    //         $stmt->execute();

    //         if ($stmt->rowCount() > 0) {
    //             return ["status" => "success", "message" => "Password updated successfully"];
    //         } else{
    //             return ["status" => "error", "message" => "failed to update password"];
    //         }
    //     } catch (PDOException $e) {
    //         return ["status" => "error", "message" => $e->getMessage(), "error_code" => 500];

    //     }
    // }

}
