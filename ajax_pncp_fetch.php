<?php
// app-contratos/ajax_pncp_fetch.php
require_once 'config.php';

header('Content-Type: application/json');

$id = trim($_GET['id'] ?? '');
$type = $_GET['type'] ?? 'compra'; 

if (empty($id)) {
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

function parsePncpId($pncpId) {
    if (preg_match('/^(\d{14})-(\d)-(\d+)\/(\d{4})$/', $pncpId, $matches)) {
        return [
            'cnpj' => $matches[1],
            'tipo' => $matches[2],
            'sequencial' => $matches[3],
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

function callPncpApi($url) {
    $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $cmd = "curl -s -L -A \"$ua\" \"$url\"";
    $response = shell_exec($cmd);

    return ['code' => 200, 'data' => json_decode($response, true)];
}

$data = null;

if ($type === 'compra') {
    $res = callPncpApi("https://pncp.gov.br/api/consulta/v1/orgaos/{$parsed['cnpj']}/compras/{$parsed['ano']}/" . (int)$parsed['sequencial']);
    if ($res['code'] === 200) {
        $data = $res['data'];
    }
} else {
    $dataInicial = "{$parsed['ano']}0101";
    $dataFinal = "{$parsed['ano']}1231";

    // Varredura inteligente
    for ($page = 1; $page <= 5; $page++) {
        $listUrl = "https://pncp.gov.br/api/consulta/v1/contratos?dataInicial={$dataInicial}&dataFinal={$dataFinal}&cnpjOrgao={$parsed['cnpj']}&pagina={$page}&tamanhoPagina=100";
        $resList = callPncpApi($listUrl);

        if ($resList['code'] === 200 && isset($resList['data']['data'])) {
            foreach ($resList['data']['data'] as $contract) {
                // Comparação rigorosa sem espaços
                if (trim($contract['numeroControlePNCP']) === $id) {
                    $data = $contract;
                    break 2;
                }
            }
            if (count($resList['data']['data']) < 100) break;
        } else {
            break;
        }
    }
}

if (!$data) {
    echo json_encode(['error' => 'Contrato não localizado no PNCP. Verifique se o ID está correto ou se foi publicado recentemente.']);
    exit;
}

// Mapeamento de campos
$result = [
    'success' => true,
    'mapped' => [
        'Objeto' => $data['objetoCompra'] ?? $data['objetoContrato'] ?? '',
        'VigenciaInicio' => isset($data['dataVigenciaInicio']) ? substr($data['dataVigenciaInicio'], 0, 10) : '',
        'VigenciaFim' => isset($data['dataVigenciaFim']) ? substr($data['dataVigenciaFim'], 0, 10) : '',
        'DataAssinatura' => isset($data['dataAssinatura']) ? substr($data['dataAssinatura'], 0, 10) : '',
        'ValorGlobalContrato' => $data['valorGlobal'] ?? $data['valorInicial'] ?? $data['valorTotalEstimado'] ?? 0,
        'ValorMensalContrato' => $data['valorParcela'] ?? 0,
        'NumeroParcelas' => $data['numeroParcelas'] ?? 0,
        'FornecedorCNPJ' => $data['niFornecedor'] ?? '',
        'FornecedorNome' => $data['nomeFornecedor'] ?? $data['razaoSocialNomeFornecedor'] ?? $data['nomeRazaoSocialFornecedor'] ?? '',
        'NProcesso' => $data['processo'] ?? $data['numeroProcesso'] ?? '',
        'SeqContrato' => $data['numeroContratoEmpenho'] ?? '',
        'AnoContrato' => $data['anoContrato'] ?? '',
        'PncpIdContratacao' => $data['numeroControlePncpCompra'] ?? '',
    ]
];

echo json_encode($result);
