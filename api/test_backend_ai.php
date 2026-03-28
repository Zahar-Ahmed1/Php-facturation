<?php
// Script pour tester l'API PHP seule avec CURL (sans passer par Angular)

// On génère un token de test
require_once __DIR__ . '/utils/AuthHelper.php';
$token = AuthHelper::generateJWT(['id' => 1, 'email' => 'admin@example.com']);

// Utiliser l'URL racine de l'API
$backend_url = "http://127.0.0.1:8000/api/documents/extraire";
$image_path = "/Users/zahar/Desktop/test-conta/facilyx_invoice.png";

if (!file_exists($image_path)) {
    die("ERREUR : L'image est introuvable à l'adresse : $image_path\n");
}

echo "--- DÉBUT DU TEST BACKEND ---\n";
echo "URL : $backend_url\n";
echo "Image : $image_path (" . filesize($image_path) . " octets)\n";

$ch = curl_init($backend_url);

$cfile = new CURLFile($image_path, 'image/png', 'file');
$data = ['file' => $cfile];

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Expect:" // Supprime l'attente 100-continue
]);

// Options de robustesse
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force l'IPv4
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "CODE HTTP : $http_code\n";
if ($curl_error) echo "ERREUR CURL : $curl_error\n";
echo "RÉPONSE DU SERVEUR :\n";
echo $response . "\n";
echo "--- FIN DU TEST ---\n";
