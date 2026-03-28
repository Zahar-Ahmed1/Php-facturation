<?php

class Client {
    private $conn;
    private $table_name = "clients";

    public $id = "";
    public $nom = "";
    public $email = "";
    public $telephone = "";
    public $adresse = "";
    public $ville = "";
    public $codePostal = "";
    public $pays = "";
    public $siret = "";
    public $tva = "";
    public $produitsVedette = null;
    public $createdAt = "";

    private $error;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getError() {
        return $this->error;
    }

    public function read($search = "", $offset = 0, $limit = 10) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($search) {
            $query .= " WHERE nom LIKE :search OR email LIKE :search OR ville LIKE :search";
        }
        $query .= " ORDER BY createdAt DESC LIMIT :offset, :limit";

        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $searchParam = "%$search%";
            $stmt->bindParam(":search", $searchParam);
        }
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            foreach ($row as $key => $value) {
                if ($key === 'produitsVedette' && $value) {
                    $this->$key = json_decode($value, true);
                } else {
                    $this->$key = $value;
                }
            }
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET id=:id, nom=:nom, email=:email, telephone=:telephone, adresse=:adresse, 
                    ville=:ville, codePostal=:codePostal, pays=:pays, siret=:siret, tva=:tva, produitsVedette=:produitsVedette";
        
        $stmt = $this->conn->prepare($query);

        $this->id = uniqid('cli_');
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = $this->telephone ? htmlspecialchars(strip_tags($this->telephone)) : "";
        $this->adresse = $this->adresse ? htmlspecialchars(strip_tags($this->adresse)) : "";
        $this->ville = $this->ville ? htmlspecialchars(strip_tags($this->ville)) : "";
        $this->codePostal = $this->codePostal ? htmlspecialchars(strip_tags($this->codePostal)) : "";
        $this->pays = $this->pays ? htmlspecialchars(strip_tags($this->pays)) : "";
        $this->siret = $this->siret ? htmlspecialchars(strip_tags($this->siret)) : "";
        $this->tva = $this->tva ? htmlspecialchars(strip_tags($this->tva)) : "";
        
        // Handle produitsVedette as JSON
        $produitsVedetteStr = $this->produitsVedette ? json_encode($this->produitsVedette) : null;

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":codePostal", $this->codePostal);
        $stmt->bindParam(":pays", $this->pays);
        $stmt->bindParam(":siret", $this->siret);
        $stmt->bindParam(":tva", $this->tva);
        $stmt->bindParam(":produitsVedette", $produitsVedetteStr);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        
        $this->error = $stmt->errorInfo();
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET nom=:nom, email=:email, telephone=:telephone, adresse=:adresse, 
                    ville=:ville, codePostal=:codePostal, pays=:pays, siret=:siret, tva=:tva, produitsVedette=:produitsVedette
                WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = $this->telephone ? htmlspecialchars(strip_tags($this->telephone)) : "";
        $this->adresse = $this->adresse ? htmlspecialchars(strip_tags($this->adresse)) : "";
        $this->ville = $this->ville ? htmlspecialchars(strip_tags($this->ville)) : "";
        $this->codePostal = $this->codePostal ? htmlspecialchars(strip_tags($this->codePostal)) : "";
        $this->pays = $this->pays ? htmlspecialchars(strip_tags($this->pays)) : "";
        $this->siret = $this->siret ? htmlspecialchars(strip_tags($this->siret)) : "";
        $this->tva = $this->tva ? htmlspecialchars(strip_tags($this->tva)) : "";
        
        // Handle produitsVedette as JSON
        $produitsVedetteStr = $this->produitsVedette ? json_encode($this->produitsVedette) : null;

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":codePostal", $this->codePostal);
        $stmt->bindParam(":pays", $this->pays);
        $stmt->bindParam(":siret", $this->siret);
        $stmt->bindParam(":tva", $this->tva);
        $stmt->bindParam(":produitsVedette", $produitsVedetteStr);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        
        $this->error = $stmt->errorInfo();
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        $this->error = $stmt->errorInfo();
        return false;
    }
}
