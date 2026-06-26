<?php
// init.php

// 1. Paramètres de session ultra-stables pour WAMP
session_set_cookie_params([
    'lifetime' => 86400, // 24h
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_name("IMMOBILIER_SESSION");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Connexion Base de Données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_immobiliere');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('[DB] Connexion échouée : ' . $e->getMessage());
    die("Erreur de connexion à la base de données.");
}

// 3. Fonctions utilitaires globales
/**
 * Retourne l'icône FontAwesome associée au type de bien
 */
function typeBienIcon($type) {
    $icons = [
        'villa'       => 'fa-home',
        'appartement' => 'fa-building',
        'r_plus_1'    => 'fa-layer-group',
        'r_plus_2'    => 'fa-layer-group',
        'r_plus_3'    => 'fa-layer-group',
        'terrain'     => 'fa-map',
        'commerce'    => 'fa-store',
        'batiment'    => 'fa-industry'
    ];
    
    return $icons[$type] ?? 'fa-question-circle';
}

/**
 * Formate un nombre en prix lisible (ex: 1 500 000 FCFA)
 */
function formatPrix($prix) {
    return number_format((float)$prix, 0, ',', ' ') . ' FCFA';
}

?>