<?php

// Autoriser les requêtes CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Gérer les requêtes OPTIONS (preflight)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/Database.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ClientController.php';
require_once __DIR__ . '/controllers/ProduitController.php';
require_once __DIR__ . '/controllers/DocumentController.php';
require_once __DIR__ . '/controllers/RacineController.php';
require_once __DIR__ . '/controllers/EntrepriseController.php';

// Si on utilise le serveur intégré PHP comme routeur, on sert les fichiers statiques s'ils existent
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path !== '/' && file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        return false;
    }
}

// Gestion de l'URL
$url = isset($_GET['url']) ? $_GET['url'] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = ltrim($url, '/');
$url = rtrim($url, '/');
$parts = explode('/', $url);

// Nettoyage des préfixes courants (index.php, api, ou dossier parent comme facturation)
$prefixes = ['index.php', 'api', 'facturation'];
while (isset($parts[0]) && in_array($parts[0], $prefixes)) {
    array_shift($parts);
}

$method = $_SERVER['REQUEST_METHOD'];

// Routes API
if (!isset($parts[0])) {
    $parts[0] = '';
}

switch ($parts[0]) {
    case '': // Route racine
        if ($method == 'GET') {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>API de Gestion Commerciale</h1><p>Le service est en ligne et fonctionne.</p>';
        }
        break;

    case 'login':
        if ($method == 'POST') {
            $auth = new AuthController();
            $auth->login();
        }
        break;

    case 'change-password':
        if ($method == 'POST') {
            $auth = new AuthController();
            $auth->changePassword();
        }
        break;

    case 'register':
        if ($method == 'POST') {
            $auth = new AuthController();
            $auth->register();
        }
        break;

    case 'clients':
        $controller = new ClientController();
        if ($method == 'GET') {
            if (isset($parts[1])) { $controller->getOne($parts[1]); } 
            else { $controller->getAll(); }
        } elseif ($method == 'POST') {
            $controller->create();
        } elseif ($method == 'PUT' && isset($parts[1])) {
            $controller->update($parts[1]);
        } elseif ($method == 'DELETE' && isset($parts[1])) {
            $controller->delete($parts[1]);
        }
        break;

    case 'racines':
        $controller = new RacineController();
        if ($method == 'GET') {
            if (isset($parts[1])) { $controller->getOne($parts[1]); } 
            else { $controller->getAll(); }
        } elseif ($method == 'POST') {
            $controller->create();
        } elseif ($method == 'PUT' && isset($parts[1])) {
            $controller->update($parts[1]);
        } elseif ($method == 'DELETE' && isset($parts[1])) {
            $controller->delete($parts[1]);
        }
        break;

    case 'entreprises':
        $controller = new EntrepriseController();
        if ($method == 'GET') {
            if (isset($parts[1])) { $controller->getOne($parts[1]); } 
            else { $controller->getAll(); }
        } elseif ($method == 'POST') {
            $controller->create();
        } elseif ($method == 'PUT' && isset($parts[1])) {
            $controller->update($parts[1]);
        } elseif ($method == 'DELETE' && isset($parts[1])) {
            $controller->delete($parts[1]);
        }
        break;

    case 'produits':
        $controller = new ProduitController();
        if ($method == 'GET') {
            if (isset($parts[1])) { $controller->getOne($parts[1]); } 
            else { $controller->getAll(); }
        } elseif ($method == 'POST') {
            $controller->create();
        } elseif ($method === 'PUT' && isset($parts[1])) {
            $controller->update($parts[1]);
        } elseif ($method === 'DELETE' && isset($parts[1])) {
            $controller->delete($parts[1]);
        }
        break;

    case 'documents':
        $controller = new DocumentController();
        if ($method == 'GET') {
            if (isset($parts[1])) { $controller->getOne($parts[1]); } 
            else { $controller->getAll(); }
        } elseif ($method == 'POST') {
            if (isset($parts[1]) && $parts[1] == 'convertir' && isset($parts[2])) {
                $controller->convertir($parts[2]);
            } elseif (isset($parts[1]) && $parts[1] == 'extraire') {
                $controller->extraireIA();
            } else {
                $controller->create();
            }
        } elseif ($method == 'PUT' && isset($parts[1])) {
            $controller->update($parts[1]);
        } elseif ($method == 'DELETE' && isset($parts[1])) {
            $controller->delete($parts[1]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Route non trouvée : " . ($parts[0] ?? 'vide')]);
        break;
}
