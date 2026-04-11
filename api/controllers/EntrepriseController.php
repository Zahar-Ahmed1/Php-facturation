<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Entreprise.php';

class EntrepriseController extends BaseController {

    public function getAll() {
        $this->authenticate();
        $entreprise = new Entreprise($this->conn);
        $search = isset($_GET['search']) ? $_GET['search'] : "";
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
        $racineId = isset($_GET['racineId']) ? $_GET['racineId'] : null;
        $offset = ($page - 1) * $limit;

        $stmt = $entreprise->read($search, $offset, $limit, $racineId);
        $entreprises_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entreprises_arr[] = $row;
        }
        $this->sendResponse($entreprises_arr);
    }

    public function getOne($id) {
        $this->authenticate();
        $entreprise = new Entreprise($this->conn);
        $entreprise->id = $id;
        if($entreprise->readOne()) {
            $this->sendResponse((array)$entreprise);
        } else {
            $this->sendError("Entreprise introuvable.", 404);
        }
    }

    public function create() {
        $this->authenticate();
        $data = $this->getPostData();
        
        if(!empty($data->nom) && !empty($data->racineId)) {
            $entreprise = new Entreprise($this->conn);
            
            // Mapper uniquement les propriétés qui existent dans le modèle (et la DB)
            $entreprise->racineId = $data->racineId;
            $entreprise->nom = $data->nom;
            $entreprise->ville = $data->ville ?? "";
            $entreprise->pays = $data->pays ?? "Maroc";
            $entreprise->email = $data->email ?? "";
            $entreprise->adresse = $data->adresse ?? "";
            $entreprise->ice = $data->ice ?? "";
            
            if($entreprise->create()) {
                $this->sendResponse(["message" => "Entreprise créée.", "id" => $entreprise->id], 201);
            } else {
                $this->sendError("Erreur lors de la création en base de données.", 500);
            }
        } else {
            $this->sendError("Données incomplètes (nom et racineId requis).", 400);
        }
    }

    public function update($id) {
        $this->authenticate();
        $data = $this->getPostData();
        $entreprise = new Entreprise($this->conn);
        $entreprise->id = $id;
        if(!$entreprise->readOne()) $this->sendError("Entreprise introuvable.", 404);
        
        $entreprise->racineId = $data->racineId ?? $entreprise->racineId;
        $entreprise->nom = $data->nom ?? $entreprise->nom;
        $entreprise->ville = $data->ville ?? $entreprise->ville;
        $entreprise->pays = $data->pays ?? $entreprise->pays;
        $entreprise->email = $data->email ?? $entreprise->email;
        $entreprise->adresse = $data->adresse ?? $entreprise->adresse;
        $entreprise->ice = $data->ice ?? $entreprise->ice;

        if($entreprise->update()) {
            $this->sendResponse(["message" => "Entreprise mise à jour."]);
        } else {
            $this->sendError("Erreur lors de la mise à jour.", 500);
        }
    }

    public function delete($id) {
        $this->authenticate();
        $entreprise = new Entreprise($this->conn);
        $entreprise->id = $id;
        if($entreprise->delete()) {
            $this->sendResponse(["message" => "Entreprise supprimée."]);
        } else {
            $this->sendError("Erreur lors de la suppression.", 500);
        }
    }
}
