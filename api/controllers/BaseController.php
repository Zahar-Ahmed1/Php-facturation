<?php

class BaseController {
    protected $db;
    protected $conn;

    public function __construct() {
        require_once __DIR__ . '/../config/Database.php';
        try {
            $this->db = new Database();
            $this->conn = $this->db->getConnection();
        } catch (Exception $e) {
            $this->enableCors(); // S'assurer que CORS est là même pour l'erreur
            $this->sendError($e->getMessage(), 500);
        }
        $this->enableCors();
    }

    protected function enableCors() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    protected function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit();
    }

    protected function sendError($message, $status = 400, $details = null) {
        $response = ["error" => $message];
        if ($details) {
            $response["details"] = $details;
        }
        $this->sendResponse($response, $status);
    }

    protected function getPostData() {
        $data = json_decode(file_get_contents("php://input"));
        if (!$data) {
            $this->sendError("Données JSON invalides.");
        }
        return $data;
    }

    protected function authenticate() {
        require_once __DIR__ . '/../utils/AuthHelper.php';
        $token = AuthHelper::getAuthToken();
        if (!$token) {
            $this->sendError("Accès refusé. Token manquant.", 401);
        }
        
        $payload = AuthHelper::validateJWT($token);
        if (!$payload) {
            $this->sendError("Accès refusé. Token invalide ou expiré.", 401);
        }
        
        return $payload;
    }
}
