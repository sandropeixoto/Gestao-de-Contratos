<?php
// app-contratos/sidebar_content.php
// Sistema de Contratos - Dashboard Moderno (Slate Theme)

$text_class = $is_collapsed ? 'hidden' : 'block';
$item_justify = $is_collapsed ? 'justify-center' : 'justify-start';
?>

<!-- Brand Section: Slate-950 (Destaque) -->
<div class="h-16 flex items-center bg-slate-900 px-6 shrink-0 border-b border-slate-800/50 <?php echo $item_justify; ?>">
    <div class="flex items-center gap-3">
        <div class="bg-blue-600 p-1.5 rounded-lg shadow-lg shadow-blue-500/20">
            <i class="ph-fill ph-files text-white <?php echo $is_collapsed ? 'text-xl' : 'text-2xl'; ?>"></i>
        </div>
        <h1 class="text-xl font-extrabold tracking-tight text-white <?php echo $text_class; ?>">
            Contratos
        </h1>
    </div>
</div>

<!-- Navigation Section: Slate-850 -->
<nav class="flex-1 flex flex-col mt-4 overflow-y-auto overflow-x-hidden custom-scrollbar">
    
    <!-- Seção: PRINCIPAL -->
    <div class="px-4 mb-6">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2 <?php echo $text_class; ?>">
            Principal
        </p>
        <ul class="space-y-1">
            <li>
                <a href="index.php" class="flex items-center gap-4 p-3 rounded-xl <?php echo isActive('index.php', $current_page); ?> group <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Dashboard"' : ''; ?>>
                    <i class="ph ph-squares-four text-2xl shrink-0 transition-transform group-hover:scale-110"></i>
                    <span class="text-sm font-medium whitespace-nowrap <?php echo $text_class; ?>">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="contratos.php" class="flex items-center gap-4 p-3 rounded-xl <?php echo isActive('contratos.php', $current_page); ?> group <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Contratos"' : ''; ?>>
                    <i class="ph ph-folder-open text-2xl shrink-0 transition-transform group-hover:scale-110"></i>
                    <span class="text-sm font-medium whitespace-nowrap <?php echo $text_class; ?>">Gestão de Contratos</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Seção: ADMINISTRATIVO -->
    <?php if (CONTRATOS_GESTOR): ?>
    <div class="px-4 mb-6">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2 <?php echo $text_class; ?>">
            Administrativo
        </p>
        <ul class="space-y-1">
            <li>
                <a href="prestadores.php" class="flex items-center gap-4 p-3 rounded-xl <?php echo isActive('prestadores.php', $current_page); ?> group <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Fornecedores"' : ''; ?>>
                    <i class="ph ph-buildings text-2xl shrink-0 transition-transform group-hover:scale-110"></i>
                    <span class="text-sm font-medium whitespace-nowrap <?php echo $text_class; ?>">Fornecedores</span>
                </a>
            </li>
            <?php if (CONTRATOS_ADMIN): ?>
            <li>
                <a href="settings.php" class="flex items-center gap-4 p-3 rounded-xl <?php echo isActive('settings.php', $current_page); ?> group <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Configurações"' : ''; ?>>
                    <i class="ph ph-gear text-2xl shrink-0 transition-transform group-hover:scale-110"></i>
                    <span class="text-sm font-medium whitespace-nowrap <?php echo $text_class; ?>">Configurações</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

</nav>

<!-- Footer Section: Control Button -->
<div class="p-4 bg-slate-900/50 border-t border-slate-800/50">
    <a href="?toggle_sidebar=1" class="flex items-center gap-4 p-3 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-all group <?php echo $is_collapsed ? 'justify-center' : ''; ?>">
        <i class="ph <?php echo $is_collapsed ? 'ph-caret-double-right' : 'ph-caret-double-left'; ?> text-xl shrink-0 transition-transform group-hover:scale-110"></i>
        <span class="text-sm font-medium whitespace-nowrap <?php echo $text_class; ?>">Recolher Menu</span>
    </a>
</div>
