<?php
// app-contratos/header.php
require_once __DIR__ . '/auth_module.php';

// Controle de estado da Sidebar (Toggle suave)
if (isset($_GET['toggle_sidebar'])) {
    $_SESSION['sidebar_collapsed'] = !($_SESSION['sidebar_collapsed'] ?? false);
    header("Location: " . basename($_SERVER['PHP_SELF']));
    exit;
}

$is_collapsed = $_SESSION['sidebar_collapsed'] ?? false;
$sidebar_width = $is_collapsed ? 'w-20' : 'w-64';
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Lógica para marcar link ativo com borda lateral azul
 * Conforme especificações: bg-slate-800, texto branco, borda lateral 4px #3b82f6
 */
function isActive($page, $current_page) {
    if ($page === $current_page) {
        return 'bg-slate-800 text-white border-l-4 border-blue-500 font-medium';
    }
    return 'text-slate-400 hover:bg-slate-800/50 hover:text-white transition-all duration-200';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratos - Dashboard Administrativo</title>
    
    <!-- Tailwind & DaisyUI (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        slate: {
                            850: '#1e293b',
                            950: '#0f172a',
                        },
                        brand: '#3b82f6' // Azul vibrante solicitado
                    }
                }
            }
        }
    </script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        /* Custom scrollbar para o menu */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
    </style>
</head>
<body class="bg-[#f8fafc] min-h-screen flex overflow-hidden">

    <!-- Mobile Drawer Overlay -->
    <div class="drawer lg:hidden">
        <input id="mobile-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-side z-50">
            <label for="mobile-drawer" class="drawer-overlay"></label>
            <aside class="w-64 min-h-full bg-slate-900 text-white flex flex-col">
                <?php include 'sidebar_content.php'; ?>
            </aside>
        </div>
    </div>

    <!-- Sidebar Desktop -->
    <aside class="hidden lg:flex sidebar-transition flex-col bg-[#1e293b] text-white fixed inset-y-0 left-0 z-40 <?php echo $sidebar_width; ?> shadow-xl">
        <?php include 'sidebar_content.php'; ?>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden sidebar-transition <?php echo $is_collapsed ? 'lg:pl-20' : 'lg:pl-64'; ?>">
        
        <!-- Topbar: Fixo, Branco, Borda inferior -->
        <header class="sticky top-0 h-16 flex items-center justify-between px-6 bg-white border-b border-gray-200 z-40 shrink-0">
            <div class="flex items-center gap-4">
                <!-- Mobile Toggle -->
                <label for="mobile-drawer" class="btn btn-square btn-ghost btn-sm lg:hidden text-gray-500">
                    <i class="ph ph-list text-2xl"></i>
                </label>
                
                <!-- Saudação Dinâmica -->
                <div class="hidden md:block">
                    <span class="text-gray-500 font-medium">Bem-vindo, <span class="text-gray-800 font-bold"><?php echo explode(' ', $_SESSION['user_name'] ?? 'Usuário')[0]; ?></span></span>
                </div>
            </div>

            <!-- Topbar Right Actions -->
            <div class="flex items-center gap-6">
                <!-- Notificações com Badge Circular -->
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="relative p-2 text-gray-400 hover:text-brand transition-colors cursor-pointer">
                        <i class="ph ph-bell text-2xl"></i>
                        <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white ring-2 ring-white">3</span>
                    </div>
                    <ul tabindex="0" class="dropdown-content mt-4 z-[100] w-80 rounded-xl bg-white p-2 shadow-2xl border border-gray-100 animate-in fade-in slide-in-from-top-2">
                        <li class="px-4 py-3 border-b border-gray-50 mb-2">
                            <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Notificações Recentes</span>
                        </li>
                        <li><a class="flex flex-col items-start gap-1 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <span class="text-sm font-semibold text-gray-800 italic">Contratos a vencer</span>
                            <span class="text-xs text-gray-400">3 contratos expiram em 30 dias</span>
                        </a></li>
                    </ul>
                </div>

                <!-- Bloco de Perfil -->
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="flex items-center gap-3 cursor-pointer hover:opacity-80 transition-opacity">
                        <div class="hidden sm:flex flex-col items-end">
                            <span class="text-sm font-bold text-gray-800 leading-tight"><?php echo $_SESSION['user_name'] ?? 'Usuário'; ?></span>
                            <span class="text-xs text-gray-400 font-medium">
                                <?php 
                                    if (CONTRATOS_ADMIN) echo 'Administrador';
                                    elseif (CONTRATOS_GESTOR) echo 'Gestor do Módulo';
                                    else echo 'Consultor Técnico';
                                ?>
                            </span>
                        </div>
                        <div class="relative">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($_SESSION['user_id'] ?? 'Sandro'); ?>" 
                                 class="h-10 w-10 rounded-full border-2 border-white shadow-sm ring-1 ring-gray-100" />
                            <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 border-2 border-white"></div>
                        </div>
                        <i class="ph ph-caret-down text-gray-300 text-xs transition-transform duration-200"></i>
                    </div>
                    
                    <!-- Dropdown Perfil (Card flutuante rounded-xl shadow-2xl) -->
                    <ul tabindex="0" class="dropdown-content mt-4 z-[100] w-56 rounded-xl bg-white p-2 shadow-2xl border border-gray-100 overflow-hidden">
                        <li><a class="flex items-center gap-3 p-3 text-gray-600 hover:bg-gray-50 hover:text-brand rounded-lg transition-all">
                            <i class="ph ph-user text-xl"></i> <span class="text-sm font-medium">Meu Perfil</span>
                        </a></li>
                        <li><a class="flex items-center gap-3 p-3 text-gray-600 hover:bg-gray-50 hover:text-brand rounded-lg transition-all">
                            <i class="ph ph-shield-check text-xl"></i> <span class="text-sm font-medium">Segurança</span>
                        </a></li>
                        <div class="h-px bg-gray-100 my-1 mx-2"></div>
                        <li><a href="#" class="flex items-center gap-3 p-3 text-red-500 hover:bg-red-50 rounded-lg transition-all">
                            <i class="ph ph-sign-out text-xl font-bold"></i> <span class="text-sm font-bold">Sair do Sistema</span>
                        </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Viewport Principal -->
        <main class="flex-1 overflow-auto p-6 md:p-10">
