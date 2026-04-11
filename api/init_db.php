<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $config = fp_config();
    echo "Diagnostic de la configuration :\n";
    echo "- Utilisateur DB détecté : " . $config['db_user'] . "\n";
    echo "- Hôte DB détecté : " . $config['db_host'] . "\n";
    echo "- Nom DB détecté : " . $config['db_name'] . "\n";
    
    $localConfigPath = __DIR__ . '/config/config.local.php';
    if (file_exists($localConfigPath)) {
        echo "- Fichier config.local.php : TROUVÉ (OK)\n";
    } else {
        echo "- Fichier config.local.php : INTROUVABLE\n";
    }
    echo "------------------------------------------\n\n";

    $database = new Database();
    $db = $database->getConnection();
    
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Initialisation de la base de données ($driver)...\n\n";

    // 1. Forcer la recréation des tables pour être sûr de la structure
    echo "Nettoyage et recréation des tables...\n";
    $schemaPath = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("Le fichier database/schema.sql est manquant sur le serveur !");
    }
    $sql = file_get_contents($schemaPath);
    
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*;/i', '', $sql);
    $sql = preg_replace('/USE .*;/i', '', $sql);
    
    if ($driver === 'mysql') {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // On supprime les tables existantes pour forcer le nouveau schéma
        $tablesToDrop = ['lignes_documents', 'documents', 'clients', 'entreprises', 'racines', 'produits', 'users'];
        foreach ($tablesToDrop as $table) {
            $db->exec("DROP TABLE IF EXISTS $table");
            echo "Table $table supprimée.\n";
        }

        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            $q = trim($query);
            if (!empty($q)) {
                $db->exec($q);
            }
        }
    } else {
        $db->exec("PRAGMA foreign_keys = OFF");
        $db->exec($sql);
    }
    echo "Tables recréées avec succès.\n";

    // 2. Insérer les données d'exemple
    echo "\nInsertion des données d'exemple...\n";
    
    // User par défaut (admin / demo123)
    $email = 'admin@facturepro.com';
    $password = 'demo123';
    $passHash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO users (id, nom, email, password, role, mustChangePassword) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute(['usr_1', 'Administrateur', $email, $passHash, 'admin', 1])) {
        echo "- Utilisateur admin créé : $email / $password (Changement de mot de passe requis)\n";
    } else {
        echo "- ERREUR lors de la création de l'utilisateur.\n";
    }

    // Racine
    $db->exec("INSERT INTO racines (id, nom, ville, adresse, ice) VALUES ('rac_1', 'Groupe facilyx', 'Casablanca', '77 RUE MOHAMED SMIHA, 10 ETG, APT N° 57', '001234567890123')");
    echo "- Exemple Racine créé.\n";

    // Entreprise
    $db->exec("INSERT INTO entreprises (id, racineId, nom, ville, email, ice, pays) VALUES ('ent_1', 'rac_1', 'facilyx SARL', 'Casablanca', 'contact@facilyx.com', '001234567890123', 'Maroc')");
    echo "- Exemple Entreprise créé.\n";

    // Client
    $db->exec("INSERT INTO clients (id, entrepriseId, nom, email, telephone, adresse, ville, codePostal, pays, siret) VALUES ('cli_1', 'ent_1', 'Client Test', 'client@test.com', '0600000000', 'Adresse Test', 'Casablanca', '20000', 'Maroc', '001234567890123')");
    echo "- Exemple Client créé.\n";

    // Produit
    $db->exec("INSERT INTO produits (id, reference, nom, description, prixHT, tva, unite, stock, stockMin, categorie) VALUES ('prod_1', 'REF-001', 'Service Consulting', 'Prestation de conseil', 500.00, 20.0, 'Heure', 100, 0, 'Services')");
    echo "- Exemple Produit créé.\n";

    // Document (Devis)
    $db->exec("INSERT INTO documents (id, numero, type, clientId, dateCreation, dateEcheance, statut, totalHT, totalTVA, totalTTC, notes, custom_to, custom_co, custom_group, custom_ice) 
               VALUES ('doc_1', 'DEV-2026-001', 'devis', 'cli_1', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'brouillon', 500.00, 100.00, 600.00, 'Note de test', 'Client Test', 'facilyx SARL', 'Groupe facilyx', '001234567890123')");
    echo "- Exemple Devis créé.\n";

    // Ligne Document
    $db->exec("INSERT INTO lignes_documents (documentId, produitId, designation, quantite, prixUnitaire, tva, remise, totalHT, totalTTC) 
               VALUES ('doc_1', 'prod_1', 'Service Consulting', 1, 500.00, 20.0, 0, 500.00, 600.00)");
    echo "- Ligne de document créée.\n";

    // 3. Vérification finale
    echo "\nVérification de l'utilisateur dans la base :\n";
    $stmt = $db->prepare("SELECT email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "- Utilisateur trouvé : " . $user['email'] . "\n";
        if (password_verify($password, $user['password'])) {
            echo "- Vérification du mot de passe : OK\n";
        } else {
            echo "- ERREUR : Le mot de passe stocké ne correspond pas !\n";
        }
    } else {
        echo "- ERREUR : L'utilisateur n'a pas été inséré !\n";
    }

    if ($driver === 'mysql') {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } else {
        $db->exec("PRAGMA foreign_keys = ON");
    }

    echo "\nInitialisation terminée avec succès !";

} catch (Exception $e) {
    echo "\nERREUR : " . $e->getMessage();
}
