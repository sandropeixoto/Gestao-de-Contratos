<?php
// app-contratos/prestador_form.php
require_once 'config.php';
require_once 'header.php';

if (!CONTRATOS_CONSULTOR) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$id = $_GET['id'] ?? null;
$prestador = null;
$contatos = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM Prestador WHERE Id = ?");
    $stmt->execute([$id]);
    $prestador = $stmt->fetch();
    
    if (!$prestador) {
        header("Location: prestadores.php");
        exit;
    }

    $stmt_c = $pdo->prepare("SELECT * FROM prestador_contatos WHERE PrestadorId = ? ORDER BY Id ASC");
    $stmt_c->execute([$id]);
    $contatos = $stmt_c->fetchAll();
}
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-base-content"><?php echo $id ? 'Editar Fornecedor' : 'Novo Fornecedor'; ?></h2>
            <p class="text-base-content/60">Preencha as informações detalhadas do prestador de serviço.</p>
        </div>
        <a href="prestadores.php" class="btn btn-ghost gap-2">
            <i class="ph ph-arrow-left"></i> Voltar para Listagem
        </a>
    </div>

    <form action="prestadores_action.php" method="POST" class="space-y-6" id="prestadorForm">
        <input type="hidden" name="action" value="<?php echo $id ? 'update' : 'create'; ?>">
        <?php if ($id): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coluna da Esquerda: Dados e Endereço -->
            <div class="space-y-6">
                <!-- Identificação -->
                <div class="card bg-base-100 shadow-xl border border-base-200">
                    <div class="card-body">
                        <h3 class="font-bold text-lg border-b pb-2 mb-4 flex items-center gap-2">
                            <i class="ph ph-identification-card text-primary"></i> Identificação
                        </h3>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nome/Razão Social</span></label>
                            <input type="text" name="Nome" required class="input input-bordered" value="<?php echo htmlspecialchars($prestador['Nome'] ?? ''); ?>">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Tipo</span></label>
                                <select name="Tipo" id="tipo_pessoa" class="select select-bordered" onchange="toggleMask()">
                                    <option value="PJ" <?php echo ($prestador['Tipo'] ?? '') == 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                                    <option value="PF" <?php echo ($prestador['Tipo'] ?? '') == 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Documento</span></label>
                                <input type="text" name="CNPJ" id="documento" required class="input input-bordered" value="<?php echo htmlspecialchars($prestador['CNPJ'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="card bg-base-100 shadow-xl border border-base-200">
                    <div class="card-body">
                        <h3 class="font-bold text-lg border-b pb-2 mb-4 flex items-center gap-2">
                            <i class="ph ph-map-pin text-primary"></i> Localização
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="form-control col-span-1">
                                <label class="label"><span class="label-text font-semibold">CEP</span></label>
                                <input type="text" name="CEP" id="cep" class="input input-bordered" value="<?php echo htmlspecialchars($prestador['CEP'] ?? ''); ?>" onblur="buscarCEP(this.value)">
                            </div>
                            <div class="form-control col-span-2">
                                <label class="label"><span class="label-text font-semibold">Cidade</span></label>
                                <input type="text" name="Cidade" id="cidade" class="input input-bordered" value="<?php echo htmlspecialchars($prestador['Cidade'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="form-control col-span-3">
                                <label class="label"><span class="label-text font-semibold">Logradouro</span></label>
                                <input type="text" name="Logradouro" id="logradouro" class="input input-bordered" value="<?php echo htmlspecialchars($prestador['Logradouro'] ?? ''); ?>">
                            </div>
                            <div class="form-control col-span-1">
                                <label class="label"><span class="label-text font-semibold">Número</span></label>
                                <input type="text" name="Numero" class="input input-bordered" value="<?php echo htmlspecialchars($prestador['Numero'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="form-control col-span-2">
                                <label class="label"><span class="label-text font-semibold">Bairro</span></label>
                                <input type="text" name="Bairro" id="bairro" class="input input-bordered" value="<?php echo htmlspecialchars($prestador['Bairro'] ?? ''); ?>">
                            </div>
                            <div class="form-control col-span-1">
                                <label class="label"><span class="label-text font-semibold">UF</span></label>
                                <input type="text" name="UF" id="uf" maxlength="2" class="input input-bordered uppercase text-center" value="<?php echo htmlspecialchars($prestador['UF'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Complemento</span></label>
                            <input type="text" name="Complemento" class="input input-bordered" value="<?php echo htmlspecialchars($prestador['Complemento'] ?? ''); ?>" placeholder="Apto, Sala, Bloco...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna da Direita: Contatos -->
            <div class="space-y-6">
                <div class="card bg-base-100 shadow-xl border border-base-200">
                    <div class="card-body">
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <i class="ph ph-users text-primary"></i> Gestão de Contatos
                            </h3>
                            <button type="button" onclick="addContatoRow()" class="btn btn-sm btn-primary gap-1 shadow-md">
                                <i class="ph ph-plus-circle"></i> Adicionar
                            </button>
                        </div>

                        <div id="contatosList" class="space-y-4">
                            <?php if (empty($contatos)): ?>
                                <div class="contato-row p-4 bg-base-200/50 rounded-xl relative border border-base-300">
                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="flex gap-2">
                                            <div class="form-control flex-1">
                                                <select name="contato_tipo[]" class="select select-bordered select-sm">
                                                    <option value="Comercial">Comercial</option>
                                                    <option value="Financeiro">Financeiro</option>
                                                    <option value="Suporte">Suporte</option>
                                                    <option value="Gestão">Gestão</option>
                                                </select>
                                            </div>
                                            <div class="form-control flex-[2]">
                                                <input type="text" name="contato_nome[]" class="input input-bordered input-sm w-full" placeholder="Nome completo">
                                            </div>
                                        </div>
                                        <div class="form-control">
                                            <input type="email" name="contato_email[]" class="input input-bordered input-sm w-full" placeholder="email@exemplo.com">
                                        </div>
                                        <div class="form-control">
                                            <input type="text" name="contato_tel[]" class="input input-bordered input-sm w-full tel-mask" placeholder="(00) 00000-0000">
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($contatos as $c): ?>
                                    <div class="contato-row p-4 bg-base-200/50 rounded-xl relative border border-base-300">
                                        <button type="button" onclick="this.parentElement.remove()" class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2 text-white shadow-md"><i class="ph ph-x"></i></button>
                                        <div class="grid grid-cols-1 gap-3">
                                            <div class="flex gap-2">
                                                <div class="form-control flex-1">
                                                    <select name="contato_tipo[]" class="select select-bordered select-sm">
                                                        <option value="Comercial" <?php echo $c['Tipo'] == 'Comercial' ? 'selected' : ''; ?>>Comercial</option>
                                                        <option value="Financeiro" <?php echo $c['Tipo'] == 'Financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                                                        <option value="Suporte" <?php echo $c['Tipo'] == 'Suporte' ? 'selected' : ''; ?>>Suporte</option>
                                                        <option value="Gestão" <?php echo $c['Tipo'] == 'Gestão' ? 'selected' : ''; ?>>Gestão</option>
                                                    </select>
                                                </div>
                                                <div class="form-control flex-[2]">
                                                    <input type="text" name="contato_nome[]" class="input input-bordered input-sm w-full" value="<?php echo htmlspecialchars($c['Nome'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-control">
                                                <input type="email" name="contato_email[]" class="input input-bordered input-sm w-full" value="<?php echo htmlspecialchars($c['Email'] ?? ''); ?>">
                                            </div>
                                            <div class="form-control">
                                                <input type="text" name="contato_tel[]" class="input input-bordered input-sm w-full tel-mask" value="<?php echo htmlspecialchars($c['Telefone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-200/50 p-6 rounded-2xl flex justify-end gap-3 mt-8">
            <a href="prestadores.php" class="btn btn-ghost px-8">Cancelar</a>
            <button type="submit" class="btn btn-primary px-12 shadow-xl">
                <i class="ph ph-floppy-disk text-xl"></i> Salvar Cadastro
            </button>
        </div>
    </form>
