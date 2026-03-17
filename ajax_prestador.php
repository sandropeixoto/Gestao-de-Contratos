<?php
// app-contratos/ajax_prestador.php
require_once 'config.php';
require_once 'auth_module.php';

header('Content-Type: application/json');

if (!CONTRATOS_LEITOR) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$term = $_GET['doc'] ?? '';
$doc = preg_replace('/[^0-9]/', '', $term); // Remove máscara

if (empty($term)) {
    echo json_encode(['success' => false, 'error' => 'Termo não informado']);
    exit;
}

try {
    // Se tiver mais números do que letras, prioriza busca por CNPJ
    if (strlen($doc) >= 5) {
        $stmt = $pdo->prepare("SELECT Id, Nome FROM Prestador 
                               WHERE CNPJ = ? OR CNPJ = ? 
                               OR CNPJ LIKE ? 
                               LIMIT 1");
        $stmt->execute([$doc, $term, "%$doc%"]);
    } else {
        // Busca prioritária por Nome
        $stmt = $pdo->prepare("SELECT Id, Nome FROM Prestador 
                               WHERE Nome LIKE ? 
                               OR CNPJ = ? 
                               LIMIT 1");
        $stmt->execute(["%$term%", $term]);
    }
    
    $prestador = $stmt->fetch();

    // Se ainda não encontrou e tem algum termo, tenta uma busca geral pelo nome
    if (!$prestador && strlen($term) >= 3) {
        $stmt = $pdo->prepare("SELECT Id, Nome FROM Prestador WHERE Nome LIKE ? LIMIT 1");
        $stmt->execute(["%$term%"]);
        $prestador = $stmt->fetch();
    }

    if ($prestador) {
        echo json_encode(['success' => true, 'data' => $prestador]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fornecedor não encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de banco de dados']);
}
