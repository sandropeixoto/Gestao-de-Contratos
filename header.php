<?php
// app-contratos/header.php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/auth_module.php';

// Controle de estado da Sidebar
if (isset($_GET['toggle_sidebar'])) {
    $_SESSION['sidebar_collapsed'] = !($_SESSION['sidebar_collapsed'] ?? false);
    header("Location: " . basename($_SERVER['PHP_SELF']));
    exit;
}

$is_collapsed = $_SESSION['sidebar_collapsed'] ?? false;
$sidebar_width = $is_collapsed ? 'w-20' : 'w-72';
$logo_display = $is_collapsed ? 'hidden' : 'block';

// Lógica para marcar link ativo
$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page, $current_page) {
    return $page === $current_page ? 'bg-white/10 text-white font-bold' : 'text-white/60 hover:bg-white/5 hover:text-white';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestorGov - Gestão de Contratos</title>
    <!-- DaisyUI & Tailwind (CSS estável) -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        /* Fix para evitar scroll horizontal indesejado */
        .main-wrapper { width: 100%; min-width: 0; }
    </style>
</head>
<body class="bg-base-200 min-h-screen flex overflow-hidden">

    <!-- Estrutura de Drawer para Mobile -->
    <div class="drawer lg:hidden">
        <input id="mobile-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-side z-50">
            <label for="mobile-drawer" class="drawer-overlay"></label>
            <aside class="w-72 min-h-full bg-[#0f172a] text-white p-4">
                <?php include 'sidebar_content.php'; ?>
            </aside>
        </div>
    </div>

    <!-- Layout Desktop (Flexbox) -->
    <!-- Sidebar Desktop -->
    <aside class="hidden lg:flex sidebar-transition flex-col bg-[#0f172a] text-white shrink-0 shadow-2xl z-40 <?php echo $sidebar_width; ?>">
        <?php include 'sidebar_content.php'; ?>
    </aside>

    <!-- Área de Conteúdo -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Topbar -->
        <header class="h-16 flex items-center justify-between px-4 md:px-8 shrink-0 glass-header z-30">
            <div class="flex items-center gap-4">
                <!-- Botão Toggle Sidebar (Desktop) -->
                <a href="?toggle_sidebar=1" class="btn btn-square btn-sm btn-ghost hidden lg:flex">
                    <i class="ph <?php echo $is_collapsed ? 'ph-caret-double-right' : 'ph-caret-double-left'; ?> text-lg text-base-content/70"></i>
                </a>

                <!-- Botão Menu (Mobile) -->
                <label for="mobile-drawer" class="btn btn-square btn-ghost lg:hidden">
                    <i class="ph ph-list text-2xl text-base-content"></i>
                </label>
                <!-- Breadcrumbs -->
                <div class="text-sm breadcrumbs hidden sm:block text-base-content/60 font-medium">
                    <ul>
                        <li><a href="../home.php" class="hover:text-primary transition-colors italic opacity-70">Painel Geral</a></li> 
                        <li><a href="index.php" class="hover:text-primary transition-colors">Início</a></li> 
                        <li>Módulo de Contratos</li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Notificações -->
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle btn-sm">
                        <div class="indicator">
                            <i class="ph ph-bell text-xl text-base-content/70"></i>
                            <span class="badge badge-sm badge-error indicator-item font-bold text-[9px]">3</span>
                        </div>
                    </div>
                    <ul tabindex="0" class="mt-3 z-[100] p-2 shadow-2xl menu menu-sm dropdown-content bg-base-100 rounded-xl w-64 border border-base-200 text-base-content">
                        <li class="menu-title font-bold text-[10px] uppercase opacity-50 px-4 py-2">Alertas</li>
                        <li><a class="py-3">Contratos a vencer</a></li>
                    </ul>
                </div>

                <!-- Perfil -->
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2 pl-1 border border-base-300 rounded-full">
                        <div class="avatar">
                            <div class="w-7 rounded-full">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Sandro" />
                            </div>
                        </div>
                        <i class="ph ph-caret-down text-[10px] opacity-50 hidden md:block"></i>
                    </div>
                    <ul tabindex="0" class="mt-3 z-[100] p-2 shadow-2xl menu menu-sm dropdown-content bg-base-100 rounded-xl w-52 border border-base-200 text-base-content">
                        <li><a class="py-3"><i class="ph ph-user"></i> Meu Perfil</a></li>
                        <div class="divider my-1"></div>
                        <li><a href="#" class="text-error"><i class="ph ph-sign-out"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Viewport Principal -->
        <main class="flex-1 overflow-auto p-4 md:p-8 bg-[#f8fafc]">
