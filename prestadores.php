<?php
// app-contratos/prestadores.php
require_once 'config.php';
require_once 'header.php';

// Bloqueio de leitura
if (!CONTRATOS_LEITOR) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Parâmetros de Busca e Paginação
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Query de contagem total para paginação
    $sql_count = "SELECT COUNT(*) FROM Prestador WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sql_count .= " AND (Nome LIKE ? OR CNPJ LIKE ? OR Cidade LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Query de dados com limite e offset
    $sql = "SELECT * FROM Prestador WHERE 1=1";
    if (!empty($search)) {
        $sql .= " AND (Nome LIKE ? OR CNPJ LIKE ? OR Cidade LIKE ?)";
    }
    $sql .= " ORDER BY Nome ASC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $prestadores = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erro: " . $e->getMessage();
}
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-base-content">Gestão de Fornecedores</h2>
            <p class="text-base-content/60">Visualize e gerencie a base de prestadores de serviço.</p>
        </div>
        <div class="flex gap-2">
            <?php if (CONTRATOS_CONSULTOR): ?>
            <a href="prestador_form.php" class="btn btn-primary gap-2 shadow-lg">
                <i class="ph ph-plus-circle text-xl"></i> Novo Fornecedor
            </a>
            <?php endif; ?>
            <?php if (CONTRATOS_ADMIN): ?>
            <a href="settings.php" class="btn btn-ghost gap-2">
                <i class="ph ph-gear text-xl"></i> Configurações
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="card bg-base-100 shadow-md border border-base-200">
        <div class="card-body p-4">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="join w-full">
                    <input type="text" name="search" placeholder="Buscar por nome, documento ou cidade..." class="input input-bordered join-item w-full" value="<?php echo htmlspecialchars($search); ?>" />
                    <button type="submit" class="btn btn-primary join-item px-8">
                        <i class="ph ph-magnifying-glass"></i> <span class="hidden md:inline">Pesquisar</span>
                    </button>
                </div>
                <?php if (!empty($search)): ?>
                    <a href="prestadores.php" class="btn btn-ghost">Limpar Filtros</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'error_has_contracts'): ?>
            <div class="alert alert-error text-white shadow-lg">
                <i class="ph ph-warning-circle text-xl"></i>
                <span>Não é possível excluir este fornecedor pois ele possui contratos atrelados.</span>
            </div>
        <?php elseif ($_GET['msg'] === 'success'): ?>
            <div class="alert alert-success text-white shadow-lg">
                <i class="ph ph-check-circle text-xl"></i>
                <span>Operação realizada com sucesso!</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Tabela de Listagem -->
    <div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="w-16">Tipo</th>
                        <th>Fornecedor / Razão Social</th>
                        <th>CNPJ / CPF</th>
                        <th>Localização</th>
                        <th class="text-right w-32">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestadores as $p): ?>
                        <tr class="hover group">
                            <td>
                                <div class="badge <?php echo $p['Tipo'] == 'PJ' ? 'badge-primary' : 'badge-secondary'; ?> badge-outline font-bold text-[10px]"><?php echo $p['Tipo']; ?></div>
                            </td>
                            <td>
                                <button onclick="viewPrestador(<?php echo $p['Id']; ?>)" class="text-left hover:underline">
                                    <div class="font-bold text-primary"><?php echo htmlspecialchars($p['Nome'] ?? ''); ?></div>
                                    <div class="text-[10px] opacity-50"><?php echo htmlspecialchars($p['Email'] ?? ''); ?></div>
                                </button>
                            </td>
                            <td class="text-sm font-mono"><?php echo htmlspecialchars($p['CNPJ'] ?? ''); ?></td>
                            <td>
                                <div class="text-sm"><?php echo htmlspecialchars($p['Cidade'] ?? '') . ' - ' . htmlspecialchars($p['UF'] ?? ''); ?></div>
                                <div class="text-[10px] opacity-50 italic"><?php echo htmlspecialchars($p['Bairro'] ?? ''); ?></div>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <?php if (CONTRATOS_CONSULTOR): ?>
                                    <a href="prestador_form.php?id=<?php echo $p['Id']; ?>" class="btn btn-square btn-sm btn-ghost text-info" title="Editar">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </a>
                                    <?php endif; ?>

                                    <?php if (CONTRATOS_GESTOR): ?>
                                    <button onclick="confirmDelete(<?php echo $p['Id']; ?>, '<?php echo htmlspecialchars($p['Nome'] ?? ''); ?>')" class="btn btn-square btn-sm btn-ghost text-error" title="Excluir">
                                        <i class="ph ph-trash text-lg"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($prestadores)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <i class="ph ph-ghost text-5xl opacity-10"></i>
                                <p class="mt-2 opacity-50 italic">Nenhum fornecedor encontrado.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
        <div class="p-4 bg-base-200/30 flex justify-center">
            <div class="join">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="join-item btn btn-sm <?php echo $page === $i ? 'btn-primary' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Visualização Detalhada -->
