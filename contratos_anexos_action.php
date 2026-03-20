<?php
// app-contratos/contratos_anexos_action.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_module.php';
require_once __DIR__ . '/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contratos.php");
    exit;
}

$action = $_POST['action'] ?? '';
$contrato_id = $_POST['contrato_id'] ?? 0;

if (!$contrato_id) {
    header("Location: contratos.php");
    exit;
}

// 1. UPLOAD DE ANEXOS
if ($action === 'upload') {
    if (!CONTRATOS_CONSULTOR) {
        header("Location: contract_view.php?id=$contrato_id&error=unauthorized");
        exit;
    }

    // Extensões permitidas (whitelist)
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'csv', 'zip'];

    $ano = $_POST['ano_contrato'] ?? date('Y');
    $seq = $_POST['seq_contrato'] ?? '000';

    // Caminho base: uploads/{ano}/{numero}/
    $upload_base = __DIR__ . "/uploads/$ano/$seq";
    if (!is_dir($upload_base)) {
        mkdir($upload_base, 0755, true);
    }

    $files = $_FILES['anexos'] ?? null;
    $categorias = $_POST['categorias'] ?? [];
    $descricoes = $_POST['descricoes'] ?? [];

    if (!$files || empty($files['name'][0])) {
        header("Location: contract_view.php?id=$contrato_id&error=no_files_selected");
        exit;
    }

    $success_count = 0;
    $error_messages = [];

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $error_messages[] = "Erro no arquivo " . $files['name'][$i] . ": Código " . $files['error'][$i];
            continue;
        }

        $tmp_name = $files['tmp_name'][$i];
        $original_name = $files['name'][$i];
        $categoria_id = $categorias[$i] ?? null;
        $descricao = $descricoes[$i] ?? '';

        if (!$categoria_id) {
            $error_messages[] = "Categoria não selecionada para o arquivo " . $original_name;
            continue;
        }

        // Busca abreviação da categoria
        $stmt_cat = $pdo->prepare("SELECT abreviacao FROM contratos_anexos_categorias WHERE id = ?");
        $stmt_cat->execute([$categoria_id]);
        $abrev = $stmt_cat->fetchColumn() ?: 'Anex';

        // Naming Convention: [nome-original-do-arquivo]+GGov+[Abrev]
        $path_info = pathinfo($original_name);
        $filename_only = $path_info['filename'];
        $extension = strtolower($path_info['extension'] ?? '');

        // Validação de extensão (whitelist)
        if (!in_array($extension, $allowed_extensions, true)) {
            $error_messages[] = "Tipo de arquivo não permitido: ." . $extension . " (" . $original_name . ")";
            continue;
        }

        // Sanitização básica do nome do arquivo
        $filename_only = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename_only);
        
        $new_filename = $filename_only . "+GGov+" . $abrev . "." . $extension;
        $dest_path = $upload_base . "/" . $new_filename;

        // Se o arquivo já existir, adiciona um sufixo para não sobrescrever
        $counter = 1;
        while (file_exists($dest_path)) {
            $new_filename = $filename_only . "+GGov+" . $abrev . "_" . $counter . "." . $extension;
            $dest_path = $upload_base . "/" . $new_filename;
            $counter++;
        }

        if (move_uploaded_file($tmp_name, $dest_path)) {
            // Salva no banco (caminho relativo para o front)
            $relative_path = "uploads/$ano/$seq/$new_filename";
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contratos_anexos 
                    (contrato_id, categoria_id, nome_arquivo_original, nome_arquivo_servidor, caminho_arquivo, descricao, usuario_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $contrato_id,
                    $categoria_id,
                    $original_name,
                    $new_filename,
                    $relative_path,
                    $descricao,
                    $_SESSION['user_id']
                ]);
                $anexo_id = $pdo->lastInsertId();
                $success_count++;

                logSistema('Upload', 'contratos_anexos', $anexo_id, json_encode([
                    'original_name' => $original_name,
                    'contrato_id' => $contrato_id
                ]));
            } catch (PDOException $e) {
                $error_messages[] = "Erro ao salvar no banco: " . $e->getMessage();
            }
        } else {
            $error_messages[] = "Falha ao mover arquivo: " . $original_name;
        }
    }

    header("Location: contract_view.php?id=$contrato_id&success_upload=$success_count");
    exit;
}

// 2. EXCLUSÃO DE ANEXO
if ($action === 'delete') {
    if (!CONTRATOS_GESTOR) {
        header("Location: contract_view.php?id=$contrato_id&error=unauthorized");
        exit;
    }

    $anexo_id = $_POST['id'] ?? 0;

    try {
        // Busca o caminho para deletar do disco
        $stmt_get = $pdo->prepare("SELECT caminho_arquivo FROM contratos_anexos WHERE id = ?");
        $stmt_get->execute([$anexo_id]);
        $path = $stmt_get->fetchColumn();

        if ($path) {
            $full_path = __DIR__ . "/" . $path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM contratos_anexos WHERE id = ?");
        $stmt->execute([$anexo_id]);

        logSistema('Delete', 'contratos_anexos', $anexo_id);

        header("Location: contract_view.php?id=$contrato_id&success_delete=1");
    } catch (PDOException $e) {
        header("Location: contract_view.php?id=$contrato_id&error=db_" . $e->getCode());
    }
    exit;
}

header("Location: contract_view.php?id=$contrato_id");
exit;
