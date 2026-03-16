<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$host = "192.185.214.25"; // "srv24.prodns.com.br";
$user = "eventoss_contratos";
$pass = "Senh@2025";
$db = "eventoss_contratos";

// Chave Secreta para integração via SSO (Deve ser a mesma do Portal)
if (!defined('SSO_SECRET_KEY')) {
    define('SSO_SECRET_KEY', 'GestorGov_Secure_Integration_Token_2026!');
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
