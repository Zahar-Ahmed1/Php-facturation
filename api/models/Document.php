<?php

class Document {
    private $conn;
    private $table_name = "documents";

    public $id = "";
    public $numero = "";
    public $type = "";
    public $clientId = "";
    public $dateCreation = "";
    public $dateEcheance = "";
    public $statut = "brouillon";
    public $totalHT = 0.0;
    public $totalTVA = 0.0;
    public $totalTTC = 0.0;
    public $notes = "";
    public $conditions = "";
    public $documentParentId = null;
    public $lignes = [];

    private $error;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getError() {
        return $this->error;
    }

    public function read($type = null, $clientId = null, $offset = 0, $limit = 10) {
        $query = "SELECT d.*, c.nom as clientNom FROM " . $this->table_name . " d 
                LEFT JOIN clients c ON d.clientId = c.id WHERE 1=1";
        
        if ($type) $query .= " AND d.type = :type";
        if ($clientId) $query .= " AND d.clientId = :clientId";
        
        $query .= " ORDER BY d.dateCreation DESC, d.numero DESC LIMIT :offset, :limit";

        $stmt = $this->conn->prepare($query);
        
        if ($type) $stmt->bindParam(":type", $type);
        if ($clientId) $stmt->bindParam(":clientId", $clientId);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT d.*, c.nom as clientNom FROM " . $this->table_name . " d 
                LEFT JOIN clients c ON d.clientId = c.id 
                WHERE d.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            // Casting numeric values
            $this->totalHT = (float)$this->totalHT;
            $this->totalTVA = (float)$this->totalTVA;
            $this->totalTTC = (float)$this->totalTTC;
            
            return true;
        }
        return false;
    }

    public function readLignes() {
        $query = "SELECT * FROM lignes_documents WHERE documentId = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $this->lignes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['quantite'] = (float)$row['quantite'];
            $row['prixUnitaire'] = (float)$row['prixUnitaire'];
            $row['tva'] = (float)$row['tva'];
            $row['remise'] = (float)$row['remise'];
            $row['totalHT'] = (float)$row['totalHT'];
            $row['totalTTC'] = (float)$row['totalTTC'];
            $this->lignes[] = $row;
        }
        return $this->lignes;
    }

    public function create() {
        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    SET id=:id, numero=:numero, type=:type, clientId=:clientId, 
                        dateCreation=:dateCreation, dateEcheance=:dateEcheance, statut=:statut, 
                        totalHT=:totalHT, totalTVA=:totalTVA, totalTTC=:totalTTC, 
                        notes=:notes, conditions=:conditions, documentParentId=:documentParentId";
            
            $stmt = $this->conn->prepare($query);

            if (!$this->id) $this->id = uniqid('doc_');
            
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":numero", $this->numero);
            $stmt->bindParam(":type", $this->type);
            $stmt->bindParam(":clientId", $this->clientId);
            $stmt->bindParam(":dateCreation", $this->dateCreation);
            $stmt->bindParam(":dateEcheance", $this->dateEcheance);
            $stmt->bindParam(":statut", $this->statut);
            $stmt->bindParam(":totalHT", $this->totalHT);
            $stmt->bindParam(":totalTVA", $this->totalTVA);
            $stmt->bindParam(":totalTTC", $this->totalTTC);
            $stmt->bindParam(":notes", $this->notes);
            $stmt->bindParam(":conditions", $this->conditions);
            $stmt->bindParam(":documentParentId", $this->documentParentId);

            $stmt->execute();

            foreach ($this->lignes as $ligne) {
                $this->createLigne($ligne);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function createLigne($ligne) {
        $ligne = (array)$ligne;
        
        $query = "INSERT INTO lignes_documents 
                SET documentId=:documentId, produitId=:produitId, designation=:designation, 
                    quantite=:quantite, prixUnitaire=:prixUnitaire, tva=:tva, 
                    remise=:remise, totalHT=:totalHT, totalTTC=:totalTTC";
        
        $stmt = $this->conn->prepare($query);
        
        $produitId = isset($ligne['produitId']) ? $ligne['produitId'] : null;
        $remise = isset($ligne['remise']) ? (float)$ligne['remise'] : 0.0;
        
        $stmt->bindValue(":documentId", $this->id);
        $stmt->bindValue(":produitId", $produitId);
        $stmt->bindValue(":designation", $ligne['designation']);
        $stmt->bindValue(":quantite", (float)$ligne['quantite']);
        $stmt->bindValue(":prixUnitaire", (float)$ligne['prixUnitaire']);
        $stmt->bindValue(":tva", (float)$ligne['tva']);
        $stmt->bindValue(":remise", $remise);
        $stmt->bindValue(":totalHT", (float)$ligne['totalHT']);
        $stmt->bindValue(":totalTTC", (float)$ligne['totalTTC']);

        $stmt->execute();
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
