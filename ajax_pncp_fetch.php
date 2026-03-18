<?php
// app-contratos/ajax_pncp_fetch.php
require_once 'config.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado (opcional, dependendo da sua auth_module.php)
// require_once 'auth_module.php'; 

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? 'compra'; // 'compra' ou 'contrato'

if (empty($id)) {
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

/**
 * Parse PNCP ID: {CNPJ}-{TYPE}-{SEQ}/{YEAR}
 * Ex: 05054903000179-1-000055/2025
 */
function parsePncpId($pncpId) {
    // Regex para capturar CNPJ, Sequencial e Ano
    // Formato esperado: (\d{14})-(\d)-(\d+)/(\d{4})
    if (preg_match('/^(\d{14})-(\d)-(\d+)\/(\d{4})$/', $pncpId, $matches)) {
        return [
            'cnpj' => $matches[1],
            'tipo' => $matches[2],
            'sequencial' => (int)$matches[3],
            'ano' => $matches[4]
        ];
    }
    return null;
}

$parsed = parsePncpId($id);

if (!$parsed) {
    echo json_encode(['error' => 'Formato de ID inválido. Use: 00000000000000-X-000000/0000']);
    exit;
}

$url = "";
if ($type === 'compra') {
    $url = "https://pncp.gov.br/api/consulta/v1/orgaos/{$parsed['cnpj']}/compras/{$parsed['ano']}/{$parsed['sequencial']}";
} else {
    $url = "https://pncp.gov.br/api/consulta/v1/orgaos/{$parsed['cnpj']}/contratos/{$parsed['ano']}/{$parsed['sequencial']}";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Em alguns ambientes pode ser necessário

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => "Erro ao consultar PNCP (Código HTTP: $httpCode). Verifique se o ID está correto."]);
    exit;
}

$data = json_decode($response, true);

// Mapeamento de campos para o nosso sistema
if ($type === 'compra') {
    $result = [
        'success' => true,
        'source' => $type,
        'raw' => $data,
        'mapped' => [
            'Objeto' => $data['objetoCompra'] ?? '',
            'VigenciaInicio' => '', 
            'VigenciaFim' => '',
            'DataAssinatura' => '',
            'ValorGlobalContrato' => $data['valorTotalEstimado'] ?? 0,
            'FornecedorCNPJ' => '',
            'FornecedorNome' => '',
            'NProcesso' => $data['processo'] ?? '',
        ]
    ];
} else {
    $result = [
        'success' => true,
        'source' => $type,
        'raw' => $data,
        'mapped' => [
            'Objeto' => $data['objetoContrato'] ?? '',
            'VigenciaInicio' => isset($data['dataVigenciaInicio']) ? substr($data['dataVigenciaInicio'], 0, 10) : '',
            'VigenciaFim' => isset($data['dataVigenciaFim']) ? substr($data['dataVigenciaFim'], 0, 10) : '',
            'DataAssinatura' => isset($data['dataAssinatura']) ? substr($data['dataAssinatura'], 0, 10) : '',
            'ValorGlobalContrato' => $data['valorTotal'] ?? $data['valorGlobal'] ?? $data['valorInicial'] ?? 0,
            'FornecedorCNPJ' => $data['niFornecedor'] ?? '',
            'FornecedorNome' => $data['nomeFornecedor'] ?? $data['razaoSocialNomeFornecedor'] ?? '',
            'NProcesso' => $data['numeroProcesso'] ?? '',
        ]
    ];
}

echo json_encode($result);
