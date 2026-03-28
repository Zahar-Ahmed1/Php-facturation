<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Client.php';

class ClientController extends BaseController {

    public function getAll() {
        $this->authenticate();
        $client = new Client($this->conn);
        
        $search = isset($_GET['search']) ? $_GET['search'] : "";
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $stmt = $client->read($search, $offset, $limit);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $clients_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($row['produitsVedette']) && $row['produitsVedette']) {
                    $row['produitsVedette'] = json_decode($row['produitsVedette'], true);
                }
                array_push($clients_arr, $row);
            }
            $this->sendResponse([
                "data" => $clients_arr,
                "page" => $page,
                "limit" => $limit
            ]);
        } else {
            $this->sendResponse(["data" => []]);
        }
    }

    public function getOne($id) {
        $this->authenticate();
        $client = new Client($this->conn);
        $client->id = $id;

        if ($client->readOne()) {
            $this->sendResponse($client);
        } else {
            $this->sendError("Client non trouvé.", 404);
        }
    }

    public function create() {
        $this->authenticate();
        $data = $this->getPostData();
        $client = new Client($this->conn);

        foreach ($data as $key => $value) {
            if (property_exists($client, $key)) {
                $client->$key = $value;
            }
        }

        // Default products vedette if empty
        if (!$client->produitsVedette) {
            $client->produitsVedette = [
                [
                    'produitId' => 'prod_1',
                    'designation' => 'Produit Vedette (Auto)',
                    'quantite' => 1,
                    'prixUnitaire' => 100.00,
                    'tva' => 20.0,
                    'remise' => 0,
                    'totalHT' => 100.00,
                    'totalTTC' => 120.00
                ]
            ];
        }

        if ($client->create()) {
            $this->sendResponse(["message" => "Client créé avec succès.", "id" => $client->id], 201);
        } else {
            $this->sendError("Erreur lors de la création du client.", 500, $client->getError());
        }
    }

    public function update($id) {
        $this->authenticate();
        $data = $this->getPostData();
        $client = new Client($this->conn);
        $client->id = $id;

        if (!$client->readOne()) {
            $this->sendError("Client non trouvé.", 404);
        }

        foreach ($data as $key => $value) {
            if (property_exists($client, $key)) {
                $client->$key = $value;
            }
        }

        if ($client->update()) {
            $this->sendResponse(["message" => "Client mis à jour avec succès."]);
        } else {
            $this->sendError("Erreur lors de la mise à jour du client.", 500, $client->getError());
        }
    }

    public function delete($id) {
        $this->authenticate();
        $client = new Client($this->conn);
        $client->id = $id;

        if (!$client->readOne()) {
            $this->sendError("Client non trouvé.", 404);
        }

        if ($client->delete()) {
            $this->sendResponse(["message" => "Client supprimé avec succès."]);
        } else {
            $this->sendError("Erreur lors de la suppression du client.", 500, $client->getError());
        }
    }
}
