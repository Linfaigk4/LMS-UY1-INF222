<?php
/**
 * GOL (Gugle Online Learning) - Configuration principale
 * Développeur: ESSENGUE BILOA VICTORIEN MICHEL
 * Matricule: 23U2628
 * Université de Yaoundé 1 - INF-L2
 */

// Configuration sécurisée des cookies de session
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,              // Cookie de session (expire à la fermeture du navigateur)
    'path'     => '/',
    'domain'   => '',             // Domaine courant
    'secure'   => $https,         // HTTPS uniquement si disponible
    'httponly' => true,           // Inaccessible en JavaScript
    'samesite' => 'Lax',          // Protection CSRF supplémentaire
]);

session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gol_lms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_NAME', 'GOL - Gugle Online Learning');
define('SITE_URL', 'http://localhost/GOL/');
define('SITE_DESCRIPTION', 'La plateforme d\'apprentissage nouvelle génération');
define('SITE_KEYWORDS', 'LMS, formation, e-learning, cours en ligne, certificat');

// Chemins des dossiers
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('UPLOAD_PDF', UPLOAD_PATH . 'pdf/');
define('UPLOAD_VIDEOS', UPLOAD_PATH . 'videos/');
define('UPLOAD_AVATARS', UPLOAD_PATH . 'avatars/');
define('UPLOAD_MODULES', UPLOAD_PATH . 'modules_images/');

// Limites
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 Mo
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024); // 2 Mo

// Timezone
date_default_timezone_set('Africa/Douala');

// Connexion à la base de données
// Sous XAMPP Linux, utiliser le socket Unix pour éviter SQLSTATE[HY000] [2002] Connection refused
define('DB_SOCKET', '/opt/lampp/var/mysql/mysql.sock');

try {
    $dsn = file_exists(DB_SOCKET)
        ? "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=utf8mb4"
        : "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Ne pas exposer les détails de la connexion en production
    error_log('GOL - Erreur PDO : ' . $e->getMessage());
    http_response_code(503);
    die('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Service indisponible</title></head><body style="font-family:sans-serif;text-align:center;padding:4rem;background:#f8fafc"><h1 style="color:#ef4444">Service temporairement indisponible</h1><p>Veuillez réessayer dans quelques instants.</p></body></html>');
}

// Thème (clair/sombre)
$theme = isset($_COOKIE['gol_theme']) ? $_COOKIE['gol_theme'] : 'light';

// Version de l'application
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'ESSENGUE BILOA VICTORIEN MICHEL');
define('APP_MATRICULE', '23U2628');
?>