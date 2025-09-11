<?php
// config/database.php

class Database {
    private $host = 'localhost';
    private $db_name = 'uip_registration';
    private $username = 'UIPdatabase';
    private $password = 'UIPincorporated2021';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }

    // Optional: Explicitly close the connection
    public function closeConnection() {
        $this->conn = null;
    }
}
?>