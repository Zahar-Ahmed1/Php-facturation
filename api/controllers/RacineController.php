<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Racine.php';

class RacineController extends BaseController {

    public function getAll() {
        $this->authenticate();
        $racine = new Racine($this->conn);
        $search = isset($_GET['search']) ? $_GET['search'] : "";
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
        $offset = ($page - 1) * $limit;

        $stmt = $racine->read($search, $offset, $limit);
        $racines_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $racines_arr[] = $row;
        }
        $this->sendResponse($racines_arr);
    }

    public function getOne($id) {
        $this->authenticate();
        $racine = new Racine($this->conn);
        $racine->id = $id;
        if($racine->readOne()) {
            $this->sendResponse((array)$racine);
        } else {
            $this->sendError("Racine introuvable.", 404);
        }
    }

    public function create() {
        $this->authenticate();
        $data = $this->getPostData();
        if(!empty($data->nom)) {
            $racine = new Racine($this->conn);
            $racine->nom = $data->nom;
            $racine->ville = $data->ville ?? "";
            $racine->adresse = $data->adresse ?? "";
            $racine->ice = $data->ice ?? "";
            if($racine->create()) {
                $this->sendResponse(["message" => "Racine créée.", "id" => $racine->id], 201);
            } else {
                $this->sendError("Erreur lors de la création.", 500);
            }
        } else {
            $this->sendError("Données incomplètes (nom requis).", 400);
        }
    }

    public function update($id) {
        $this->authenticate();
        $data = $this->getPostData();
        $racine = new Racine($this->conn);
        $racine->id = $id;
        if(!$racine->readOne()) $this->sendError("Racine introuvable.", 404);
        
        $racine->nom = $data->nom ?? $racine->nom;
        $racine->ville = $data->ville ?? $racine->ville;
        $racine->adresse = $data->adresse ?? $racine->adresse;
        $racine->ice = $data->ice ?? $racine->ice;
        if($racine->update()) {
            $this->sendResponse(["message" => "Racine mise à jour."]);
        } else {
            $this->sendError("Erreur lors de la mise à jour.", 500);
        }
    }

    public function delete($id) {
        $this->authenticate();
        $racine = new Racine($this->conn);
        $racine->id = $id;
        if($racine->delete()) {
            $this->sendResponse(["message" => "Racine supprimée."]);
        } else {
            $this->sendError("Erreur lors de la suppression.", 500);
        }
    }
}
