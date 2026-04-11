
# API Backend SaaS de Gestion Commerciale (PHP Natif)

Cette API backend est conçue pour une application SaaS de facturation, développée en PHP natif (sans framework) pour être facilement déployable sur n'importe quel hébergement classique (cPanel, Apache, etc.).

## 🏗️ Architecture
L'API suit une structure **MVC (Modèle-Vue-Contrôleur)** simplifiée :
- **Models** : Gèrent la logique de données et les interactions avec la base de données MySQL via PDO.
- **Controllers** : Gèrent la logique métier, l'authentification et les réponses HTTP.
- **Utils/Config** : Utilitaires pour le JWT, la connexion DB et les helpers.
- **Public/index.php** : Point d'entrée unique (Router) qui redirige les requêtes.

## 🚀 Installation
1. Importez le fichier `api/database/schema.sql` dans votre base de données MySQL.
2. Configurez vos identifiants :
   - Copiez `api/config/config.local.php.example` vers `api/config/config.local.php`.
   - Modifiez les valeurs dans `api/config/config.local.php` (DB, JWT, Gemini API).
   - *Alternativement, vous pouvez utiliser des variables d'environnement (DB_HOST, DB_NAME, DB_USER, DB_PASS, JWT_SECRET, GEMINI_API_KEY).*
3. (Optionnel) Exécutez le seeder pour ajouter des données de test : `php seeder.php`.
4. Assurez-vous que le module `mod_rewrite` est activé sur votre serveur Apache.

## ⚙️ Configuration
L'application utilise une hiérarchie de configuration :
1. **Défauts** : Définis dans `api/config/config.php`.
2. **Fichier Local** : `api/config/config.local.php` (Ignoré par Git, recommandé pour le développement et la production).
3. **Variables d'Environnement** : Prioritaires sur les fichiers si elles sont définies (Idéal pour l'hébergement cloud ou Hostinger via `.htaccess`).

## 🔐 Authentification
L'authentification utilise des **JSON Web Tokens (JWT)**.
1. `POST /api/register` : Créer un compte.
2. `POST /api/login` : Obtenir un token.
3. Ajoutez le header `Authorization: Bearer <votre_token>` pour toutes les routes protégées.
*Note : Pour la production, générez une clé secrète longue et aléatoire dans votre configuration.*

## 🛠️ Routes API
Toutes les routes retournent du JSON et attendent du JSON en entrée.

### Clients
- `GET /api/clients` (Supporte `?search=...`, `?page=1`, `?limit=10`)
- `POST /api/clients`
- `PUT /api/clients/{id}`
- `DELETE /api/clients/{id}`

### Produits
- `GET /api/produits`
- `POST /api/produits`
- `PUT /api/produits/{id}`
- `DELETE /api/produits/{id}`

### Documents (Devis, Factures...)
- `GET /api/documents` (Filtres: `?type=facture`, `?clientId=...`)
- `POST /api/documents`
- `POST /api/documents/convertir/{id}?to=facture` (Convertit un devis en facture)
- `POST /api/documents/extraire` : Extraction intelligente via IA (Gemini). Envoyez un fichier (PDF, Image) via `multipart/form-data`.

## 🤖 Extraction par IA (Gemini)
Cette API intègre Google Gemini pour extraire automatiquement les données des factures/devis numérisés.
- **Endpoint** : `POST /api/documents/extraire`
- **Format** : `multipart/form-data` avec le champ `file`.
- **Requis** : Une clé API Gemini valide dans la configuration.

## 🔗 Intégration Angular (Exemple)

### AuthService
```typescript
import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private apiUrl = 'http://votre-domaine.com/api';

  constructor(private http: HttpClient) {}

  login(credentials: any) {
    return this.http.post(`${this.apiUrl}/login`, credentials);
  }

  getHeaders() {
    const token = localStorage.getItem('jwt');
    return { 'Authorization': `Bearer ${token}` };
  }
}
```

### ClientService
```typescript
@Injectable({ providedIn: 'root' })
export class ClientService {
  constructor(private http: HttpClient, private auth: AuthService) {}

  getClients() {
    return this.http.get(`${this.apiUrl}/clients`, { headers: this.auth.getHeaders() });
  }
}
```

## 📮 Postman
Importez ces exemples de requêtes :
- **Login** : `POST /api/login` avec `{"email": "admin@example.com", "password": "password123"}`
- **Create Client** : `POST /api/clients` avec `{"nom": "Nouveau Client", "email": "test@client.com"}`
- **Create Document** : `POST /api/documents` avec :
```json
{
  "type": "devis",
  "clientId": "cli_1",
  "numero": "QUO-2024-001",
  "dateCreation": "2024-03-26",
  "lignes": [
    {
      "produitId": "prod_1",
      "designation": "Installation Serveur",
      "quantite": 1,
      "prixUnitaire": 500,
      "tva": 20
    }
  ]
}
```

## 💡 Fonctionnalités Spéciales
- **Calcul automatique** : Les totaux HT, TVA et TTC sont recalculés côté serveur lors de la création/mise à jour d'un document.
- **Conversion** : Un devis peut être transformé en facture ou bon de livraison en conservant les lignes et le lien parent.
- **CORS** : Pré-configuré pour accepter les requêtes provenant de votre application Angular.
