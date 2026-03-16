<?php
// app-contratos/ajax_prestador_details.php
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

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID não informado']);
    exit;
}

try {
    // Busca Fornecedor
    $stmt = $pdo->prepare("SELECT * FROM Prestador WHERE Id = ?");
    $stmt->execute([$id]);
    $prestador = $stmt->fetch();

    if ($prestador) {
        // Busca Contatos
        $stmt_c = $pdo->prepare("SELECT * FROM prestador_contatos WHERE PrestadorId = ? ORDER BY Id ASC");
        $stmt_c->execute([$id]);
        $contatos = $stmt_c->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'data' => $prestador,
            'contatos' => $contatos
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fornecedor não encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de banco de dados: ' . $e->getMessage()]);
}
?>