<?php

class Entreprise {
    private $conn;
    private $table_name = "entreprises";

    public $id;
    public $racineId;
    public $nom;
    public $ville;
    public $pays;
    public $email;
    public $adresse;
    public $ice;
    public $createdAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read($search = "", $offset = 0, $limit = 10, $racineId = null) {
        $query = "SELECT * FROM " . $this->table_name;
        $conditions = [];
        $params = [];

        if ($search) {
            $conditions[] = "(nom LIKE :search OR ville LIKE :search OR adresse LIKE :search OR email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($racineId) {
            $conditions[] = "racineId = :racineId";
            $params[':racineId'] = $racineId;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY createdAt DESC LIMIT :offset, :limit";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
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
            $this->racineId = $row['racineId'];
            $this->nom = $row['nom'];
            $this->ville = $row['ville'];
            $this->pays = $row['pays'];
            $this->email = $row['email'];
            $this->adresse = $row['adresse'];
            $this->ice = $row['ice'];
            $this->createdAt = $row['createdAt'];
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET id=:id, racineId=:racineId, nom=:nom, ville=:ville, pays=:pays, 
                    email=:email, adresse=:adresse, ice=:ice";
        $stmt = $this->conn->prepare($query);
        
        $this->id = uniqid('ent_');
        $this->sanitize();

        // Default values for existing columns
        $this->ice = $this->ice ?? "";
        $this->ville = $this->ville ?? "";
        $this->pays = $this->pays ?? "Maroc";
        $this->email = $this->email ?? "";
        $this->adresse = $this->adresse ?? "";

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":racineId", $this->racineId);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":pays", $this->pays);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ice", $this->ice);

        try {
            if($stmt->execute()) return true;
        } catch (PDOException $e) {
            error_log("SQL Error in Entreprise::create: " . $e->getMessage());
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET racineId=:racineId, nom=:nom, ville=:ville, pays=:pays, 
                    email=:email, adresse=:adresse, ice=:ice 
                WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        $this->sanitize();

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":racineId", $this->racineId);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":pays", $this->pays);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ice", $this->ice);

        try {
            if($stmt->execute()) return true;
        } catch (PDOException $e) {
            error_log("SQL Error in Entreprise::update: " . $e->getMessage());
        }
        return false;
    }

    private function sanitize() {
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->racineId = htmlspecialchars(strip_tags($this->racineId));
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->ville = htmlspecialchars(strip_tags($this->ville));
        $this->pays = htmlspecialchars(strip_tags($this->pays));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->ice = htmlspecialchars(strip_tags($this->ice));
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if($stmt->execute()) return true;
        return false;
    }
}
