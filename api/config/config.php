<?php

/**
 * Configuration fusionnée : défauts locaux, config.local.php, variables d'environnement (Hostinger / .htaccess SetEnv).
 */
function fp_config(): array {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $defaults = [
        'db_host' => 'localhost',
        'db_name' => 'commercial_db',
        'db_user' => 'root',
        'db_pass' => '',
        'jwt_secret' => 'votre_cle_secrete_super_securisee_123456',
        'gemini_api_key' => '',
    ];
    $localPath = __DIR__ . '/config.local.php';
    $local = [];
    if (is_readable($localPath)) {
        $loaded = require $localPath;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }
    $merged = array_merge($defaults, $local);
    $envMap = [
        'DB_HOST' => 'db_host',
        'DB_NAME' => 'db_name',
        'DB_USER' => 'db_user',
        'DB_PASS' => 'db_pass',
        'JWT_SECRET' => 'jwt_secret',
        'GEMINI_API_KEY' => 'gemini_api_key',
    ];
    foreach ($envMap as $envName => $key) {
        $v = getenv($envName);
        if ($v !== false && $v !== '') {
            $merged[$key] = $v;
        }
    }
    $cached = $merged;
    return $cached;
}
