<?php

class Racine {
    private $conn;
    private $table_name = "racines";

    public $id;
    public $nom;
    public $ville;
    public $adresse;
    public $ice;
    public $if_group;
    public $createdAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read($search = "", $offset = 0, $limit = 10) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($search) {
            $query .= " WHERE nom LIKE :search OR ville LIKE :search";
        }
        $query .= " ORDER BY createdAt DESC LIMIT :offset, :limit";

        $stmt = $this->conn->prepare($query);
        if ($search) {
            $search = "%{$search}%";
            $stmt->bindParam(':search', $search);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->nom = $row['nom'];
            $this->ville = $row['ville'];
            $this->adresse = $row['adresse'];
            $this->ice = $row['ice'];
            $this->if_group = isset($row['if_group']) ? $row['if_group'] : '';
            $this->createdAt = $row['createdAt'];
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET id=:id, nom=:nom, ville=:ville, adresse=:adresse, ice=:ice, if_group=:if_group";
        $stmt = $this->conn->prepare($query);
        $this->id = uniqid('rac_');
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->ville = htmlspecialchars(strip_tags($this->ville));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->ice = htmlspecialchars(strip_tags($this->ice));
        $this->if_group = htmlspecialchars(strip_tags($this->if_group));
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ice", $this->ice);
        $stmt->bindParam(":if_group", $this->if_group);
        if($stmt->execute()) return true;
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nom=:nom, ville=:ville, adresse=:adresse, ice=:ice, if_group=:if_group WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->ville = htmlspecialchars(strip_tags($this->ville));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->ice = htmlspecialchars(strip_tags($this->ice));
        $this->if_group = htmlspecialchars(strip_tags($this->if_group));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ice", $this->ice);
        $stmt->bindParam(":if_group", $this->if_group);
        $stmt->bindParam(":id", $this->id);
        if($stmt->execute()) return true;
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if($stmt->execute()) return true;
        return false;
    }
}
