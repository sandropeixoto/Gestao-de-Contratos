<?php
/**
 * app-contratos/auth_sso.php
 * Receptor de Autenticação SSO para o Módulo de Contratos.
 * Permite o login automático vindo do Portal GestorGov.
 */
require_once 'config.php';
require_once 'logger.php';

// session_start() não é mais necessário aqui, já está no config.php de forma centralizada

// 1. Captura os dados da URL enviados pelo Portal
$payload_base64 = $_GET['sso_payload'] ?? null;
$signature_received = $_GET['sso_sig'] ?? null;

if (!$payload_base64 || !$signature_received) {
    header("Location: ../index.php?error=sso_missing_token");
    exit;
}

// 2. Valida a assinatura HMAC-SHA256 usando a chave secreta compartilhada
// A constante SSO_SECRET_KEY deve estar definida no config.php deste módulo
$expected_signature = hash_hmac('sha256', $payload_base64, SSO_SECRET_KEY);

if ($signature_received !== $expected_signature) {
    die("Erro de Segurança: Assinatura SSO inválida. Verifique a chave secreta.");
}

// 3. Decodifica o Payload
$payload_json = base64_decode($payload_base64);
$user_data = json_decode($payload_json, true);

// 4. Verifica a expiração (iat = Issued At, exp = Expires At)
if (time() > ($user_data['exp'] ?? 0)) {
    die("Erro de Autenticação: O token SSO expirou por segurança. Tente acessar novamente via Portal.");
}

// 5. Normaliza e define a Sessão Local do Módulo de Contratos
$_SESSION['user_id']    = $user_data['user_id'] ?? 0;
$_SESSION['user_name']  = $user_data['user_name'] ?? 'Usuário';
$_SESSION['user_email'] = $user_data['user_email'] ?? '';

// Normalização de Nível (Garante compatibilidade com as travas de segurança)
$raw_level = strtolower(trim((string)($user_data['user_level'] ?? 'Consultor')));
if ($raw_level === 'administrador' || $raw_level === '1') $_SESSION['user_level'] = 'Administrador';
elseif ($raw_level === 'gestor' || $raw_level === '2') $_SESSION['user_level'] = 'Gestor';
else $_SESSION['user_level'] = 'Consultor';

// 6. Log de sucesso no login SSO (Ajustado para o novo logger.php)
logSistema('LOGIN', 'usuarios', $_SESSION['user_id'], 'SSO Login Success: ' . $_SESSION['user_email']);

// 7. Redireciona para a Dashboard do Módulo de Contratos
header("Location: index.php");
exit;
