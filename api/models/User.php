<?php

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $nom;
    public $email;
    public $password;
    public $mustChangePassword;
    public $createdAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nom=:nom, email=:email, password=:password";
        $stmt = $this->conn->prepare($query);

        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT id, nom, email, password, mustChangePassword FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $cleanEmail = trim(strip_tags($this->email));
        $stmt->bindParam(":email", $cleanEmail);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->mustChangePassword = (bool)$row['mustChangePassword'];
            return true;
        }
        return false;
    }
}
