<?php

class Produit {
    private $conn;
    private $table_name = "produits";

    public $id = "";
    public $reference = "";
    public $nom = "";
    public $description = "";
    public $prixHT = 0.0;
    public $tva = 20.0;
    public $unite = "";
    public $stock = 0;
    public $stockMin = 0;
    public $categorie = "";

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
            $query .= " WHERE nom LIKE :search OR reference LIKE :search OR categorie LIKE :search";
        }
        $query .= " ORDER BY reference ASC LIMIT :offset, :limit";

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
            $this->id = $row['id'];
            $this->reference = $row['reference'];
            $this->nom = $row['nom'];
            $this->description = $row['description'];
            $this->prixHT = (float)$row['prixHT'];
            $this->tva = (float)$row['tva'];
            $this->unite = $row['unite'];
            $this->stock = (int)$row['stock'];
            $this->stockMin = (int)$row['stockMin'];
            $this->categorie = $row['categorie'];
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET id=:id, reference=:reference, nom=:nom, description=:description, 
                    prixHT=:prixHT, tva=:tva, unite=:unite, stock=:stock, stockMin=:stockMin, categorie=:categorie";
        
        $stmt = $this->conn->prepare($query);

        $this->id = uniqid('prod_');
        
        // Nettoyage des données
        $this->reference = htmlspecialchars(strip_tags($this->reference));
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->description = $this->description ? htmlspecialchars(strip_tags($this->description)) : "";
        $this->unite = $this->unite ? htmlspecialchars(strip_tags($this->unite)) : "";
        $this->categorie = $this->categorie ? htmlspecialchars(strip_tags($this->categorie)) : "";
        
        // Conversion explicite pour les types numériques
        $this->prixHT = (float)$this->prixHT;
        $this->tva = (float)$this->tva;
        $this->stock = (int)$this->stock;
        $this->stockMin = (int)$this->stockMin;

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":reference", $this->reference);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":prixHT", $this->prixHT);
        $stmt->bindParam(":tva", $this->tva);
        $stmt->bindParam(":unite", $this->unite);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":stockMin", $this->stockMin);
        $stmt->bindParam(":categorie", $this->categorie);

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
                SET reference=:reference, nom=:nom, description=:description, 
                    prixHT=:prixHT, tva=:tva, unite=:unite, stock=:stock, stockMin=:stockMin, categorie=:categorie
                WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->reference = htmlspecialchars(strip_tags($this->reference));
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->description = $this->description ? htmlspecialchars(strip_tags($this->description)) : "";
        $this->unite = $this->unite ? htmlspecialchars(strip_tags($this->unite)) : "";
        $this->categorie = $this->categorie ? htmlspecialchars(strip_tags($this->categorie)) : "";
        
        // Conversion explicite pour les types numériques
        $this->prixHT = (float)$this->prixHT;
        $this->tva = (float)$this->tva;
        $this->stock = (int)$this->stock;
        $this->stockMin = (int)$this->stockMin;

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":reference", $this->reference);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":prixHT", $this->prixHT);
        $stmt->bindParam(":tva", $this->tva);
        $stmt->bindParam(":unite", $this->unite);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":stockMin", $this->stockMin);
        $stmt->bindParam(":categorie", $this->categorie);

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
