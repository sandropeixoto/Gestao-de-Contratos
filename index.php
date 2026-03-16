<?php
// app-contratos/index.php
require_once 'config.php';
require_once 'header.php';

// Fetch metrics
try {
    // Total contracts (only main ones)
    $total_contracts = $pdo->query("SELECT COUNT(*) FROM Contratos WHERE PaiId = 0")->fetchColumn();
    
    // Total global value (including TACs)
    $total_value = $pdo->query("SELECT SUM(ValorGlobalContrato) FROM Contratos")->fetchColumn();
    
    // Active contracts (considering TACs)
    // A contract is active if its own expiration OR any of its TACs expiration is >= CURDATE()
    $active_contracts = $pdo->query("
        SELECT COUNT(DISTINCT c.Id) 
        FROM Contratos c 
        LEFT JOIN Contratos t ON t.PaiId = c.Id
        WHERE c.PaiId = 0 
        AND (c.VigenciaFim >= CURDATE() OR t.VigenciaFim >= CURDATE())
    ")->fetchColumn();
    
    // Expiring in 30 days (considering effective expiration)
    $expiring_soon = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT c.Id, GREATEST(c.VigenciaFim, COALESCE(MAX(t.VigenciaFim), '0000-00-00')) as VigenciaEfetiva
            FROM Contratos c
            LEFT JOIN Contratos t ON t.PaiId = c.Id
            WHERE c.PaiId = 0
            GROUP BY c.Id
        ) as V
        WHERE V.VigenciaEfetiva <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
        AND V.VigenciaEfetiva >= CURDATE()
    ")->fetchColumn();
    
    // Recent contracts (only main ones)
    $stmt = $pdo->prepare("
        SELECT c.*, p.Nome as PrestadorNome,
               GREATEST(c.VigenciaFim, COALESCE((SELECT MAX(VigenciaFim) FROM Contratos WHERE PaiId = c.Id), '0000-00-00')) as VigenciaEfetiva
        FROM Contratos c 
        LEFT JOIN Prestador p ON c.PrestadorId = p.Id 
        WHERE c.PaiId = 0
        ORDER BY c.Id DESC LIMIT 5
    ");
    $stmt->execute();
    $recent_contracts = $stmt->fetchAll();

    // Data for Analytics: Spending by Modality
    $analytics_stmt = $pdo->query("
        SELECT m.Descricao as label, SUM(c.ValorGlobalContrato) as total 
        FROM Contratos c 
        LEFT JOIN Modalidade m ON c.ModalidadeId = m.Id 
        GROUP BY c.ModalidadeId
        ORDER BY total DESC
    ");
    $analytics_data = $analytics_stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erro ao buscar dados: " . $e->getMessage();
}
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-base-content">Dashboard de Contratos</h2>
            <p class="text-base-content/60">Bem-vindo de volta! Aqui está o resumo do seu gerenciamento.</p>
        </div>
        <div class="flex gap-2">
            <a href="contract_form.php" class="btn btn-primary shadow-lg">
                <i class="ph ph-plus-circle text-xl"></i> Novo Contrato
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error shadow-lg">
            <i class="ph ph-warning-circle text-2xl"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="contratos.php?status=all" class="stats shadow-xl bg-base-100 overflow-hidden group hover:bg-base-200 transition-colors cursor-pointer">
            <div class="stat relative">
                <div class="stat-figure text-primary opacity-20 absolute -right-4 -bottom-4 transition-transform group-hover:scale-110">
                    <i class="ph ph-files text-8xl"></i>
                </div>
                <div class="stat-title font-medium uppercase tracking-wider text-xs">Total de Contratos</div>
                <div class="stat-value text-primary"><?php echo number_format($total_contracts, 0, ',', '.'); ?></div>
                <div class="stat-desc font-medium">Registrados no sistema</div>
            </div>
        </a>
        
        <div class="stats shadow-xl bg-base-100 overflow-hidden group">
            <div class="stat relative">
                <div class="stat-figure text-secondary opacity-20 absolute -right-4 -bottom-4 transition-transform group-hover:scale-110">
                    <i class="ph ph-currency-circle-dollar text-8xl"></i>
                </div>
                <div class="stat-title font-medium uppercase tracking-wider text-xs">Valor Global Acumulado</div>
                <div class="stat-value text-secondary text-2xl">R$ <?php echo number_format($total_value, 2, ',', '.'); ?></div>
                <div class="stat-desc font-medium">Investimento total</div>
            </div>
        </div>

        <a href="contratos.php?status=active" class="stats shadow-xl bg-base-100 overflow-hidden group hover:bg-base-200 transition-colors cursor-pointer">
            <div class="stat relative">
                <div class="stat-figure text-success opacity-20 absolute -right-4 -bottom-4 transition-transform group-hover:scale-110">
                    <i class="ph ph-check-circle text-8xl"></i>
                </div>
                <div class="stat-title font-medium uppercase tracking-wider text-xs">Contratos Vigentes</div>
                <div class="stat-value text-success"><?php echo number_format($active_contracts, 0, ',', '.'); ?></div>
                <div class="stat-desc font-medium">Ativos no momento</div>
            </div>
        </a>

        <a href="contratos.php?status=expiring&days=30" class="stats shadow-xl bg-base-100 overflow-hidden group hover:bg-base-200 transition-colors cursor-pointer">
            <div class="stat relative">
                <div class="stat-figure text-warning opacity-20 absolute -right-4 -bottom-4 transition-transform group-hover:scale-110">
                    <i class="ph ph-clock-countdown text-8xl"></i>
                </div>
                <div class="stat-title font-medium uppercase tracking-wider text-xs">A vencer (30 dias)</div>
                <div class="stat-value text-warning"><?php echo number_format($expiring_soon, 0, ',', '.'); ?></div>
                <div class="stat-desc font-medium text-warning font-bold italic">Atenção requerida</div>
            </div>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Analytics Card -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body">
                <h3 class="card-title flex items-center gap-2">
                    <i class="ph ph-chart-pie-slice text-primary"></i> Gastos por Modalidade
                </h3>
                <div class="relative h-[300px] w-full mt-4">
                    <canvas id="modalityChart"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    <?php 
                    $colors = ['#570df8', '#f000b8', '#37cdbe', '#3d4451', '#fbbd23', '#ef4f4f'];
                    foreach(array_slice($analytics_data, 0, 4) as $index => $item): 
                    ?>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" style="background-color: <?php echo $colors[$index % count($colors)]; ?>"></span>
                                <?php echo htmlspecialchars($item['label'] ?? 'Não Definida'); ?>
                            </span>
                            <span class="font-bold">R$ <?php echo number_format($item['total'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Contracts Table -->
        <div class="lg:col-span-2 card bg-base-100 shadow-xl overflow-hidden border border-base-200">
            <div class="p-6 border-b border-base-200 flex justify-between items-center bg-base-100/50">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i class="ph ph-list-bullets text-primary"></i> Contratos Recentes
                </h3>
                <a href="contratos.php" class="btn btn-ghost btn-sm text-primary">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th>Número/Ano</th>
                            <th>Objeto</th>
                            <th>Fornecedor</th>
                            <th>Vencimento</th>
                            <th class="text-right">Valor Global</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_contracts as $c): ?>
                        <tr class="hover">
                            <td class="font-bold text-primary">
                                <?php echo $c['SeqContrato'] . '/' . $c['AnoContrato']; ?>
                            </td>
                            <td class="max-w-xs truncate" title="<?php echo htmlspecialchars($c['Objeto']); ?>">
                                <?php echo htmlspecialchars($c['Objeto']); ?>
                            </td>
                            <td class="text-sm opacity-80">
                                <?php echo htmlspecialchars($c['PrestadorNome'] ?? 'Não informado'); ?>
                            </td>
                            <td>
                                <?php 
                                    $vencimento = new DateTime($c['VigenciaEfetiva']);
                                    $hoje = new DateTime();
                                    $diff = $hoje->diff($vencimento);
                                    $is_expired = ($vencimento < $hoje);
                                    $is_warning = (!$is_expired && $diff->days <= 30);
                                    
                                    $badge_class = "badge-ghost";
                                    if ($is_expired) $badge_class = "badge-error";
                                    elseif ($is_warning) $badge_class = "badge-warning";
                                ?>
                                <span class="badge <?php echo $badge_class; ?> font-medium">
                                    <?php echo $vencimento->format('d/m/Y'); ?>
                                </span>
                            </td>
                            <td class="text-right font-semibold">
                                R$ <?php echo number_format($c['ValorGlobalContrato'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_contracts)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 opacity-50 italic">Nenhum contrato encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shortcut Sidebar/Alerts -->
        <div class="space-y-6">
            <div class="card bg-primary text-primary-content shadow-xl">
                <div class="card-body">
                    <h3 class="card-title"><i class="ph ph-lightbulb"></i> Dica do Gestor</h3>
                    <p class="text-sm opacity-90">Mantenha a documentação de fiscalização sempre atualizada para evitar atrasos na renovação dos contratos.</p>
                    <div class="card-actions justify-end mt-4">
                        <button class="btn btn-sm btn-ghost bg-white/20 hover:bg-white/30 border-none text-white">Entendi</button>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="ph ph-bell-ringing text-warning"></i> Alertas Críticos
                    </h3>
                    <div class="space-y-4 mt-4">
                        <?php if ($expiring_soon > 0): ?>
                        <div class="flex items-start gap-3 p-3 bg-warning/10 rounded-lg border border-warning/20">
                            <i class="ph ph-warning text-warning text-2xl"></i>
                            <div>
                                <p class="font-bold text-sm">Contratos a vencer</p>
                                <p class="text-xs opacity-70"><?php echo $expiring_soon; ?> contrato(s) precisam de atenção imediata.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-start gap-3 p-3 bg-info/10 rounded-lg border border-info/20">
                            <i class="ph ph-info text-info text-2xl"></i>
                            <div>
                                <p class="font-bold text-sm">Backup do Sistema</p>
                                <p class="text-xs opacity-70">O último backup foi realizado hoje às 04:00 AM.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('modalityChart').getContext('2d');
    const data = {
        labels: <?php echo json_encode(array_column($analytics_data, 'label')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($analytics_data, 'total')); ?>,
            backgroundColor: ['#570df8', '#f000b8', '#37cdbe', '#3d4451', '#fbbd23', '#ef4f4f'],
            hoverOffset: 15,
            borderWidth: 0
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 12,
                    boxPadding: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) label += ': ';
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                            }
                            return label;
                        }
                    }
                }
            },
            cutout: '75%',
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
});
</script>
