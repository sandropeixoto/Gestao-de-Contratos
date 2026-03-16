<?php
// app-contratos/contract_view.php
require_once 'config.php';
require_once 'header.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: contratos.php");
    exit;
}

try {
    // Busca Contrato Principal com nomes de tabelas auxiliares e Vigência Efetiva
    $stmt = $pdo->prepare("
        SELECT c.*, p.Nome as PrestadorNome, p.CNPJ as PrestadorCNPJ, p.Email as PrestadorEmail,
               m.Descricao as ModalidadeNome, cat.Descricao as CategoriaNome, f.NomeFonte as FonteNome,
               d.SiglaDiretoria as DiretoriaSigla,
               coord.Nome as CoordenacaoNome, coord.Responsavel as CoordenacaoResponsavel, coord.Email as CoordenacaoEmail,
               GREATEST(c.VigenciaFim, COALESCE((SELECT MAX(VigenciaFim) FROM Contratos WHERE PaiId = c.Id), '0000-00-00')) as VigenciaEfetiva
        FROM Contratos c
        LEFT JOIN Prestador p ON c.PrestadorId = p.Id
        LEFT JOIN Modalidade m ON c.ModalidadeId = m.Id
        LEFT JOIN CategoriaContrato cat ON c.CategoriaContratoId = cat.Id
        LEFT JOIN FontesRecursos f ON c.FonteRecursosId = f.IdFonte
        LEFT JOIN Diretorias d ON c.DiretoriaId = d.IdDiretoria
        LEFT JOIN contratos_coordenacoes coord ON c.CoordenacaoId = coord.Id
        WHERE c.Id = ?
    ");
    $stmt->execute([$id]);
    $contract = $stmt->fetch();

    if (!$contract) {
        die("Contrato não encontrado.");
    }

    $is_tac = ($contract['PaiId'] ?? 0) > 0;
    $main_contract_id = $is_tac ? $contract['PaiId'] : $id;

    // Se for o contrato principal, busca os termos vinculados
    $terms = [];
    if (!$is_tac) {
        try {
            $stmt_terms = $pdo->prepare("
                SELECT c.*, COALESCE(td.Nome, 'Termo') as TipoNome
                FROM Contratos c
                LEFT JOIN TiposDocumentos td ON c.TipoDocumentoId = td.Id
                WHERE c.PaiId = ?
                ORDER BY c.DataAssinatura ASC, c.Id ASC
            ");
            $stmt_terms->execute([$id]);
            $terms = $stmt_terms->fetchAll();
        } catch (PDOException $e) {
            $stmt_terms = $pdo->prepare("SELECT *, 'Termo' as TipoNome FROM Contratos WHERE PaiId = ? ORDER BY Id ASC");
            $stmt_terms->execute([$id]);
            $terms = $stmt_terms->fetchAll();
        }
    } else {
        // Se for um TAC, busca os dados básicos do pai para exibir no header
        $stmt_parent = $pdo->prepare("SELECT SeqContrato, AnoContrato FROM Contratos WHERE Id = ?");
        $stmt_parent->execute([$contract['PaiId']]);
        $parent_info = $stmt_parent->fetch();
    }

    // Cálculo do Valor Total (Contrato + Aditivos) - Apenas se for principal
    $total_value = $contract['ValorGlobalContrato'];
    if (!$is_tac) {
        foreach($terms as $t) {
            $total_value += $t['ValorGlobalContrato'];
        }
    }

    // Busca Categorias de Anexos
    $stmt_cat = $pdo->prepare("SELECT * FROM contratos_anexos_categorias ORDER BY descricao ASC");
    $stmt_cat->execute();
    $anexo_categories = $stmt_cat->fetchAll();

    // Busca Anexos existentes
    $stmt_anexos = $pdo->prepare("
        SELECT a.*, c.descricao as CategoriaNome, c.abreviacao as CategoriaAbrev, u.nome as UsuarioNome
        FROM contratos_anexos a
        JOIN contratos_anexos_categorias c ON a.categoria_id = c.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.contrato_id = ?
        ORDER BY a.data_upload DESC
    ");
    $stmt_anexos->execute([$id]);
    $attachments = $stmt_anexos->fetchAll();

    // Busca Fiscais Setoriais
    $stmt_fs = $pdo->prepare("SELECT * FROM contratos_fiscais_setoriais WHERE contrato_id = ? ORDER BY id ASC");
    $stmt_fs->execute([$id]);
    $fiscais_setoriais = $stmt_fs->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar dossiê: " . $e->getMessage());
}

// Lógica de Status
$vencimento = new DateTime($contract['VigenciaEfetiva']);
$hoje = new DateTime();
$diff = $hoje->diff($vencimento);
$is_expired = ($vencimento < $hoje);
$is_warning = (!$is_expired && $diff->days <= 30);

// Alertas de Sucesso/Erro
$success_upload = $_GET['success_upload'] ?? null;
$success_delete_anexo = $_GET['success_delete'] ?? null;
$error_code = $_GET['error'] ?? null;

// Mapa de Erros
$error_map = [
    'no_files_selected' => 'Nenhum arquivo foi selecionado para upload.',
    'unauthorized' => 'Você não tem permissão para realizar esta ação.',
    'db' => 'Ocorreu um erro no banco de dados ao processar a solicitação.',
    'invalid_contract' => 'ID de contrato inválido ou não encontrado.'
];
?>

<div class="space-y-6">
    <?php if ($success_upload): ?>
        <div class="alert alert-success shadow-lg">
            <i class="ph ph-check-circle text-2xl"></i>
            <span><?php echo (int)$success_upload; ?> arquivo(s) anexado(s) com sucesso!</span>
        </div>
    <?php endif; ?>

    <?php if ($error_code): ?>
        <div class="alert alert-error shadow-lg">
            <i class="ph ph-warning-circle text-2xl"></i>
            <span><?php echo $error_map[$error_code] ?? "Erro inesperado: " . htmlspecialchars($error_code); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success_delete_anexo): ?>
        <div class="alert alert-success shadow-lg">
            <i class="ph ph-trash text-2xl"></i>
            <span>Anexo removido com sucesso.</span>
        </div>
    <?php endif; ?>

    <!-- Header de Ações -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo $is_tac ? 'contract_view.php?id='.$contract['PaiId'] : 'contratos.php'; ?>" class="btn btn-circle btn-ghost" title="Voltar">
                <i class="ph ph-arrow-left text-2xl"></i>
            </a>
            <div>
                <?php if ($is_tac): ?>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="badge badge-primary badge-sm font-bold uppercase text-[9px]">Termo Aditivo (TAC)</span>
                        <a href="contract_view.php?id=<?php echo $contract['PaiId']; ?>" class="text-xs link link-primary font-bold hover:opacity-70 transition-opacity">
                            Vínculo: Contrato Principal <?php echo ($parent_info['SeqContrato'] ?? '') . '/' . ($parent_info['AnoContrato'] ?? ''); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <h2 class="text-3xl font-bold">
                    <?php echo $is_tac ? 'Termo N. '.$contract['SeqContrato'] : 'Contrato ' . ($contract['SeqContrato'] ?? '') . '/' . ($contract['AnoContrato'] ?? ''); ?>
                </h2>
                <div class="flex items-center gap-2 mt-1">
                    <?php if ($is_expired): ?>
                        <span class="badge badge-error gap-1 text-white font-bold"><i class="ph ph-x-circle"></i> Vencido</span>
                    <?php elseif ($is_warning): ?>
                        <span class="badge badge-warning gap-1 font-bold"><i class="ph ph-warning"></i> Vence em <?php echo $diff->days; ?> dias</span>
                    <?php else: ?>
                        <span class="badge badge-success text-white gap-1 font-bold"><i class="ph ph-check-circle"></i> Vigente</span>
                    <?php endif; ?>
                    <span class="text-base-content/50 text-sm">• Fornecedor: <?php echo htmlspecialchars($contract['PrestadorNome'] ?? ''); ?></span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <?php if ($is_tac): ?>
                <a href="contract_view.php?id=<?php echo $contract['PaiId']; ?>" class="btn btn-outline btn-primary gap-2">
                    <i class="ph ph-arrow-u-up-left"></i> Contrato Principal
                </a>
            <?php endif; ?>

            <?php if (CONTRATOS_CONSULTOR): ?>
            <a href="contract_form.php?id=<?php echo $id; ?>" class="btn btn-outline btn-info gap-2">
                <i class="ph ph-pencil-simple"></i> Editar <?php echo $is_tac ? 'Termo' : 'Contrato'; ?>
            </a>
            <?php endif; ?>

            <?php if (CONTRATOS_GESTOR): ?>
            <button onclick="confirmDelete(<?php echo $id; ?>, '<?php echo $is_tac ? 'Termo N. '.$contract['SeqContrato'] : ($contract['SeqContrato'] ?? '') . '/' . ($contract['AnoContrato'] ?? ''); ?>')" class="btn btn-outline btn-error gap-2">
                <i class="ph ph-trash"></i> Excluir
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Coluna Principal (Informações e Termos) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card de Resumo Principal -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body">
                    <h3 class="font-bold text-lg border-b pb-2 mb-4"><?php echo $is_tac ? 'Justificativa do Termo' : 'Resumo do Objeto'; ?></h3>
                    <p class="text-base-content/80 leading-relaxed italic">
                        "<?php echo nl2br(htmlspecialchars($contract['Objeto'] ?? '')); ?>"
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="stat p-0">
                            <div class="stat-title text-xs uppercase font-bold">Vigência Efetiva</div>
                            <div class="stat-value text-2xl text-primary"><?php echo isset($contract['VigenciaEfetiva']) ? date('d/m/Y', strtotime($contract['VigenciaEfetiva'])) : 'N/A'; ?></div>
                            <div class="stat-desc font-medium">Início: <?php echo isset($contract['VigenciaInicio']) ? date('d/m/Y', strtotime($contract['VigenciaInicio'])) : 'N/A'; ?></div>
                        </div>
                        <div class="stat p-0">
                            <div class="stat-title text-xs uppercase font-bold">Valor Mensal <?php echo $is_tac ? 'do Termo' : 'Original'; ?></div>
                            <div class="stat-value text-2xl">R$ <?php echo number_format($contract['ValorMensalContrato'] ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="stat p-0">
                            <div class="stat-title text-xs uppercase font-bold text-secondary"><?php echo $is_tac ? 'Valor Global do Termo' : 'Valor Total Acumulado'; ?></div>
                            <div class="stat-value text-2xl text-secondary">R$ <?php echo number_format($is_tac ? $contract['ValorGlobalContrato'] : $total_value, 2, ',', '.'); ?></div>
                            <?php if (!$is_tac): ?>
                                <div class="stat-desc font-bold text-secondary/70">Contrato + Aditivos</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Seção Ver Mais -->
                    <div class="collapse collapse-arrow mt-6 bg-base-200/50 rounded-lg">
                        <input type="checkbox" /> 
                        <div class="collapse-title text-sm font-bold flex items-center gap-2">
                            <i class="ph ph-plus-circle text-primary"></i> Detalhes Administrativos e Orçamentários
                        </div>
                        <div class="collapse-content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                                <!-- Coluna 1: Gestão -->
                                <div class="space-y-4">
                                    <h4 class="text-[10px] uppercase font-bold opacity-40 border-b pb-1">Gestão e Fiscalização</h4>
                                    <div class="space-y-2">
                                        <p class="text-sm"><strong>Diretoria:</strong> <?php echo htmlspecialchars($contract['DiretoriaSigla'] ?? 'Não informada'); ?></p>
                                        <p class="text-sm"><strong>Coordenador da área:</strong> <?php echo htmlspecialchars($contract['CoordenacaoNome'] ?? 'Não informada'); ?></p>
                                        <p class="text-sm"><strong>Fiscal Titular:</strong> <?php echo htmlspecialchars($contract['FiscalContrato'] ?? 'N/A'); ?></p>
                                        <p class="text-sm"><strong>Fiscal Substituto:</strong> <?php echo htmlspecialchars($contract['FiscalSubstituto'] ?? 'N/A'); ?></p>
                                        <?php if ($contract['NumeroDiarioOficialContrato']): ?>
                                            <p class="text-sm"><strong>Diário Oficial:</strong> <?php echo htmlspecialchars($contract['NumeroDiarioOficialContrato']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Coluna 2: Execução -->
                                <div class="space-y-4">
                                    <h4 class="text-[10px] uppercase font-bold opacity-40 border-b pb-1">Execução Orçamentária</h4>
                                    <div class="space-y-2">
                                        <p class="text-sm"><strong>Nº Processo:</strong> <?php echo htmlspecialchars($contract['NProcesso'] ?? 'N/A'); ?></p>
                                        <p class="text-sm"><strong>Fundamentação:</strong> <?php echo htmlspecialchars($contract['FundamentacaoLegal'] ?? 'N/A'); ?></p>
                                        <p class="text-sm"><strong>Programa Trab.:</strong> <?php echo htmlspecialchars($contract['ProgramaTrabalho'] ?? 'N/A'); ?></p>
                                        <p class="text-sm"><strong>Funcional Prog.:</strong> <?php echo htmlspecialchars($contract['FuncionalProgramatica'] ?? 'N/A'); ?></p>
                                        <p class="text-sm"><strong>Nat. Despesa:</strong> <?php echo htmlspecialchars($contract['NaturezaDespesa'] ?? 'N/A'); ?></p>
                                        <p class="text-sm"><strong>Fonte Recursos:</strong> <?php echo htmlspecialchars($contract['FonteNome'] ?? ''); ?> (<?php echo htmlspecialchars($contract['FonteRecursos'] ?? ''); ?>)</p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($contract['Observacao']): ?>
                                <div class="mt-6 p-4 bg-white/50 rounded-lg border border-base-300">
                                    <h4 class="text-[10px] uppercase font-bold opacity-40 mb-2">Observações</h4>
                                    <p class="text-sm italic leading-relaxed"><?php echo nl2br(htmlspecialchars($contract['Observacao'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($fiscais_setoriais)): ?>
                            <div class="mt-6 border-t border-base-300 pt-4">
                                <h4 class="text-xs font-bold uppercase opacity-50 mb-3 flex items-center gap-2">
                                    <i class="ph ph-users-three"></i> Fiscais Setoriais Vinculados
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($fiscais_setoriais as $fs): ?>
                                        <div class="bg-white/50 p-3 rounded-lg border border-base-300 flex items-center justify-between">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold"><?php echo htmlspecialchars($fs['nome']); ?></span>
                                                <span class="text-[10px] opacity-60 font-mono"><?php echo htmlspecialchars($fs['email'] ?? '-'); ?></span>
                                            </div>
                                            <i class="ph ph-user-circle text-primary text-xl opacity-30"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Listagem de Termos Vinculados (Apenas no Contrato Principal) -->
            <?php if (!$is_tac): ?>
            <div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
                <div class="p-6 border-b border-base-200 flex justify-between items-center bg-base-50">
                    <h3 class="text-xl font-bold flex items-center gap-2">
                        <i class="ph ph-stack text-primary"></i> Termos e Aditivos
                    </h3>
                    <?php if (CONTRATOS_CONSULTOR): ?>
                    <a href="contract_form.php?parent_id=<?php echo $id; ?>" class="btn btn-primary btn-sm gap-2">
                        <i class="ph ph-plus-circle"></i> Adicionar Termo
                    </a>
                    <?php endif; ?>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr class="bg-base-200/50">
                                <th>Tipo</th>
                                <th>Nº</th>
                                <th>Data Assinatura</th>
                                <th>Nova Vigência</th>
                                <th class="text-right">Valor</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($terms as $t): ?>
                                <tr class="hover group cursor-pointer transition-colors" onclick="window.location='contract_view.php?id=<?php echo $t['Id']; ?>'">
                                    <td><span class="badge badge-outline badge-sm uppercase font-bold text-[10px]"><?php echo htmlspecialchars($t['TipoNome'] ?? ''); ?></span></td>
                                    <td class="font-bold text-primary"><?php echo htmlspecialchars($t['SeqContrato'] ?? ''); ?></td>
                                    <td><?php echo isset($t['DataAssinatura']) ? date('d/m/Y', strtotime($t['DataAssinatura'])) : 'N/A'; ?></td>
                                    <td class="text-primary font-medium"><?php echo isset($t['VigenciaFim']) ? date('d/m/Y', strtotime($t['VigenciaFim'])) : 'N/A'; ?></td>
                                    <td class="text-right font-semibold">R$ <?php echo number_format($t['ValorGlobalContrato'] ?? 0, 2, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="contract_view.php?id=<?php echo $t['Id']; ?>" onclick="event.stopPropagation()" class="btn btn-square btn-sm btn-ghost text-primary" title="Ver Dossiê do Termo"><i class="ph ph-eye text-lg"></i></a>
                                            
                                            <?php if (CONTRATOS_CONSULTOR): ?>
                                            <a href="contract_form.php?id=<?php echo $t['Id']; ?>" onclick="event.stopPropagation()" class="btn btn-square btn-sm btn-ghost text-info" title="Editar"><i class="ph ph-pencil-simple text-lg"></i></a>
                                            <?php endif; ?>

                                            <?php if (CONTRATOS_GESTOR): ?>
                                            <button onclick="event.stopPropagation(); confirmDelete(<?php echo $t['Id']; ?>, '<?php echo ($t['SeqContrato'] ?? '') . '/' . ($t['AnoContrato'] ?? ''); ?>')" class="btn btn-square btn-sm btn-ghost text-error" title="Excluir"><i class="ph ph-trash text-lg"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($terms)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-12 opacity-50 italic">Nenhum termo ou aditivo vinculado a este contrato.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Listagem de Documentos Anexos (Suporta TAC e Contrato Principal) -->
            <div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
                <div class="p-6 border-b border-base-200 flex justify-between items-center bg-base-50">
                    <h3 class="text-xl font-bold flex items-center gap-2">
                        <i class="ph ph-files text-primary"></i> Documentos Anexos
                    </h3>
                    <?php if (CONTRATOS_CONSULTOR): ?>
                    <button onclick="upload_modal.showModal()" class="btn btn-primary btn-sm gap-2">
                        <i class="ph ph-upload-simple"></i> Novo Anexo
                    </button>
                    <?php endif; ?>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr class="bg-base-200/50">
                                <th>Arquivo</th>
                                <th>Categoria</th>
                                <th>Descrição / Observação</th>
                                <th>Usuário</th>
                                <th>Data</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attachments as $a): ?>
                                <tr class="hover group">
                                    <td class="max-w-xs truncate">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-file-pdf text-error text-xl"></i>
                                            <span class="font-medium" title="<?php echo htmlspecialchars($a['nome_arquivo_original']); ?>">
                                                <?php echo htmlspecialchars($a['nome_arquivo_original']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-sm badge-outline"><?php echo htmlspecialchars($a['CategoriaNome']); ?></span></td>
                                    <td class="text-xs">
                                        <?php if ($a['descricao']): ?>
                                            <div class="flex items-start gap-2 text-info bg-info/5 p-2 rounded border border-info/10">
                                                <i class="ph ph-chat-centered-text mt-0.5"></i>
                                                <span><?php echo htmlspecialchars($a['descricao']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="opacity-30 italic">- Nenhuma descrição -</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-xs"><?php echo htmlspecialchars($a['UsuarioNome'] ?? 'N/A'); ?></td>
                                    <td class="text-xs whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($a['data_upload'])); ?></td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-1">
                                            <a href="<?php echo htmlspecialchars($a['caminho_arquivo']); ?>" target="_blank" class="btn btn-square btn-sm btn-ghost text-primary" title="Ver/Baixar">
                                                <i class="ph ph-download-simple text-lg"></i>
                                            </a>
                                            <?php if (CONTRATOS_GESTOR): ?>
                                            <button onclick="confirmDeleteAnexo(<?php echo $a['id']; ?>, '<?php echo htmlspecialchars($a['nome_arquivo_original']); ?>')" class="btn btn-square btn-sm btn-ghost text-error" title="Excluir">
                                                <i class="ph ph-trash text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($attachments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 opacity-50 italic">Nenhum documento anexado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Coluna Lateral (Dossiê do Fornecedor e Atalhos) -->
        <div class="space-y-6">
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body">
                    <h3 class="card-title text-sm uppercase opacity-50"><i class="ph ph-buildings"></i> Dados do Fornecedor</h3>
                    <p class="font-bold text-lg mt-2"><?php echo htmlspecialchars($contract['PrestadorNome'] ?? ''); ?></p>
                    <p class="text-sm font-mono opacity-70"><?php echo htmlspecialchars($contract['PrestadorCNPJ'] ?? ''); ?></p>
                    
                    <div class="divider my-2"></div>
                    
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-2 text-sm">
                            <i class="ph ph-envelope text-primary"></i>
                            <span><?php echo htmlspecialchars($contract['PrestadorEmail'] ?? 'E-mail não cadastrado'); ?></span>
                        </div>
                        <a href="prestadores.php?id=<?php echo $contract['PrestadorId']; ?>" class="btn btn-ghost btn-xs text-primary justify-start px-0">
                            <i class="ph ph-arrow-square-out"></i> Ver cadastro completo
                        </a>
                    </div>
                </div>
            </div>

            <div class="card bg-primary text-primary-content shadow-xl">
                <div class="card-body">
                    <h3 class="card-title"><i class="ph ph-info"></i> Notas do Sistema</h3>
                    <p class="text-sm opacity-90">Este dossiê consolida todas as informações desde a assinatura original até o último termo de apostilamento ou aditivo registrado.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão (Reutilizado) -->
<dialog id="delete_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg text-error flex items-center gap-2"><i class="ph ph-warning"></i> Confirmar Exclusão</h3>
    <p class="py-4">Deseja excluir o documento <span id="del_name" class="font-bold"></span>?</p>
    <div class="modal-action">
      <form method="POST" action="contracts_action.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="del_id">
        <input type="hidden" name="redirect" value="contract_view.php?id=<?php echo $id; ?>">
        <button type="submit" class="btn btn-error text-white">Sim, Excluir</button>
        <button type="button" class="btn" onclick="delete_modal.close()">Cancelar</button>
      </form>
    </div>
  </div>
</dialog>

<!-- Modal de Upload de Anexos -->
<dialog id="upload_modal" class="modal">
  <div class="modal-box max-w-2xl">
    <h3 class="font-bold text-lg flex items-center gap-2"><i class="ph ph-upload-simple text-primary"></i> Novo(s) Anexo(s)</h3>
    <p class="text-sm opacity-60 mb-6">Selecione um ou mais arquivos para anexar a este contrato.</p>
    
    <form action="contratos_anexos_action.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="contrato_id" value="<?php echo $id; ?>">
        <input type="hidden" name="ano_contrato" value="<?php echo $contract['AnoContrato']; ?>">
        <input type="hidden" name="seq_contrato" value="<?php echo $contract['SeqContrato']; ?>">

        <div id="upload-rows" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-base-200/50 rounded-xl relative border border-base-300">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold uppercase text-[10px] opacity-60">Arquivo</span></label>
                    <input type="file" name="anexos[]" required class="file-input file-input-bordered file-input-sm w-full" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold uppercase text-[10px] opacity-60">Categoria</span></label>
                    <select name="categorias[]" required class="select select-bordered select-sm w-full">
                        <option value="">Selecione...</option>
                        <?php foreach($anexo_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['descricao']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text font-bold uppercase text-[10px] opacity-60">Descrição (Opcional)</span></label>
                    <input type="text" name="descricoes[]" placeholder="Breve resumo do conteúdo..." class="input input-bordered input-sm w-full" />
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6">
            <button type="button" onclick="addUploadRow()" class="btn btn-ghost btn-sm text-primary gap-2">
                <i class="ph ph-plus-circle"></i> Adicionar mais arquivos
            </button>
            <div class="modal-action mt-0">
                <button type="submit" class="btn btn-primary text-white px-8">Iniciar Upload</button>
                <button type="button" class="btn" onclick="upload_modal.close()">Cancelar</button>
            </div>
        </div>
    </form>
  </div>
</dialog>

<!-- Modal de Exclusão de Anexo -->
<dialog id="delete_anexo_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg text-error flex items-center gap-2"><i class="ph ph-warning"></i> Confirmar Exclusão de Anexo</h3>
    <p class="py-4">Deseja excluir permanentemente o arquivo <span id="anexo_del_name" class="font-bold"></span>?</p>
    <div class="modal-action">
      <form method="POST" action="contratos_anexos_action.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="anexo_del_id">
        <input type="hidden" name="contrato_id" value="<?php echo $id; ?>">
        <button type="submit" class="btn btn-error text-white">Sim, Excluir</button>
        <button type="button" class="btn" onclick="delete_anexo_modal.close()">Cancelar</button>
      </form>
    </div>
  </div>
</dialog>

<script>
function confirmDelete(id, name) {
    document.getElementById('del_id').value = id;
    document.getElementById('del_name').innerText = name;
    delete_modal.showModal();
}

function confirmDeleteAnexo(id, name) {
    document.getElementById('anexo_del_id').value = id;
    document.getElementById('anexo_del_name').innerText = name;
    delete_anexo_modal.showModal();
}

function addUploadRow() {
    const container = document.getElementById('upload-rows');
    const firstRow = container.querySelector('div');
    const newRow = firstRow.cloneNode(true);
    
    // Limpa os valores
    newRow.querySelectorAll('input, select').forEach(el => el.value = '');
    
    // Adiciona botão de remover
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-circle btn-xs btn-error absolute -top-2 -right-2 shadow-lg';
    removeBtn.innerHTML = '<i class="ph ph-x"></i>';
    removeBtn.onclick = function() { newRow.remove(); };
    newRow.appendChild(removeBtn);
    
    container.appendChild(newRow);
}
</script>

<?php require_once 'footer.php'; ?>
