<?php
// app-contratos/ajax_prestador.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'auth_module.php';

header('Content-Type: application/json');

if (!CONTRATOS_LEITOR) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$doc = $_GET['doc'] ?? '';
$doc = preg_replace('/[^0-9]/', '', $doc); // Remove máscara

if (empty($doc)) {
    echo json_encode(['success' => false, 'error' => 'Documento não informado']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT Id, Nome FROM Prestador WHERE CNPJ = ? OR CNPJ = ? LIMIT 1");
    // Tenta com e sem máscara (embora o ideal seja salvar limpo, vamos garantir)
    $stmt->execute([$doc, $_GET['doc']]);
    $prestador = $stmt->fetch();

    if ($prestador) {
        echo json_encode(['success' => true, 'data' => $prestador]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fornecedor não encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de banco de dados']);
}
