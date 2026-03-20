<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Carrega variáveis do .env (sem dependência de biblioteca) ---
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// --- CENTRALIZAÇÃO DE SESSÃO GESTORGOV (MODULO) ---
$session_lifetime = 86400; // 24 horas
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/', // IMPORTANTE: Deve ser o mesmo da raiz
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// app-contratos/config.php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: '';

// Chave Secreta para integração via SSO (Deve ser a mesma do Portal)
if (!defined('SSO_SECRET_KEY')) {
    $sso_key = getenv('SSO_SECRET_KEY');
    if (!$sso_key) {
        die('SSO_SECRET_KEY não configurada. Verifique o arquivo .env.');
    }
    define('SSO_SECRET_KEY', $sso_key);
}

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
