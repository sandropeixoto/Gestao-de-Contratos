<?php
// app-contratos/sidebar_content.php
// Este arquivo é incluído tanto na sidebar desktop quanto no drawer mobile
$text_class = $is_collapsed ? 'hidden' : 'block';
$item_justify = $is_collapsed ? 'justify-center' : 'justify-start';
?>

<!-- Logo Area -->
<div class="px-6 py-10 border-b border-white/5 shrink-0 h-24 flex items-center <?php echo $item_justify; ?>">
    <div class="flex items-center gap-3">
        <i class="ph-fill ph-files text-primary <?php echo $is_collapsed ? 'text-3xl' : 'text-4xl'; ?>"></i> 
        <h1 class="text-2xl font-black tracking-tighter <?php echo $text_class; ?>">
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-white to-white/60">GestorGov</span>
        </h1>
    </div>
</div>

<!-- Menu Principal -->
<nav class="flex-1 mt-4 overflow-y-auto overflow-x-hidden p-3 custom-scrollbar">
    <ul class="menu menu-md w-full gap-1 p-0">
        <li class="menu-title text-white/20 text-[10px] uppercase font-bold tracking-widest px-4 mb-2 <?php echo $text_class; ?>">Principal</li>
        
        <li>
            <a href="index.php" class="p-3 rounded-xl flex items-center gap-4 transition-all <?php echo isActive('index.php', $current_page); ?> <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Dashboard"' : ''; ?>>
                <i class="ph ph-squares-four text-2xl shrink-0"></i> 
                <span class="tracking-tight whitespace-nowrap <?php echo $text_class; ?>">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="contratos.php" class="p-3 rounded-xl flex items-center gap-4 transition-all <?php echo isActive('contratos.php', $current_page); ?> <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Contratos"' : ''; ?>>
                <i class="ph ph-folder-open text-2xl shrink-0"></i> 
                <span class="tracking-tight whitespace-nowrap <?php echo $text_class; ?>">Contratos</span>
            </a>
        </li>
        
        <?php if (CONTRATOS_GESTOR): ?>
        <li class="menu-title text-white/20 text-[10px] uppercase font-bold tracking-widest px-4 mt-6 mb-2 <?php echo $text_class; ?>">Administração</li>
        
        <li>
            <a href="prestadores.php" class="p-3 rounded-xl flex items-center gap-4 transition-all <?php echo isActive('prestadores.php', $current_page); ?> <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Fornecedores"' : ''; ?>>
                <i class="ph ph-buildings text-2xl shrink-0"></i> 
                <span class="tracking-tight whitespace-nowrap <?php echo $text_class; ?>">Fornecedores</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (CONTRATOS_ADMIN): ?>
        <li>
            <a href="settings.php" class="p-3 rounded-xl flex items-center gap-4 transition-all <?php echo isActive('settings.php', $current_page); ?> <?php echo $is_collapsed ? 'justify-center tooltip tooltip-right' : ''; ?>" <?php echo $is_collapsed ? 'data-tip="Configurações"' : ''; ?>>
                <i class="ph ph-gear text-2xl shrink-0"></i> 
                <span class="tracking-tight whitespace-nowrap <?php echo $text_class; ?>">Configurações</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- User Footer -->
<div class="p-4 border-t border-white/5 bg-black/20 shrink-0">
    <div class="flex items-center gap-3 <?php echo $item_justify; ?>">
        <div class="avatar online shadow-lg shrink-0">
            <div class="w-10 rounded-xl ring ring-primary ring-offset-base-100 ring-offset-1">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($_SESSION['user_name'] ?? 'User'); ?>" />
            </div>
        </div>
        <div class="overflow-hidden <?php echo $text_class; ?>">
            <p class="font-bold text-xs truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></p>
            <p class="text-[9px] uppercase font-black opacity-30">
                <?php 
                if (CONTRATOS_ADMIN) echo 'Administrador';
                elseif (CONTRATOS_GESTOR) echo 'Gestor';
                elseif (CONTRATOS_CONSULTOR) echo 'Consultor';
                else echo 'Leitor';
                ?>
            </p>
        </div>
    </div>
</div>