<dialog id="view_modal" class="modal">
  <div class="modal-box w-11/12 max-w-4xl p-0 overflow-hidden border border-base-300 shadow-2xl">
    <div class="bg-primary p-6 text-white flex justify-between items-center">
        <div>
            <h3 class="font-bold text-2xl" id="v_nome">Carregando...</h3>
            <div class="flex gap-2 mt-1">
                <span class="badge badge-outline border-white text-white font-bold" id="v_tipo">-</span>
                <span class="text-sm opacity-80" id="v_doc"></span>
            </div>
        </div>
        <form method="dialog">
            <button class="btn btn-circle btn-ghost"><i class="ph ph-x text-2xl"></i></button>
        </form>
    </div>
    
    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 bg-base-100">
        <!-- Coluna 1: Localização -->
        <div class="space-y-6">
            <section>
                <h4 class="text-xs font-bold uppercase tracking-wider text-base-content/40 mb-3 border-b pb-1 flex items-center gap-2">
                    <i class="ph ph-map-pin"></i> Endereço Completo
                </h4>
                <div class="space-y-2">
                    <p class="text-lg font-semibold" id="v_logradouro">-</p>
                    <p class="text-sm opacity-70">
                        <span id="v_bairro"></span><span id="v_cidade_uf"></span>
                    </p>
                    <p class="text-xs opacity-50">CEP: <span id="v_cep"></span> | Complemento: <span id="v_complemento"></span></p>
                </div>
            </section>

            <div class="divider"></div>

            <div class="flex gap-2">
                <?php if (CONTRATOS_CONSULTOR): ?>
                <a href="#" id="v_btn_edit" class="btn btn-info flex-1 gap-2 text-white shadow-lg">
                    <i class="ph ph-pencil-simple text-xl"></i> Editar Dados
                </a>
                <?php endif; ?>
                
                <?php if (CONTRATOS_GESTOR): ?>
                <button id="v_btn_delete" class="btn btn-error flex-1 gap-2 text-white shadow-lg">
                    <i class="ph ph-trash text-xl"></i> Excluir
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna 2: Contatos -->
        <div class="bg-base-200/50 rounded-2xl p-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-base-content/40 mb-4 flex items-center gap-2">
                <i class="ph ph-users"></i> Canais de Contato
            </h4>
            <div id="v_contatos" class="space-y-4">
                <!-- Populado via JS -->
            </div>
        </div>
    </div>
  </div>
</dialog>

<script>
function viewPrestador(id) {
    const modal = document.getElementById('view_modal');
    const contatosDiv = document.getElementById('v_contatos');
    
    // Reset modal
    document.getElementById('v_nome').innerText = 'Carregando...';
    contatosDiv.innerHTML = '<div class="flex flex-col items-center justify-center py-12 opacity-30"><span class="loading loading-spinner loading-lg"></span></div>';
    
    modal.showModal();

    fetch(`ajax_prestador_details.php?id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const p = res.data;
                document.getElementById('v_nome').innerText = p.Nome || 'Sem Nome';
                document.getElementById('v_tipo').innerText = p.Tipo || '-';
                document.getElementById('v_doc').innerText = p.CNPJ || '-';
                document.getElementById('v_logradouro').innerText = (p.Logradouro || '-') + (p.Numero ? ', ' + p.Numero : '');
                document.getElementById('v_bairro').innerText = p.Bairro || '';
                document.getElementById('v_cidade_uf').innerText = (p.Bairro ? ', ' : '') + (p.Cidade || '-') + ' / ' + (p.UF || '-');
                document.getElementById('v_cep').innerText = p.CEP || '-';
                document.getElementById('v_complemento').innerText = p.Complemento || 'N/A';

                // Botões de Ação na Modal
                const btnEdit = document.getElementById('v_btn_edit');
                if(btnEdit) btnEdit.href = `prestador_form.php?id=${p.Id}`;
                
                const btnDelete = document.getElementById('v_btn_delete');
                if(btnDelete) btnDelete.onclick = () => {
                    modal.close();
                    confirmDelete(p.Id, p.Nome);
                };

                // Contatos
                contatosDiv.innerHTML = '';
                if (res.contatos && res.contatos.length > 0) {
                    res.contatos.forEach(c => {
                        const row = document.createElement('div');
                        row.className = 'bg-white p-4 rounded-xl shadow-sm border border-base-300';
                        row.innerHTML = `
                            <div class="flex justify-between items-start mb-1">
                                <span class="badge badge-ghost badge-xs text-[9px] font-bold uppercase">${c.Tipo}</span>
                            </div>
                            <div class="font-bold text-sm">${c.Nome}</div>
                            <div class="text-[11px] opacity-70 flex items-center gap-1 mt-1">
                                <i class="ph ph-envelope"></i> ${c.Email || 'N/A'}
                            </div>
                            <div class="text-[11px] opacity-70 flex items-center gap-1">
                                <i class="ph ph-phone"></i> ${c.Telefone || 'N/A'}
                            </div>
                        `;
                        contatosDiv.appendChild(row);
                    });
                } else {
                    contatosDiv.innerHTML = '<div class="text-center py-12 opacity-30 italic text-sm">Nenhum contato cadastrado.</div>';
                }
            } else {
                alert('Erro ao carregar detalhes: ' + res.error);
                modal.close();
            }
        });
}
</script>

<dialog id="delete_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg text-error flex items-center gap-2"><i class="ph ph-warning"></i> Excluir Fornecedor</h3>
    <p class="py-4">Deseja excluir permanentemente <span id="del_name" class="font-bold text-error"></span>?</p>
    <div class="modal-action">
      <form method="POST" action="prestadores_action.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="del_id">
        <button type="submit" class="btn btn-error text-white">Sim, Excluir</button>
        <button type="button" class="btn" onclick="delete_modal.close()">Cancelar</button>
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
</script>

<?php require_once 'footer.php'; ?>
