<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = 'root';
    private $database = 'webhr';
    private $conn;

    public function __construct() {
        $this->conn = mysqli_connect($this->host, $this->username, $this->password, $this->database);

        if (!$this->conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function close() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
}
?>
