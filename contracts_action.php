<?php
// app-contratos/contracts_action.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_module.php';
require_once __DIR__ . '/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contratos.php");
    exit;
}

$action = $_POST['action'] ?? '';

// Proteção de permissão no Backend
if (($action === 'create' || $action === 'update') && !CONTRATOS_CONSULTOR) {
    die("Acesso negado: você não tem permissão para salvar documentos.");
}
if ($action === 'delete' && !CONTRATOS_GESTOR) {
    die("Acesso negado: você não tem permissão para excluir documentos.");
}
$id = $_POST['id'] ?? null;

try {
    $pdo->beginTransaction();

    if ($action === 'create' || $action === 'update') {
        $paiId = $_POST['PaiId'] ?? 0;
        $tipoDocId = ($paiId == 0) ? 1 : ($_POST['TipoDocumentoId'] ?? 2); // 1 = Contrato, 2 = Termo Aditivo (fallback)

        $data = [
            'Objeto' => $_POST['Objeto'],
            'VigenciaInicio' => $_POST['VigenciaInicio'],
            'VigenciaFim' => $_POST['VigenciaFim'],
            'DataAssinatura' => $_POST['DataAssinatura'],
            'SeqContrato' => $_POST['SeqContrato'],
            'AnoContrato' => $_POST['AnoContrato'] ?: null,
            'PaiId' => $paiId,
            'TipoDocumentoId' => $tipoDocId,
            'FiscalContrato' => $_POST['FiscalContrato'] ?: null,
            'EmailFiscal' => $_POST['EmailFiscal'] ?: null,
            'FiscalSubstituto' => $_POST['FiscalSubstituto'] ?: null,
            'EmailFiscalSubstituto' => $_POST['EmailFiscalSubstituto'] ?: null,
            'PrestadorId' => $_POST['PrestadorId'],
            'ValorMensalContrato' => $_POST['ValorMensalContrato'] ?: null,
            'ValorGlobalContrato' => $_POST['ValorGlobalContrato'],
            'NProcesso' => $_POST['NProcesso'] ?: null,
            'ModalidadeId' => $_POST['ModalidadeId'] ?: null,
            'NumeroModalidade' => $_POST['NumeroModalidade'] ?: null,
            'DiretoriaId' => $_POST['DiretoriaId'] ?: null,
            'CoordenacaoId' => $_POST['CoordenacaoId'] ?: null,
            'CategoriaContratoId' => $_POST['CategoriaContratoId'] ?: null,
            'FonteRecursosId' => $_POST['FonteRecursosId'] ?: null,
            'FundamentacaoLegal' => $_POST['FundamentacaoLegal'] ?? null,
            'ProgramaTrabalho' => $_POST['ProgramaTrabalho'] ?? null,
            'FuncionalProgramatica' => $_POST['FuncionalProgramatica'] ?? null,
            'NaturezaDespesa' => $_POST['NaturezaDespesa'] ?? null,
            'FonteRecursos' => $_POST['FonteRecursos'] ?? null,
            'NumeroDiarioOficialContrato' => $_POST['NumeroDiarioOficialContrato'] ?? null,
            'Observacao' => $_POST['Observacao'] ?? null
        ];

        $contrato_final_id = $id;

        if ($action === 'create') {
            $cols = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $sql = "INSERT INTO Contratos ($cols) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $contrato_final_id = $pdo->lastInsertId();
            
            logSistema('Create', 'Contratos', $contrato_final_id, json_encode($data));
        } else {
            $sets = [];
            foreach ($data as $key => $val) {
                $sets[] = "$key = :$key";
            }
            $sql = "UPDATE Contratos SET " . implode(", ", $sets) . " WHERE Id = :id";
            $data['id'] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            logSistema('Update', 'Contratos', $id, json_encode($data));
        }

        // Sincronização de Fiscais Setoriais
        $stmt_del = $pdo->prepare("DELETE FROM contratos_fiscais_setoriais WHERE contrato_id = ?");
        $stmt_del->execute([$contrato_final_id]);

        if (isset($_POST['fs_nome']) && is_array($_POST['fs_nome'])) {
            foreach ($_POST['fs_nome'] as $k => $nome) {
                if (!empty(trim($nome))) {
                    $stmt_fs = $pdo->prepare("INSERT INTO contratos_fiscais_setoriais (contrato_id, nome, email) VALUES (?, ?, ?)");
                    $stmt_fs->execute([$contrato_final_id, $nome, $_POST['fs_email'][$k] ?? null]);
                }
            }
        }

        $pdo->commit();
        
        $redirect = $_POST['redirect'] ?? "contratos.php?msg=success";
        header("Location: $redirect");
        exit;

    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM Contratos WHERE Id = ?");
        $stmt->execute([$id]);
        
        logSistema('Delete', 'Contratos', $id);
        
        $pdo->commit();
        $redirect = $_POST['redirect'] ?? "contratos.php?msg=deleted";
        header("Location: $redirect");
        exit;
    }

} catch (PDOException $e) {
    die("Erro ao processar ação: " . $e->getMessage());
}

header("Location: contratos.php");
exit;
?>
