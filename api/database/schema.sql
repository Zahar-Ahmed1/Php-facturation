CREATE DATABASE IF NOT EXISTS commercial_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE commercial_db;

-- Table users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table clients
CREATE TABLE IF NOT EXISTS clients (
    id VARCHAR(50) PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telephone VARCHAR(50),
    adresse TEXT,
    ville VARCHAR(100),
    codePostal VARCHAR(20),
    pays VARCHAR(100),
    siret VARCHAR(20),
    tva VARCHAR(50),
    produitsVedette JSON DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table produits
CREATE TABLE IF NOT EXISTS produits (
    id VARCHAR(50) PRIMARY KEY,
    reference VARCHAR(100) NOT NULL UNIQUE,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prixHT DECIMAL(15, 2) NOT NULL,
    tva DECIMAL(5, 2) NOT NULL,
    unite VARCHAR(50),
    stock INT DEFAULT 0,
    stockMin INT DEFAULT 0,
    categorie VARCHAR(100)
);

-- Table documents
CREATE TABLE IF NOT EXISTS documents (
    id VARCHAR(50) PRIMARY KEY,
    numero VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('devis', 'facture', 'bon-livraison', 'bon-commande') NOT NULL,
    clientId VARCHAR(50) NOT NULL,
    dateCreation DATE NOT NULL,
    dateEcheance DATE,
    statut ENUM('brouillon', 'envoyé', 'accepté', 'refusé', 'converti', 'payé', 'en-attente', 'livré') DEFAULT 'brouillon',
    totalHT DECIMAL(15, 2) DEFAULT 0,
    totalTVA DECIMAL(15, 2) DEFAULT 0,
    totalTTC DECIMAL(15, 2) DEFAULT 0,
    notes TEXT,
    conditions TEXT,
    documentParentId VARCHAR(50),
    FOREIGN KEY (clientId) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (documentParentId) REFERENCES documents(id) ON DELETE SET NULL
);

-- Table lignes_documents
CREATE TABLE IF NOT EXISTS lignes_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documentId VARCHAR(50) NOT NULL,
    produitId VARCHAR(50),
    designation TEXT NOT NULL,
    quantite INT NOT NULL,
    prixUnitaire DECIMAL(15, 2) NOT NULL,
    tva DECIMAL(5, 2) NOT NULL,
    remise DECIMAL(5, 2) DEFAULT 0,
    totalHT DECIMAL(15, 2) NOT NULL,
    totalTTC DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (documentId) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (produitId) REFERENCES produits(id) ON DELETE SET NULL
);
