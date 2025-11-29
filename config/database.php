<?php
class Database 
{
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "agri_db";
    private $conn;

    public function connect()
    {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->user, $this->pass);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database Error: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}