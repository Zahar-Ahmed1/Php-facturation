<?php

require_once 'api/config/Database.php';

$database = new Database();
$db = $database->getConnection();

echo "--- Seeding Users ---\n";
$db->exec("INSERT IGNORE INTO users (nom, email, password) VALUES ('Admin', 'admin@example.com', '" . password_hash('password123', PASSWORD_BCRYPT) . "')");
$db->exec("INSERT IGNORE INTO users (nom, email, password) VALUES ('Admin', 'admin@facturepro.com', '" . password_hash('password123', PASSWORD_BCRYPT) . "')");

echo "--- Seeding Clients ---\n";
$vedetteA = json_encode([
    ['produitId' => 'prod_1', 'designation' => 'Produit 1', 'quantite' => 1, 'prixUnitaire' => 100.00, 'tva' => 20.0, 'remise' => 0, 'totalHT' => 100.00, 'totalTTC' => 120.00]
]);
$vedetteB = json_encode([
    ['produitId' => 'prod_2', 'designation' => 'Produit 2', 'quantite' => 1, 'prixUnitaire' => 50.00, 'tva' => 5.5, 'remise' => 0, 'totalHT' => 50.00, 'totalTTC' => 52.75]
]);

$clients = [
    ['cli_1', 'Client A', 'contact@clienta.com', '0123456789', '123 Rue de Paris', 'Paris', '75001', 'France', $vedetteA],
    ['cli_2', 'Client B', 'contact@clientb.com', '0987654321', '456 Avenue de Lyon', 'Lyon', '69002', 'France', $vedetteB]
];

foreach ($clients as $c) {
    $stmt = $db->prepare("INSERT IGNORE INTO clients (id, nom, email, telephone, adresse, ville, codePostal, pays, produitsVedette) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute($c);
}

echo "--- Seeding Produits ---\n";
$produits = [
    ['prod_1', 'REF001', 'Produit 1', 'Description du produit 1', 100.00, 20.0, 'unité', 50, 5, 'Informatique'],
    ['prod_2', 'REF002', 'Produit 2', 'Description du produit 2', 50.00, 5.5, 'heure', 100, 10, 'Service']
];

foreach ($produits as $p) {
    $stmt = $db->prepare("INSERT IGNORE INTO produits (id, reference, nom, description, prixHT, tva, unite, stock, stockMin, categorie) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute($p);
}

echo "--- Seeding Documents ---\n";
$documents = [
    ['doc_1', 'FAC-2024-001', 'facture', 'cli_1', '2024-03-26', '2024-04-26', 'en-attente', 150.00, 30.00, 180.00, 'Notes facture', 'Conditions...'],
    ['doc_2', 'DEV-2024-001', 'devis', 'cli_2', '2024-03-26', '2024-04-26', 'brouillon', 100.00, 20.00, 120.00, 'Notes devis', 'Conditions...']
];

foreach ($documents as $d) {
    $stmt = $db->prepare("INSERT IGNORE INTO documents (id, numero, type, clientId, dateCreation, dateEcheance, statut, totalHT, totalTVA, totalTTC, notes, conditions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute($d);
}

echo "--- Seeding Lignes Documents ---\n";
$lignes = [
    ['doc_1', 'prod_1', 'Produit 1', 1, 100.00, 20.0, 0, 100.00, 120.00],
    ['doc_1', 'prod_2', 'Produit 2', 1, 50.00, 20.0, 0, 50.00, 60.00],
    ['doc_2', 'prod_1', 'Produit 1', 1, 100.00, 20.0, 0, 100.00, 120.00]
];

foreach ($lignes as $l) {
    $stmt = $db->prepare("INSERT IGNORE INTO lignes_documents (documentId, produitId, designation, quantite, prixUnitaire, tva, remise, totalHT, totalTTC) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute($l);
}

echo "Seeding terminé avec succès !\n";
