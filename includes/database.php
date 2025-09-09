<?php
// includes/database.php
require_once __DIR__ . '/config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Use charset in DSN and set error mode to exception
            $this->conn = new PDO(
              "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
              $this->username,
              $this->password,
              [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            // For dev only. In production, log error instead of echo
            echo "Connection error: " . $exception->getMessage();
            exit;
        }
        return $this->conn;
    }
}
?>
