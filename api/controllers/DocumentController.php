<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Document.php';

class DocumentController extends BaseController {

    public function getAll() {
        $this->authenticate();
        $document = new Document($this->conn);
        
        $type = isset($_GET['type']) ? $_GET['type'] : null;
        $clientId = isset($_GET['clientId']) ? $_GET['clientId'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $stmt = $document->read($type, $clientId, $offset, $limit);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $docs_arr = array();
            $documentInstance = new Document($this->conn);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['totalHT'] = (float)$row['totalHT'];
                $row['totalTVA'] = (float)$row['totalTVA'];
                $row['totalTTC'] = (float)$row['totalTTC'];
                
                // Charger les lignes pour chaque document afin qu'elles soient disponibles dans le PDF
                $documentInstance->id = $row['id'];
                $row['lignes'] = $documentInstance->readLignes();
                
                array_push($docs_arr, $row);
            }
            $this->sendResponse([
                "data" => $docs_arr,
                "page" => $page,
                "limit" => $limit
            ]);
        } else {
            $this->sendResponse(["data" => []]);
        }
    }

    public function getOne($id) {
        $this->authenticate();
        $document = new Document($this->conn);
        $document->id = $id;

        if ($document->readOne()) {
            $response = (array)$document;
            $response['lignes'] = $document->readLignes();
            
            // On s'assure que les relations sont chargées
            if (isset($response['clientId'])) {
                require_once __DIR__ . '/../models/Client.php';
                $client = new Client($this->conn);
                $client->id = $response['clientId'];
                if ($client->readOne()) {
                    $response['client'] = (array)$client;
                }
            }
            
            $this->sendResponse($response);
        } else {
            $this->sendError("Document non trouvé.", 404);
        }
    }

    public function create() {
        $this->authenticate();
        $data = $this->getPostData();
        $document = new Document($this->conn);

        $this->mapDataToDocument($data, $document);
        $this->calculateTotals($document);

        if ($document->create()) {
            $this->sendResponse(["message" => "Document créé avec succès.", "id" => $document->id], 201);
        } else {
            $this->sendError("Erreur lors de la création du document.", 500, $document->getError());
        }
    }

    public function update($id) {
        $this->authenticate();
        $data = $this->getPostData();
        $document = new Document($this->conn);
        $document->id = $id;

        if (!$document->readOne()) {
            $this->sendError("Document non trouvé.", 404);
        }

        // Supprimer les anciennes lignes avant de recréer
        $query = "DELETE FROM lignes_documents WHERE documentId = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        $this->mapDataToDocument($data, $document);
        $this->calculateTotals($document);

        // Mettre à jour le document principal
        $query = "UPDATE documents 
                SET numero=:numero, type=:type, clientId=:clientId, 
                    dateCreation=:dateCreation, dateEcheance=:dateEcheance, statut=:statut, 
                    totalHT=:totalHT, totalTVA=:totalTVA, totalTTC=:totalTTC, 
                    notes=:notes, conditions=:conditions, documentParentId=:documentParentId
                WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $document->id);
        $stmt->bindParam(":numero", $document->numero);
        $stmt->bindParam(":type", $document->type);
        $stmt->bindParam(":clientId", $document->clientId);
        $stmt->bindParam(":dateCreation", $document->dateCreation);
        $stmt->bindParam(":dateEcheance", $document->dateEcheance);
        $stmt->bindParam(":statut", $document->statut);
        $stmt->bindParam(":totalHT", $document->totalHT);
        $stmt->bindParam(":totalTVA", $document->totalTVA);
        $stmt->bindParam(":totalTTC", $document->totalTTC);
        $stmt->bindParam(":notes", $document->notes);
        $stmt->bindParam(":conditions", $document->conditions);
        $stmt->bindParam(":documentParentId", $document->documentParentId);

        if ($stmt->execute()) {
            // Créer les nouvelles lignes
            foreach ($document->lignes as $ligne) {
                $document->createLigne($ligne);
            }
            $this->sendResponse(["message" => "Document mis à jour avec succès."]);
        } else {
            $this->sendError("Erreur lors de la mise à jour du document.", 500, $stmt->errorInfo());
        }
    }

    public function delete($id) {
        $this->authenticate();
        $document = new Document($this->conn);
        $document->id = $id;

        if (!$document->readOne()) {
            $this->sendError("Document non trouvé.", 404);
        }

        if ($document->delete()) {
            $this->sendResponse(["message" => "Document supprimé avec succès."]);
        } else {
            $this->sendError("Erreur lors de la suppression du document.", 500, $document->getError());
        }
    }

    public function convertir($id) {
        $this->authenticate();
        $sourceDoc = new Document($this->conn);
        $sourceDoc->id = $id;

        if (!$sourceDoc->readOne()) {
            $this->sendError("Document source non trouvé.", 404);
        }

        // IMPORTANT: On doit charger les lignes du document source avant de convertir
        $sourceDoc->readLignes();

        $targetType = isset($_GET['to']) ? $_GET['to'] : 'facture';
        
        // Validation du type cible
        $validTypes = ['devis', 'facture', 'bon-livraison', 'bon-commande'];
        if (!in_array($targetType, $validTypes)) {
            $this->sendError("Type de document cible invalide.");
        }

        $newDoc = new Document($this->conn);
        
        // Copie des données de base
        $newDoc->clientId = $sourceDoc->clientId;
        $newDoc->notes = $sourceDoc->notes;
        $newDoc->conditions = $sourceDoc->conditions;
        $newDoc->documentParentId = $sourceDoc->id;
        
        // Copie profonde des lignes pour éviter les problèmes de référence
        $newDoc->lignes = [];
        foreach ($sourceDoc->lignes as $ligne) {
            $newDoc->lignes[] = (array)$ligne;
        }

        // Nouvelles métadonnées
        $newDoc->type = $targetType;
        
        // Génération d'un numéro intelligent
        $prefixMap = [
            'devis' => 'DEV',
            'facture' => 'FAC',
            'bon-livraison' => 'BL',
            'bon-commande' => 'BC'
        ];
        $prefix = $prefixMap[$targetType];
        $newDoc->numero = $prefix . '-' . date('Ymd-His');
        
        $newDoc->dateCreation = date('Y-m-d');
        $newDoc->dateEcheance = date('Y-m-d', strtotime('+30 days'));
        $newDoc->statut = 'brouillon';

        // Recalculer les totaux pour être sûr que tout est correct
        $this->calculateTotals($newDoc);

        if ($newDoc->create()) {
            // Mettre à jour le statut du document parent si c'est un devis vers facture par exemple
            if ($sourceDoc->type === 'devis' && $targetType === 'facture') {
                $query = "UPDATE documents SET statut='converti' WHERE id=?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$sourceDoc->id]);
            }

            $this->sendResponse([
                "message" => "Document transformé avec succès en " . $targetType . ".",
                "id" => $newDoc->id,
                "numero" => $newDoc->numero
            ], 201);
        } else {
            $this->sendError("Erreur lors de la transformation du document.", 500, $newDoc->getError());
        }
    }

    public function extraireIA() {
        try {
            $this->authenticate();

            if (!isset($_FILES['file'])) {
                $this->sendError("Aucun fichier fourni.", 400);
            }

            $file = $_FILES['file'];
            $tmp_path = $file['tmp_name'];
            $mime_type = $file['type'];
            
            error_log("IA - Fichier reçu : " . $file['name'] . " ($mime_type)");

            if (!file_exists($tmp_path)) {
                $this->sendError("Fichier temporaire introuvable.", 500);
            }

            $file_content = file_get_contents($tmp_path);
            if ($file_content === false) {
                $this->sendError("Impossible de lire le fichier.", 500);
            }

            // Logique d'extraction Gemini directement intégrée (sans require_once)
            $api_key = "AIzaSyAEBTrpCTzpoPvsVtmA923_oBXjGSbGLAw";
            $model = "gemini-2.0-flash"; 
            $url = "https://generativelanguage.googleapis.com/v1/models/" . $model . ":generateContent?key=" . $api_key;

            $file_data = base64_encode($file_content);

            $prompt = "Tu es un assistant comptable expert. Analyse cette image de facture/devis et extrait les informations suivantes au format JSON uniquement.
            Recherche spécifiquement les colonnes DESCRIPTION, QTY, P/U, TVA, TOTAL TTC.
            Structure attendue :
            {
                \"numero\": \"numéro du document\",
                \"dateCreation\": \"YYYY-MM-DD\",
                \"totalHT\": 0.00,
                \"totalTVA\": 0.00,
                \"totalTTC\": 0.00,
                \"lignes\": [
                    {
                        \"designation\": \"nom complet du produit\",
                        \"quantite\": 0,
                        \"prixUnitaire\": 0.00,
                        \"tva\": 20.0,
                        \"totalHT\": 0.00,
                        \"totalTTC\": 0.00
                    }
                ]
            }
            Réponds uniquement avec le JSON valide.";

            $data = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt],
                            [
                                "inline_data" => [
                                    "mime_type" => $mime_type,
                                    "data" => $file_data
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 429) {
                $this->sendError("Quota IA dépassé.", 429);
            }

            if ($http_code !== 200) {
                $error_details = json_decode($response, true);
                $this->sendError("Erreur Gemini ($http_code)", 500, $error_details);
            }

            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $json_text = $result['candidates'][0]['content']['parts'][0]['text'];
                
                $startPos = strpos($json_text, '{');
                $endPos = strrpos($json_text, '}');
                if ($startPos !== false && $endPos !== false) {
                    $json_text = substr($json_text, $startPos, $endPos - $startPos + 1);
                }
                
                $parsed = json_decode($json_text, true);
                
                if ($parsed) {
                    $this->sendResponse([
                        "message" => "Données extraites avec succès.",
                        "data" => $parsed
                    ]);
                } else {
                    $this->sendError("Format JSON invalide.", 500);
                }
            } else {
                $this->sendError("Aucune donnée renvoyée par l'IA.", 500);
            }
        } catch (Exception $e) {
            $this->sendError("Erreur interne : " . $e->getMessage(), 500);
        }
    }

    private function mapDataToDocument($data, $document) {
        foreach ($data as $key => $value) {
            if ($key != 'lignes' && property_exists($document, $key)) {
                $document->$key = $value;
            }
        }
        if (isset($data->lignes)) {
            $document->lignes = $data->lignes;
        }
    }

    private function calculateTotals($document) {
        $totalHT = 0;
        $totalTVA = 0;
        $updatedLignes = [];

        foreach ($document->lignes as $ligne) {
            $l = (array)$ligne;
            
            $quantite = (float)$l['quantite'];
            $prixUnitaire = (float)$l['prixUnitaire'];
            $tva = (float)$l['tva'];
            $remise = isset($l['remise']) ? (float)$l['remise'] : 0.0;

            $ligneHT = $quantite * $prixUnitaire;
            if ($remise > 0) {
                $ligneHT -= ($ligneHT * ($remise / 100));
            }
            $ligneTVA = $ligneHT * ($tva / 100);
            
            $l['totalHT'] = round($ligneHT, 2);
            $l['totalTTC'] = round($ligneHT + $ligneTVA, 2);
            
            $totalHT += $ligneHT;
            $totalTVA += $ligneTVA;
            
            $updatedLignes[] = $l;
        }

        $document->lignes = $updatedLignes;
        $document->totalHT = round($totalHT, 2);
        $document->totalTVA = round($totalTVA, 2);
        $document->totalTTC = round($totalHT + $totalTVA, 2);
    }
}
