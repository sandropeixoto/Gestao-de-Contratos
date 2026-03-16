<?php
// app-contratos/contratos.php
require_once 'config.php';
require_once 'header.php';

// Lógica de Persistência de Filtros
if (!isset($_SESSION['contratos_filters'])) {
    $_SESSION['contratos_filters'] = ['search' => '', 'status' => 'all', 'days' => '', 'ano' => ''];
}

// Se houver novos filtros via GET, atualiza a sessão
if (isset($_GET['search']) || isset($_GET['status']) || isset($_GET['ano'])) {
    $_SESSION['contratos_filters'] = [
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? 'all',
        'days'   => $_GET['days'] ?? '',
        'ano'    => $_GET['ano'] ?? ''
    ];
}

// Carrega filtros da sessão
$search = $_SESSION['contratos_filters']['search'];
$status = $_SESSION['contratos_filters']['status'];
$days   = $_SESSION['contratos_filters']['days'];
$ano    = $_SESSION['contratos_filters']['ano'];

// Limpar filtros se solicitado explicitamente
if (isset($_GET['clear'])) {
    $_SESSION['contratos_filters'] = ['search' => '', 'status' => 'all', 'days' => '', 'ano' => ''];
    header("Location: contratos.php");
    exit;
}

try {
    // Buscar anos distintos para o autocomplete
    $available_years = $pdo->query("SELECT DISTINCT AnoContrato FROM Contratos WHERE AnoContrato IS NOT NULL AND AnoContrato > 0 ORDER BY AnoContrato DESC")->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT c.*, p.Nome as PrestadorNome, m.Descricao as ModalidadeNome, td.Nome as TipoDocumentoNome,
                   GREATEST(c.VigenciaFim, COALESCE(t.MaxTacVigencia, '0000-00-00')) as VigenciaEfetiva
            FROM Contratos c 
            LEFT JOIN Prestador p ON c.PrestadorId = p.Id 
            LEFT JOIN Modalidade m ON c.ModalidadeId = m.Id
            LEFT JOIN TiposDocumentos td ON c.TipoDocumentoId = td.Id
            LEFT JOIN (
                SELECT PaiId, MAX(VigenciaFim) as MaxTacVigencia
                FROM Contratos
                WHERE PaiId > 0
                GROUP BY PaiId
            ) t ON c.Id = t.PaiId
            WHERE c.PaiId = 0";
    $params = [];

    if (!empty($ano)) {
        $sql .= " AND c.AnoContrato = ?";
        $params[] = intval($ano);
    }

    if (!empty($search)) {
        $sql .= " AND (c.SeqContrato LIKE ? OR c.Objeto LIKE ? OR p.Nome LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Lógica de Status e Vencimento
    if ($status === 'active') {
        $sql .= " HAVING VigenciaEfetiva >= CURDATE()";
    } elseif ($status === 'expired') {
        $sql .= " HAVING VigenciaEfetiva < CURDATE()";
    } elseif ($status === 'expiring' && !empty($days)) {
        $sql .= " HAVING VigenciaEfetiva <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND VigenciaEfetiva >= CURDATE()";
        $params[] = intval($days);
    }

    $sql .= " ORDER BY c.Id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contracts = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erro ao buscar contratos: " . $e->getMessage();
}
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-base-content">Lista de Contratos</h2>
            <p class="text-base-content/60">Gerencie todos os contratos registrados.</p>
        </div>
        <?php if (CONTRATOS_CONSULTOR): ?>
        <a href="contract_form.php" class="btn btn-primary shadow-lg">
            <i class="ph ph-plus-circle text-xl"></i> Novo Contrato
        </a>
        <?php endif; ?>
    </div>

    <!-- Filters & Search -->
    <div class="card bg-base-100 shadow-md border border-base-200">
        <div class="card-body p-4">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="form-control w-full md:w-32">
                    <label class="label"><span class="label-text font-semibold">Ano</span></label>
                    <select name="ano" class="select select-bordered w-full" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach($available_years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $ano == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control flex-1">
                    <label class="label"><span class="label-text font-semibold">Pesquisar</span></label>
                    <div class="join w-full">
                        <input type="text" name="search" placeholder="Número, objeto ou fornecedor..." 
                               class="input input-bordered join-item w-full" value="<?php echo htmlspecialchars($search ?? ''); ?>" />
                        <button type="submit" class="btn btn-primary join-item">
                            <i class="ph ph-magnifying-glass text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-control w-full md:w-48">
                    <label class="label"><span class="label-text font-semibold">Status</span></label>
                    <select name="status" class="select select-bordered w-full" onchange="if(this.value != 'expiring') { this.form.days.value=''; } this.form.submit()">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Vigentes</option>
                        <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Vencidos</option>
                        <option value="expiring" <?php echo $status === 'expiring' ? 'selected' : ''; ?>>Filtrar por Vencimento</option>
                    </select>
                    <input type="hidden" name="days" value="<?php echo htmlspecialchars($days ?? ''); ?>">
                </div>

                <div class="flex gap-2 mb-2 md:mb-0">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold text-primary">à vencer em</span></label>
                        <div class="flex gap-2">
                            <a href="contratos.php?status=expiring&days=30&ano=<?php echo $ano; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm <?php echo ($status==='expiring' && $days==='30') ? 'btn-primary' : 'btn-outline'; ?>">30d</a>
                            <a href="contratos.php?status=expiring&days=60&ano=<?php echo $ano; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm <?php echo ($status==='expiring' && $days==='60') ? 'btn-primary' : 'btn-outline'; ?>">60d</a>
                            <a href="contratos.php?status=expiring&days=90&ano=<?php echo $ano; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm <?php echo ($status==='expiring' && $days==='90') ? 'btn-primary' : 'btn-outline'; ?>">90d</a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($search) || $status !== 'all' || !empty($days) || !empty($ano)): ?>
                <div class="form-control">
                    <a href="contratos.php?clear=1" class="btn btn-ghost text-error">Limpar</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Indicador de Filtros Ativos -->
    <?php if (!empty($search) || $status !== 'all' || !empty($ano)): ?>
    <div class="flex flex-wrap items-center gap-2 px-1">
        <span class="text-xs font-bold uppercase text-base-content/50 mr-2">Filtros Ativos:</span>
        
        <?php if (!empty($ano)): ?>
            <div class="badge badge-primary badge-outline gap-2 py-3 px-4">
                <i class="ph ph-calendar"></i> Ano: <?php echo $ano; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($search)): ?>
            <div class="badge badge-primary badge-outline gap-2 py-3 px-4">
                <i class="ph ph-magnifying-glass"></i> Termo: "<?php echo htmlspecialchars($search ?? ''); ?>"
            </div>
        <?php endif; ?>

        <?php if ($status !== 'all'): ?>
            <div class="badge badge-primary badge-outline gap-2 py-3 px-4 capitalize">
                <i class="ph ph-funnel"></i> 
                <?php 
                    $status_label = $status;
                    if ($status === 'active') $status_label = 'Vigentes';
                    if ($status === 'expired') $status_label = 'Vencidos';
                    if ($status === 'expiring') $status_label = "A Vencer ({$days}d)";
                    echo $status_label;
                ?>
            </div>
        <?php endif; ?>

        <a href="contratos.php?clear=1" class="btn btn-ghost btn-xs text-error hover:bg-error/10">
            <i class="ph ph-x-circle"></i> Remover todos
        </a>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error shadow-lg">
            <i class="ph ph-warning-circle text-2xl"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Contracts Table -->
    <div class="card bg-base-100 shadow-xl overflow-hidden border border-base-200">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="w-16">ID</th>
                        <th>Número/Ano</th>
                        <th>Objeto</th>
                        <th>Fornecedor</th>
                        <th>Período</th>
                        <th>Status</th>
                        <th class="text-right">Valor Global</th>
                        <th class="text-center w-24">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($contracts as $c): ?>
                    <tr class="hover group cursor-pointer transition-colors" onclick="window.location='contract_view.php?id=<?php echo $c['Id']; ?>'">
                        <td class="text-xs opacity-50"><?php echo $c['Id']; ?></td>
                        <td class="font-bold text-primary">
                            <?php echo $c['SeqContrato'] . '/' . $c['AnoContrato']; ?>
                        </td>
                        <td class="max-w-xs overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($c['Objeto'] ?? ''); ?>">
                            <?php echo htmlspecialchars($c['Objeto'] ?? ''); ?>
                        </td>
                        <td class="text-sm">
                            <div class="font-medium"><?php echo htmlspecialchars($c['PrestadorNome'] ?? 'N/A'); ?></div>
                            <div class="text-[10px] flex flex-col gap-1 mt-1 opacity-70">
                                <?php if (!empty($c['ModalidadeNome'])): ?>
                                    <span class="flex items-center gap-1"><i class="ph ph-tag"></i> <?php echo htmlspecialchars($c['ModalidadeNome'] ?? ''); ?> (<?php echo htmlspecialchars($c['NumeroModalidade'] ?? 'S/N'); ?>)</span>
                                <?php endif; ?>
                                <?php if (!empty($c['NProcesso'])): ?>
                                    <span class="flex items-center gap-1"><i class="ph ph-stack"></i> Proc: <?php echo $c['NProcesso']; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-xs whitespace-nowrap">
                            <div class="flex flex-col">
                                <span>Ini: <?php echo date('d/m/Y', strtotime($c['VigenciaInicio'])); ?></span>
                                <span class="font-bold text-primary">Fim: <?php echo date('d/m/Y', strtotime($c['VigenciaEfetiva'])); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $vencimento = new DateTime($c['VigenciaEfetiva']);
                                $hoje = new DateTime();
                                $diff = $hoje->diff($vencimento);
                                $is_expired = ($vencimento < $hoje);
                                $is_warning = (!$is_expired && $diff->days <= 30);
                                
                                if ($is_expired) {
                                    echo '<span class="badge badge-error badge-sm">Vencido</span>';
                                } elseif ($is_warning) {
                                    echo '<span class="badge badge-warning badge-sm">Vence em ' . $diff->days . 'd</span>';
                                } else {
                                    echo '<span class="badge badge-success badge-sm text-white">Vigente</span>';
                                }
                            ?>
                        </td>
                        <td class="text-right font-semibold">
                            R$ <?php echo number_format($c['ValorGlobalContrato'], 2, ',', '.'); ?>
                        </td>
                        <td>
                            <div class="flex justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php if (CONTRATOS_CONSULTOR): ?>
                                <a href="contract_form.php?id=<?php echo $c['Id']; ?>" onclick="event.stopPropagation()" class="btn btn-square btn-sm btn-ghost text-info" title="Editar">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </a>
                                <?php endif; ?>

                                <?php if (CONTRATOS_GESTOR): ?>
                                <button onclick="event.stopPropagation(); confirmDelete(<?php echo $c['Id']; ?>, '<?php echo $c['SeqContrato'] . '/' . $c['AnoContrato']; ?>')" class="btn btn-square btn-sm btn-ghost text-error" title="Excluir">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (!CONTRATOS_CONSULTOR): ?>
                                <i class="ph ph-eye text-lg opacity-30" title="Apenas Visualização"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-12">
                                <i class="ph ph-folder-open text-6xl opacity-10 mb-2 block mx-auto"></i>
                                <p class="opacity-50 italic">Nenhum contrato encontrado com os filtros aplicados.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<dialog id="delete_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg text-error flex items-center gap-2">
        <i class="ph ph-warning"></i> Confirmar Exclusão
    </h3>
    <p class="py-4">Tem certeza que deseja excluir o contrato <span id="delete_contract_num" class="font-bold"></span>? Esta ação não pode ser desfeita.</p>
    <div class="modal-action">
      <form method="POST" action="contracts_action.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_contract_id">
        <button type="submit" class="btn btn-error text-white">Sim, Excluir</button>
        <button type="button" class="btn" onclick="delete_modal.close()">Cancelar</button>
      </form>
    </div>
  </div>
</dialog>

<script>
function confirmDelete(id, num) {
    document.getElementById('delete_contract_id').value = id;
    document.getElementById('delete_contract_num').innerText = num;
    delete_modal.showModal();
}
</script>

<?php require_once 'footer.php'; ?>
