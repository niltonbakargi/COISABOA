<?php
/**
 * COISABOA - Template do Cabeçalho
 * @version 1.0.0
 */

// Configurações padrão da página
$page_title = $page_title ?? 'COISABOA - Sistema Comercial';
$current_page = $current_page ?? 'dashboard';
$body_class = $body_class ?? '';

// Verificar se há mensagens flash
$flash_messages = getFlashMessages();

// Determinar classe do body baseada na página atual
$body_class .= ' page-' . $current_page;

// Verificar se está em ambiente de desenvolvimento
$is_dev = is_dev_environment();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <!-- Meta Tags para SEO -->
    <meta name="description" content="Sistema COISABOA - Controle de compras, vendas e estoque para pequenos negócios">
    <meta name="keywords" content="estoque, compras, vendas, controle, negócio, pequena empresa">
    <meta name="author" content="Sistema COISABOA">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/assets/manifest.json">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <link rel="stylesheet" href="/assets/css/responsive-tables.css">
    
    <!-- CSS específico da página -->
    <?php if (file_exists(ASSETS_PATH . '/css/' . $current_page . '.css')): ?>
        <link rel="stylesheet" href="/assets/css/<?= $current_page ?>.css">
    <?php endif; ?>
    
    <!-- Fontes (opcional - pode ser adicionado depois) -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> -->
    
    <?php if ($is_dev): ?>
        <!-- Indicador de ambiente de desenvolvimento -->
        <style>
            body::before {
                content: 'DEV';
                position: fixed;
                top: 10px;
                right: 10px;
                background: #ef4444;
                color: white;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
                z-index: 10000;
                pointer-events: none;
            }
        </style>
    <?php endif; ?>
</head>
<body class="<?= trim($body_class) ?>">

<!-- Flash Messages -->
<?php if (!empty($flash_messages)): ?>
    <div class="flash-messages">
        <?php foreach ($flash_messages as $msg): ?>
            <div class="flash-message flash-<?= $msg['tipo'] ?> fade-in">
                <span class="flash-text"><?= $msg['texto'] ?></span>
                <button class="flash-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay hidden">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Carregando...</p>
    </div>
</div>

<!-- Header Principal -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <div class="logo">
                <div class="logo-icon">📱</div>
                <h1 class="logo-text">COISABOA</h1>
            </div>
            
            <!-- Menu de Navegação -->
            <nav class="main-nav" id="mainNav">
                <ul class="nav-list">
                    <li class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                        <a href="/" class="nav-link">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item <?= $current_page === 'compras' ? 'active' : '' ?>">
                        <a href="/modules/purchases/comprei.php" class="nav-link">
                            <span class="nav-icon">🛒</span>
                            Compras
                        </a>
                    </li>
                    <li class="nav-item <?= $current_page === 'vendas' ? 'active' : '' ?>">
                        <a href="/modules/sales/vendi.php" class="nav-link">
                            <span class="nav-icon">💰</span>
                            Vendas
                        </a>
                    </li>
                    <li class="nav-item <?= $current_page === 'estoque' ? 'active' : '' ?>">
                        <a href="/modules/inventory/estoque.php" class="nav-link">
                            <span class="nav-icon">📊</span>
                            Estoque
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Menu Mobile Toggle -->
            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- Conteúdo Principal -->
<main class="main-content">
