<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Produit.php';

class ProduitController extends BaseController {

    public function getAll() {
        $this->authenticate();
        $produit = new Produit($this->conn);
        
        $search = isset($_GET['search']) ? $_GET['search'] : "";
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $stmt = $produit->read($search, $offset, $limit);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $produits_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['prixHT'] = (float)$row['prixHT'];
                $row['tva'] = (float)$row['tva'];
                $row['stock'] = (int)$row['stock'];
                $row['stockMin'] = (int)$row['stockMin'];
                array_push($produits_arr, $row);
            }
            $this->sendResponse([
                "data" => $produits_arr,
                "page" => $page,
                "limit" => $limit
            ]);
        } else {
            $this->sendResponse(["data" => []]);
        }
    }

    public function getOne($id) {
        $this->authenticate();
        $produit = new Produit($this->conn);
        $produit->id = $id;

        if ($produit->readOne()) {
            $this->sendResponse($produit);
        } else {
            $this->sendError("Produit non trouvé.", 404);
        }
    }

    public function create() {
        $this->authenticate();
        $data = $this->getPostData();
        $produit = new Produit($this->conn);

        foreach ($data as $key => $value) {
            if (property_exists($produit, $key)) {
                $produit->$key = $value;
            }
        }

        if ($produit->create()) {
            $this->sendResponse(["message" => "Produit créé avec succès.", "id" => $produit->id], 201);
        } else {
            $this->sendError("Erreur lors de la création du produit.", 500, $produit->getError());
        }
    }

    public function update($id) {
        $this->authenticate();
        $data = $this->getPostData();
        $produit = new Produit($this->conn);
        $produit->id = $id;

        if (!$produit->readOne()) {
            $this->sendError("Produit non trouvé.", 404);
        }

        foreach ($data as $key => $value) {
            if (property_exists($produit, $key)) {
                $produit->$key = $value;
            }
        }

        if ($produit->update()) {
            $this->sendResponse(["message" => "Produit mis à jour avec succès."]);
        } else {
            $this->sendError("Erreur lors de la mise à jour du produit.", 500, $produit->getError());
        }
    }

    public function delete($id) {
        $this->authenticate();
        $produit = new Produit($this->conn);
        $produit->id = $id;

        if (!$produit->readOne()) {
            $this->sendError("Produit non trouvé.", 404);
        }

        if ($produit->delete()) {
            $this->sendResponse(["message" => "Produit supprimé avec succès."]);
        } else {
            $this->sendError("Erreur lors de la suppression du produit.", 500, $produit->getError());
        }
    }
}
