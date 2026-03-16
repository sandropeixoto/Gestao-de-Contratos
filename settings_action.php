<?php
// app-contratos/settings_action.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_module.php';
require_once __DIR__ . '/logger.php';

if (!CONTRATOS_ADMIN) {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: settings.php");
    exit;
}

$action = $_POST['action'] ?? '';
$tab = $_POST['tab'] ?? '';
$pk_value = $_POST['pk_value'] ?? null;

// Processamento especial para aba de Permissões
if ($tab === 'permissoes') {
        if ($action === 'toggle_global_read') {
            $valor = isset($_POST['leitura_global']) ? '1' : '0';
            $stmt = $pdo->prepare("UPDATE contratos_configuracoes SET valor = ? WHERE chave = 'acesso_leitura_global'");
            $stmt->execute([$valor]);
            logSistema('Toggle Global Read', 'contratos_configuracoes', null, json_encode(['valor' => $valor]));
        } 
        elseif ($action === 'update_user_permission') {
            $usuario_id = $_POST['usuario_id'];
            $perfil = $_POST['perfil'];

            if (empty($perfil)) {
                $stmt = $pdo->prepare("DELETE FROM contratos_permissoes WHERE usuario_id = ?");
                $stmt->execute([$usuario_id]);
                logSistema('Remove Permission', 'contratos_permissoes', $usuario_id);
            } else {
                $stmt = $pdo->prepare("INSERT INTO contratos_permissoes (usuario_id, perfil) VALUES (?, ?) ON DUPLICATE KEY UPDATE perfil = ?");
                $stmt->execute([$usuario_id, $perfil, $perfil]);
                logSistema('Update Permission', 'contratos_permissoes', $usuario_id, json_encode(['perfil' => $perfil]));
            }
        }
        header("Location: settings.php?tab=permissoes&msg=success");
        exit;
    }

    // Re-mapear configurações para processamento de tabelas auxiliares
    $tables = [
    'diretorias' => ['table' => 'Diretorias', 'pk' => 'IdDiretoria', 'fields' => ['SiglaDiretoria', 'NomeDiretoria']],
    'fontes' => ['table' => 'FontesRecursos', 'pk' => 'IdFonte', 'fields' => ['NomeFonte']],
    'categorias' => ['table' => 'CategoriaContrato', 'pk' => 'Id', 'fields' => ['Codigo', 'Descricao']],
    'modalidades' => ['table' => 'Modalidade', 'pk' => 'Id', 'fields' => ['Codigo', 'Descricao']],
    'tipos_doc' => ['table' => 'TiposDocumentos', 'pk' => 'Id', 'fields' => ['Nome']],
    'coordenacoes' => ['table' => 'contratos_coordenacoes', 'pk' => 'Id', 'fields' => ['Nome', 'Responsavel', 'Email']]
];

if (!isset($tables[$tab])) {
    header("Location: settings.php");
    exit;
}

$current = $tables[$tab];
$table = $current['table'];
$pk = $current['pk'];

try {
    if ($action === 'create' || $action === 'update') {
        $data = [];
        foreach ($current['fields'] as $field) {
            $data[$field] = $_POST[$field] ?? null;
        }

        if ($action === 'create') {
            $cols = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $stmt = $pdo->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
            $stmt->execute($data);
            logSistema('Create Settings', $table, $pdo->lastInsertId(), json_encode($data));
        } else {
            $sets = [];
            foreach ($data as $key => $val) {
                $sets[] = "$key = :$key";
            }
            $stmt = $pdo->prepare("UPDATE $table SET " . implode(", ", $sets) . " WHERE $pk = :pk_value");
            $data['pk_value'] = $pk_value;
            $stmt->execute($data);
            logSistema('Update Settings', $table, $pk_value, json_encode($data));
        }
    } elseif ($action === 'delete' && $pk_value) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
        $stmt->execute([$pk_value]);
        logSistema('Delete Settings', $table, $pk_value);
    }

    header("Location: settings.php?tab=$tab&msg=success");
    exit;

} catch (PDOException $e) {
    die("Erro ao processar alteração: " . $e->getMessage());
}
?>
