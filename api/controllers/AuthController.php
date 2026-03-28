<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController {

    public function register() {
        $data = $this->getPostData();
        
        if (empty($data->nom) || empty($data->email) || empty($data->password)) {
            $this->sendError("Tous les champs sont requis.");
        }

        $user = new User($this->conn);
        $user->nom = $data->nom;
        $user->email = $data->email;
        $user->password = $data->password;

        if ($user->emailExists()) {
            $this->sendError("Cet email est déjà utilisé.");
        }

        if ($user->create()) {
            $this->sendResponse(["message" => "Utilisateur créé avec succès."], 201);
        } else {
            $this->sendError("Impossible de créer l'utilisateur.", 500);
        }
    }

    public function login() {
        $data = $this->getPostData();

        if (empty($data->email) || empty($data->password)) {
            $this->sendError("Email et mot de passe requis.");
        }

        $user = new User($this->conn);
        $user->email = $data->email;

        if ($user->emailExists() && password_verify($data->password, $user->password)) {
            require_once __DIR__ . '/../utils/AuthHelper.php';
            $token = AuthHelper::generateJWT([
                "id" => $user->id,
                "nom" => $user->nom,
                "email" => $user->email
            ]);

            $this->sendResponse([
                "message" => "Connexion réussie.",
                "jwt" => $token,
                "user" => [
                    "id" => $user->id,
                    "nom" => $user->nom,
                    "email" => $user->email
                ]
            ]);
        } else {
            $this->sendError("Email ou mot de passe incorrect.", 401);
        }
    }
}
