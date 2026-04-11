<?php

require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $c = fp_config();
        $this->host = $c['db_host'];
        $this->db_name = $c['db_name'];
        $this->username = $c['db_user'];
        $this->password = $c['db_pass'];
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            throw new Exception("Erreur de connexion : " . $exception->getMessage());
        }

        return $this->conn;
    }
}
