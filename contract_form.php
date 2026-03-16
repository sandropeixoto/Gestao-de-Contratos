<?php
// app-contratos/contract_form.php
require_once 'config.php';
require_once 'header.php';

if (!CONTRATOS_CONSULTOR) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$id = $_GET['id'] ?? null;
$parentId = $_GET['parent_id'] ?? 0;
$contract = null;
$prestador_atual = null;
$fiscais_setoriais = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT c.*, p.CNPJ as PrestadorDoc, p.Nome as PrestadorNome 
                           FROM Contratos c 
                           LEFT JOIN Prestador p ON c.PrestadorId = p.Id 
                           WHERE c.Id = ?");
    $stmt->execute([$id]);
    $contract = $stmt->fetch();

    $stmt_fs = $pdo->prepare("SELECT * FROM contratos_fiscais_setoriais WHERE contrato_id = ? ORDER BY id ASC");
    $stmt_fs->execute([$id]);
    $fiscais_setoriais = $stmt_fs->fetchAll();
    
    $parentId = $contract['PaiId'] ?? 0;
} elseif ($parentId) {
    $stmt = $pdo->prepare("SELECT c.*, p.CNPJ as PrestadorDoc, p.Nome as PrestadorNome 
                           FROM Contratos c 
                           LEFT JOIN Prestador p ON c.PrestadorId = p.Id 
                           WHERE c.Id = ?");
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch();
    if ($parent) {
        $contract = $parent;
        $contract['Id'] = null;
        $contract['PaiId'] = $parentId;
        $contract['SeqContrato'] = '';
        $contract['AnoContrato'] = null;
        $contract['Objeto'] = '';
        $contract['VigenciaInicio'] = '';
        $contract['VigenciaFim'] = '';
        $contract['DataAssinatura'] = '';
        $contract['ValorMensalContrato'] = '';
        $contract['ValorGlobalContrato'] = '';
        $contract['NProcesso'] = '';
        $contract['FundamentacaoLegal'] = '';
        $contract['ProgramaTrabalho'] = '';
        $contract['FuncionalProgramatica'] = '';
        $contract['NaturezaDespesa'] = '';
        $contract['NumeroDiarioOficialContrato'] = '';
        $contract['Observacao'] = '';
    }
}

$is_tac = $parentId > 0;
$page_title = $id ? ($is_tac ? 'Editar Termo Aditivo' : 'Editar Contrato') : ($is_tac ? 'Novo Termo Aditivo' : 'Novo Contrato');

// Fetch Dropdowns (apenas se for contrato pai, exceto Fontes que o TAC usa)
$categories = $is_tac ? [] : $pdo->query("SELECT Id, Descricao FROM CategoriaContrato ORDER BY Descricao ASC")->fetchAll();
$modalidades = $is_tac ? [] : $pdo->query("SELECT Id, Descricao FROM Modalidade ORDER BY Descricao ASC")->fetchAll();
$diretorias = $is_tac ? [] : $pdo->query("SELECT IdDiretoria, NomeDiretoria, SiglaDiretoria FROM Diretorias ORDER BY NomeDiretoria ASC")->fetchAll();
$coordenacoes = $is_tac ? [] : $pdo->query("SELECT Id, Nome FROM contratos_coordenacoes ORDER BY Nome ASC")->fetchAll();
$fontes = $pdo->query("SELECT IdFonte, NomeFonte FROM FontesRecursos ORDER BY NomeFonte ASC")->fetchAll();
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-base-content"><?php echo $page_title; ?></h2>
            <p class="text-base-content/60"><?php echo $is_tac ? 'Vincular novo termo aditivo ao contrato principal.' : 'Preencha os campos abaixo.'; ?></p>
        </div>
        <a href="<?php echo $is_tac && !$id ? 'contract_view.php?id='.$parentId : 'contratos.php'; ?>" class="btn btn-ghost gap-2">
            <i class="ph ph-arrow-left"></i> Voltar
        </a>
    </div>

    <form action="contracts_action.php" method="POST" class="card bg-base-100 shadow-xl border border-base-200">
        <input type="hidden" name="action" value="<?php echo $id ? 'update' : 'create'; ?>">
        <input type="hidden" name="PaiId" value="<?php echo $parentId; ?>">
        <input type="hidden" name="TipoDocumentoId" value="<?php echo $is_tac ? 2 : 1; ?>">
        <?php if ($id): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <?php if ($is_tac): ?>
            <!-- Campos Herdados Ocultos para TAC (Integridade) -->
            <input type="hidden" name="PrestadorId" value="<?php echo $contract['PrestadorId'] ?? ''; ?>">
            <input type="hidden" name="DiretoriaId" value="<?php echo $contract['DiretoriaId'] ?? ''; ?>">
            <input type="hidden" name="CoordenacaoId" value="<?php echo $contract['CoordenacaoId'] ?? ''; ?>">
            <input type="hidden" name="CategoriaContratoId" value="<?php echo $contract['CategoriaContratoId'] ?? ''; ?>">
            <input type="hidden" name="ModalidadeId" value="<?php echo $contract['ModalidadeId'] ?? ''; ?>">
            <input type="hidden" name="FiscalContrato" value="<?php echo htmlspecialchars($contract['FiscalContrato'] ?? ''); ?>">
            <input type="hidden" name="EmailFiscal" value="<?php echo htmlspecialchars($contract['EmailFiscal'] ?? ''); ?>">
            <input type="hidden" name="AnoContrato" value="">
        <?php endif; ?>

        <div class="card-body space-y-8">
            
            <!-- SEÇÃO TAC (Apenas se for TAC) - Campos Estritamente Conforme Lista -->
            <?php if ($is_tac): ?>
                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-file-text text-primary"></i> Informações do Termo (TAC)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="form-control md:col-span-1">
                            <label class="label"><span class="label-text font-semibold">N. Tac</span></label>
                            <input type="number" name="SeqContrato" required class="input input-bordered" value="<?php echo htmlspecialchars($contract['SeqContrato'] ?? ''); ?>">
                        </div>
                        <div class="form-control md:col-span-3">
                            <label class="label"><span class="label-text font-semibold">Número do processo</span></label>
                            <input type="text" name="NProcesso" class="input input-bordered" value="<?php echo htmlspecialchars($contract['NProcesso'] ?? ''); ?>">
                        </div>
                        <div class="form-control md:col-span-4">
                            <label class="label"><span class="label-text font-semibold">Fundamentação Legal</span></label>
                            <input type="text" name="FundamentacaoLegal" class="input input-bordered" value="<?php echo htmlspecialchars($contract['FundamentacaoLegal'] ?? ''); ?>">
                        </div>
                        <div class="form-control md:col-span-4">
                            <label class="label"><span class="label-text font-semibold">Justificativa (Objeto)</span></label>
                            <textarea name="Objeto" required class="textarea textarea-bordered h-24"><?php echo htmlspecialchars($contract['Objeto'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-calendar text-primary"></i> Vigência e Valores
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Vigência Início</span></label>
                            <input type="date" name="VigenciaInicio" required class="input input-bordered" value="<?php echo $contract['VigenciaInicio'] ?? ''; ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Vigência Fim</span></label>
                            <input type="date" name="VigenciaFim" required class="input input-bordered" value="<?php echo $contract['VigenciaFim'] ?? ''; ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Data Assinatura</span></label>
                            <input type="date" name="DataAssinatura" required class="input input-bordered" value="<?php echo $contract['DataAssinatura'] ?? ''; ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Diário Oficial TAC</span></label>
                            <input type="text" name="NumeroDiarioOficialContrato" class="input input-bordered" value="<?php echo htmlspecialchars($contract['NumeroDiarioOficialContrato'] ?? ''); ?>">
                        </div>
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-semibold">Valor Mensal Contrato</span></label>
                            <input type="number" step="0.01" name="ValorMensalContrato" class="input input-bordered" value="<?php echo htmlspecialchars($contract['ValorMensalContrato'] ?? ''); ?>">
                        </div>
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-semibold">Valor Global Contrato</span></label>
                            <input type="number" step="0.01" name="ValorGlobalContrato" required class="input input-bordered" value="<?php echo htmlspecialchars($contract['ValorGlobalContrato'] ?? ''); ?>">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-coins text-primary"></i> Execução Orçamentária
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Programa Trabalho</span></label>
                            <input type="text" name="ProgramaTrabalho" class="input input-bordered" value="<?php echo htmlspecialchars($contract['ProgramaTrabalho'] ?? ''); ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Funcional Programática</span></label>
                            <input type="text" name="FuncionalProgramatica" class="input input-bordered" value="<?php echo htmlspecialchars($contract['FuncionalProgramatica'] ?? ''); ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Natureza da Despesa</span></label>
                            <input type="text" name="NaturezaDespesa" class="input input-bordered" value="<?php echo htmlspecialchars($contract['NaturezaDespesa'] ?? ''); ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Fonte de Recursos</span></label>
                            <select name="FonteRecursosId" class="select select-bordered w-full">
                                <option value="">Selecione...</option>
                                <?php foreach($fontes as $f): ?>
                                    <option value="<?php echo $f['IdFonte']; ?>" <?php echo ($contract['FonteRecursosId'] ?? '') == $f['IdFonte'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($f['NomeFonte'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-semibold">No Fonte Recursos</span></label>
                            <input type="text" name="FonteRecursos" class="input input-bordered" value="<?php echo htmlspecialchars($contract['FonteRecursos'] ?? ''); ?>" placeholder="Texto livre">
                        </div>
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-semibold">Observação</span></label>
                            <textarea name="Observacao" class="textarea textarea-bordered h-20"><?php echo htmlspecialchars($contract['Observacao'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </section>

            <!-- SEÇÃO CONTRATO ORIGINAL (Apenas se NÃO for TAC) -->
            <?php else: ?>
                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-info text-primary"></i> Informações Básicas
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="form-control md:col-span-1">
                            <label class="label"><span class="label-text font-semibold">Número</span></label>
                            <input type="number" name="SeqContrato" required class="input input-bordered" value="<?php echo htmlspecialchars($contract['SeqContrato'] ?? ''); ?>" placeholder="Ex: 45">
                        </div>
                        <div class="form-control md:col-span-1">
                            <label class="label"><span class="label-text font-semibold">Ano</span></label>
                            <input type="number" name="AnoContrato" required class="input input-bordered" value="<?php echo htmlspecialchars($contract['AnoContrato'] ?? date('Y')); ?>">
                        </div>
                        <div class="form-control md:col-span-4">
                            <label class="label"><span class="label-text font-semibold">Objeto</span></label>
                            <textarea name="Objeto" required class="textarea textarea-bordered h-24" placeholder="Descrição detalhada do contrato..."><?php echo htmlspecialchars($contract['Objeto'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-calendar text-primary"></i> Datas e Vigência
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Data Assinatura</span></label>
                            <input type="date" name="DataAssinatura" required class="input input-bordered" value="<?php echo $contract['DataAssinatura'] ?? ''; ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Início Vigência</span></label>
                            <input type="date" name="VigenciaInicio" required class="input input-bordered" value="<?php echo $contract['VigenciaInicio'] ?? ''; ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Fim Vigência</span></label>
                            <input type="date" name="VigenciaFim" required class="input input-bordered" value="<?php echo $contract['VigenciaFim'] ?? ''; ?>">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-briefcase text-primary"></i> Fornecedor e Valores
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Documento do Fornecedor (CPF/CNPJ)</span>
                                <a href="prestadores.php" target="_blank" class="label-text-alt link link-primary flex items-center gap-1">
                                    <i class="ph ph-plus-circle"></i> Novo Fornecedor
                                </a>
                            </label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" id="prestador_doc" class="input input-bordered w-full pr-10" placeholder="Digite o documento para buscar..." value="<?php echo htmlspecialchars($contract['PrestadorDoc'] ?? ''); ?>" onblur="buscarPrestador(this.value)">
                                    <div id="doc_loading" class="absolute right-3 top-3 hidden"><span class="loading loading-spinner loading-sm opacity-50"></span></div>
                                </div>
                                <input type="hidden" name="PrestadorId" id="PrestadorId" required value="<?php echo $contract['PrestadorId'] ?? ''; ?>">
                            </div>
                            <div id="prestador_info" class="mt-2 p-3 bg-base-200 rounded-lg border border-base-300 flex items-center gap-3 <?php echo isset($contract['PrestadorNome']) ? '' : 'hidden'; ?>">
                                <i class="ph ph-check-circle text-success text-xl"></i>
                                <div>
                                    <p class="text-xs uppercase font-bold opacity-50">Nome do Fornecedor:</p>
                                    <p id="prestador_nome" class="font-bold"><?php echo htmlspecialchars($contract['PrestadorNome'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Valor Mensal (R$)</span></label>
                            <input type="number" step="0.01" name="ValorMensalContrato" class="input input-bordered" value="<?php echo htmlspecialchars($contract['ValorMensalContrato'] ?? ''); ?>" placeholder="0.00">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Valor Global (R$)</span></label>
                            <input type="number" step="0.01" name="ValorGlobalContrato" required class="input input-bordered" value="<?php echo htmlspecialchars($contract['ValorGlobalContrato'] ?? ''); ?>" placeholder="0.00">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-user-focus text-primary"></i> Fiscalização e Gestão
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Diretoria Resp.</span></label>
                            <select name="DiretoriaId" class="select select-bordered w-full">
                                <option value="">Selecione...</option>
                                <?php foreach($diretorias as $d): ?>
                                    <option value="<?php echo $d['IdDiretoria']; ?>" <?php echo ($contract['DiretoriaId'] ?? '') == $d['IdDiretoria'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['SiglaDiretoria'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Coordenador da área</span></label>
                            <select name="CoordenacaoId" class="select select-bordered w-full">
                                <option value="">Selecione...</option>
                                <?php foreach($coordenacoes as $c): ?>
                                    <option value="<?php echo $c['Id']; ?>" <?php echo ($contract['CoordenacaoId'] ?? '') == $c['Id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['Nome'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Fiscal Titular</span></label>
                            <input type="text" name="FiscalContrato" class="input input-bordered w-full" value="<?php echo htmlspecialchars($contract['FiscalContrato'] ?? ''); ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">E-mail do Fiscal</span></label>
                            <input type="email" name="EmailFiscal" class="input input-bordered w-full" value="<?php echo htmlspecialchars($contract['EmailFiscal'] ?? ''); ?>">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-bold border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="ph ph-plus text-primary"></i> Outros Detalhes
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Categoria</span></label>
                            <select name="CategoriaContratoId" class="select select-bordered w-full">
                                <option value="">Selecione...</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['Id']; ?>" <?php echo ($contract['CategoriaContratoId'] ?? '') == $cat['Id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['Descricao'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Fonte de Recurso</span></label>
                            <select name="FonteRecursosId" class="select select-bordered w-full">
                                <option value="">Selecione...</option>
                                <?php foreach($fontes as $f): ?>
                                    <option value="<?php echo $f['IdFonte']; ?>" <?php echo ($contract['FonteRecursosId'] ?? '') == $f['IdFonte'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($f['NomeFonte'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Número do Processo</span></label>
                            <input type="text" name="NProcesso" class="input input-bordered" value="<?php echo htmlspecialchars($contract['NProcesso'] ?? ''); ?>">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Modalidade</span></label>
                            <select name="ModalidadeId" class="select select-bordered w-full">
                                <option value="">Selecione...</option>
                                <?php foreach($modalidades as $m): ?>
                                    <option value="<?php echo $m['Id']; ?>" <?php echo ($contract['ModalidadeId'] ?? '') == $m['Id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['Descricao'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Número da Modalidade</span></label>
                            <input type="text" name="NumeroModalidade" class="input input-bordered" value="<?php echo htmlspecialchars($contract['NumeroModalidade'] ?? ''); ?>" placeholder="Ex: 002/2021">
                        </div>
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-semibold">Observação</span></label>
                            <textarea name="Observacao" class="textarea textarea-bordered h-20"><?php echo htmlspecialchars($contract['Observacao'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <div class="card-footer p-8 bg-base-200/50 flex justify-end gap-3">
            <a href="<?php echo $is_tac && !$id ? 'contract_view.php?id='.$parentId : 'contratos.php'; ?>" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary px-8 shadow-lg">
                <i class="ph ph-floppy-disk text-xl"></i> Salvar <?php echo $is_tac ? 'Termo' : 'Contrato'; ?>
            </button>
        </div>
    </form>
</div>

<script>
function buscarPrestador(doc) {
    if (doc.length < 3) return;
    const loading = document.getElementById('doc_loading');
    const info = document.getElementById('prestador_info');
    const nome = document.getElementById('prestador_nome');
    const inputId = document.getElementById('PrestadorId');
    loading.classList.remove('hidden');
    fetch('ajax_prestador.php?doc=' + encodeURIComponent(doc))
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');
            if (data.success) {
                nome.innerText = data.data.Nome;
                inputId.value = data.data.Id;
                info.classList.remove('hidden');
                info.classList.add('flex');
            } else {
                nome.innerText = ''; inputId.value = ''; info.classList.add('hidden');
                alert('Fornecedor não encontrado.');
            }
        })
        .catch(error => { loading.classList.add('hidden'); console.error('Erro:', error); });
}
</script>

<?php require_once 'footer.php'; ?>
