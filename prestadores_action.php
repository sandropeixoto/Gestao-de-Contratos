<?php
// app-contratos/prestadores_action.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_module.php';
require_once __DIR__ . '/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: prestadores.php");
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;

if (($action === 'create' || $action === 'update') && !CONTRATOS_CONSULTOR) {
    die("Acesso negado: sem permissão para salvar.");
}

if ($action === 'delete' && !CONTRATOS_GESTOR) {
    die("Acesso negado: sem permissão para excluir.");
}

try {
    if ($action === 'create' || $action === 'update') {
        $cnpj_limpo = preg_replace('/[^0-9]/', '', $_POST['CNPJ']);

        $data = [
            'Nome' => $_POST['Nome'],
            'Tipo' => $_POST['Tipo'],
            'CNPJ' => $cnpj_limpo,
            'CEP' => $_POST['CEP'],
            'Logradouro' => $_POST['Logradouro'],
            'Numero' => $_POST['Numero'],
            'Complemento' => $_POST['Complemento'],
            'Bairro' => $_POST['Bairro'],
            'Cidade' => $_POST['Cidade'],
            'UF' => strtoupper($_POST['UF'])
        ];

        $pdo->beginTransaction();

        if ($action === 'create') {
            $cols = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $stmt = $pdo->prepare("INSERT INTO Prestador ($cols) VALUES ($placeholders)");
            $stmt->execute($data);
            $prestadorId = $pdo->lastInsertId();

            logSistema('Create', 'Prestador', $prestadorId, json_encode($data));
        } else {
            $sets = [];
            foreach ($data as $key => $val) {
                $sets[] = "$key = :$key";
            }
            $stmt = $pdo->prepare("UPDATE Prestador SET " . implode(", ", $sets) . " WHERE Id = :id");
            $data['id'] = $id;
            $stmt->execute($data);
            $prestadorId = $id;

            logSistema('Update', 'Prestador', $prestadorId, json_encode($data));
        }

        // Salvar Contatos (1-N)
        // Primeiro remove os antigos para reinserir (simplificação de sincronização)
        $stmt_del = $pdo->prepare("DELETE FROM prestador_contatos WHERE PrestadorId = ?");
        $stmt_del->execute([$prestadorId]);

        if (isset($_POST['contato_nome'])) {
            foreach ($_POST['contato_nome'] as $key => $nome) {
                if (!empty($nome)) {
                    $stmt_c = $pdo->prepare("INSERT INTO prestador_contatos (PrestadorId, Tipo, Nome, Email, Telefone) VALUES (?, ?, ?, ?, ?)");
                    $stmt_c->execute([
                        $prestadorId,
                        $_POST['contato_tipo'][$key],
                        $nome,
                        $_POST['contato_email'][$key],
                        $_POST['contato_tel'][$key]
                    ]);
                }
            }
        }

        $pdo->commit();
        header("Location: prestadores.php?msg=success");
        exit;

    } elseif ($action === 'delete' && $id) {
        // 1. Verificar se existem contratos atrelados
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Contratos WHERE PrestadorId = ?");
        $stmt_check->execute([$id]);
        if ($stmt_check->fetchColumn() > 0) {
            header("Location: prestadores.php?msg=error_has_contracts");
            exit;
        }

        // 2. Excluir (a exclusão de contatos é automática via ON DELETE CASCADE no banco)
        $stmt = $pdo->prepare("DELETE FROM Prestador WHERE Id = ?");
        $stmt->execute([$id]);
        
        logSistema('Delete', 'Prestador', $id);
        
        header("Location: prestadores.php?msg=success");
        exit;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Erro ao processar fornecedor: " . $e->getMessage());
}
?>