<?php
// app-contratos/auth_module.php
require_once 'config.php'; // Inicia sessão globalmente

// A re-hidratação agora é feita centralmente no auth_check.php da raiz,
// que é incluído no header.php antes deste arquivo.

$user_id = $_SESSION['user_id'] ?? 0;
$user_level = $_SESSION['user_level'] ?? '';

// Conversão segura para comparação robusta (SSOT Instruction #3)
$raw_level = strtolower(trim((string)$user_level));
$is_admin = ($raw_level === 'administrador' || $raw_level === 'admin' || $raw_level === '1');
$is_gestor = ($raw_level === 'gestor' || $raw_level === '2');

// Busca perfil específico do módulo (apenas se tiver um user_id válido)
$perfil_modulo = null;
if ($user_id > 0) {
    try {
        $stmt_p = $pdo->prepare("SELECT perfil FROM contratos_permissoes WHERE usuario_id = ?");
        $stmt_p->execute([$user_id]);
        $perfil_modulo = $stmt_p->fetchColumn();
    } catch (PDOException $e) {
        $perfil_modulo = null; // Tabela pode não existir ainda
    }
}

// Busca configuração de leitura global
$leitura_global = false;
try {
    $stmt_c = $pdo->prepare("SELECT valor FROM contratos_configuracoes WHERE chave = 'acesso_leitura_global'");
    $stmt_c->execute();
    $leitura_global = $stmt_c->fetchColumn() === '1';
} catch (PDOException $e) {
    $leitura_global = false;
}

// Definição de permissões robustas (Case-Insensitive & ID numeric support)
$raw_perfil = strtolower(trim((string)($perfil_modulo ?? '')));
define('CONTRATOS_ADMIN', $is_admin);
define('CONTRATOS_GESTOR', $is_admin || $is_gestor || $raw_perfil === 'gestor');
define('CONTRATOS_CONSULTOR', $is_admin || $is_gestor || $raw_perfil === 'gestor' || $raw_perfil === 'consultor');
define('CONTRATOS_LEITOR', CONTRATOS_CONSULTOR || $leitura_global);

// Se não houver sessão ativa de e-mail, algo está errado (auth_check.php deveria ter pego)
if (!isset($_SESSION['user_email'])) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

// Bloqueio de acesso básico (se não for leitor, não entra no módulo)
if (!CONTRATOS_LEITOR && basename($_SERVER['PHP_SELF']) !== 'index.php') {
    header("Location: ../home.php?error=no_access_contratos");
    exit;
}
?>