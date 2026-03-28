<?php

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ClientController.php';
require_once __DIR__ . '/controllers/ProduitController.php';
require_once __DIR__ . '/controllers/DocumentController.php';

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

// Nettoyage des préfixes courants
if (isset($parts[0]) && ($parts[0] === 'index.php' || $parts[0] === 'api')) {
    array_shift($parts);
    // On vérifie une deuxième fois au cas où on aurait /index.php/api/
    if (isset($parts[0]) && ($parts[0] === 'api')) {
        array_shift($parts);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// Routes API
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