</div>

<template id="contatoTemplate">
    <div class="contato-row p-4 bg-base-200/50 rounded-xl relative border border-base-300">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2 text-white shadow-md"><i class="ph ph-x"></i></button>
        <div class="grid grid-cols-1 gap-3">
            <div class="flex gap-2">
                <div class="form-control flex-1">
                    <select name="contato_tipo[]" class="select select-bordered select-sm">
                        <option value="Comercial">Comercial</option>
                        <option value="Financeiro">Financeiro</option>
                        <option value="Suporte">Suporte</option>
                        <option value="Gestão">Gestão</option>
                    </select>
                </div>
                <div class="form-control flex-[2]">
                    <input type="text" name="contato_nome[]" class="input input-bordered input-sm w-full" placeholder="Nome completo">
                </div>
            </div>
            <div class="form-control">
                <input type="email" name="contato_email[]" class="input input-bordered input-sm w-full" placeholder="email@exemplo.com">
            </div>
            <div class="form-control">
                <input type="text" name="contato_tel[]" class="input input-bordered input-sm w-full tel-mask" placeholder="(00) 00000-0000">
            </div>
        </div>
    </div>
</template>

<script>
$(document).ready(function(){
    $('#cep').mask('00000-000');
    $('.tel-mask').mask('(00) 00000-0000');
    toggleMask(false); // Mantém o valor atual se estiver editando
});

function toggleMask(clear = true) {
    const tipo = $('#tipo_pessoa').val();
    const doc = $('#documento');
    if(clear) doc.val('');
    
    if (tipo === 'PJ') {
        doc.mask('00.000.000/0000-00');
        doc.attr('placeholder', '00.000.000/0000-00');
    } else {
        doc.mask('000.000.000-00');
        doc.attr('placeholder', '000.000.000-00');
    }
}

function addContatoRow() {
    const template = document.getElementById('contatoTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('contatosList').appendChild(clone);
    $('.tel-mask').mask('(00) 00000-0000');
}

function buscarCEP(cep) {
    cep = cep.replace(/\D/g, '');
    if (cep.length !== 8) return;

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(res => res.json())
        .then(data => {
            if (!data.erro) {
                $('#logradouro').val(data.logradouro);
                $('#bairro').val(data.bairro);
                $('#cidade').val(data.localidade);
                $('#uf').val(data.uf);
            }
        });
}
</script>

<?php require_once 'footer.php'; ?>
