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
                    "email" => $user->email,
                    "mustChangePassword" => (bool)$user->mustChangePassword
                ]
            ]);
        } else {
            $this->sendError("Email ou mot de passe incorrect.", 401);
        }
    }

    public function changePassword() {
        $userPayload = $this->authenticate();
        $data = $this->getPostData();

        if (empty($data->newEmail) || empty($data->newPassword)) {
            $this->sendError("Le nouvel email et le nouveau mot de passe sont requis.");
        }

        $query = "UPDATE users SET email = :email, password = :password, mustChangePassword = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $newPasswordHash = password_hash($data->newPassword, PASSWORD_BCRYPT);
        $newEmail = htmlspecialchars(strip_tags($data->newEmail));

        $stmt->bindParam(':email', $newEmail);
        $stmt->bindParam(':password', $newPasswordHash);
        $stmt->bindParam(':id', $userPayload['id']);

        if ($stmt->execute()) {
            // Optionnel : Générer un nouveau token avec le nouvel email
            require_once __DIR__ . '/../utils/AuthHelper.php';
            $newToken = AuthHelper::generateJWT([
                "id" => $userPayload['id'],
                "nom" => $userPayload['nom'],
                "email" => $newEmail
            ]);

            $this->sendResponse([
                "message" => "Identifiants mis à jour avec succès.",
                "jwt" => $newToken
            ]);
        } else {
            $this->sendError("Erreur lors de la mise à jour des identifiants.", 500);
        }
    }
}
