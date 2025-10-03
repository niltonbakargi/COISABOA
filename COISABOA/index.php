<?php
/**
 * COISABOA - Sistema de Gestão Pessoal
 * Dashboard Principal - Otimizado para Mobile
 */

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Configuração do banco de dados
require_once 'config/database.php';

// Buscar dados do usuário
$pdo = getDB();
$usuario_stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
$usuario_stmt->execute([$_SESSION['usuario_id']]);
$usuario = $usuario_stmt->fetch();

// Buscar estatísticas
try {
    // Total de compras
    $compras_stmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(valor_total), 0) as valor_total FROM compras");
    $compras_stats = $compras_stmt->fetch();
    
    // Total de vendas
    $vendas_stmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(valor_vendido), 0) as valor_total FROM vendas");
    $vendas_stats = $vendas_stmt->fetch();
    
    // Total em estoque
    $estoque_stmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(quantidade), 0) as itens FROM estoque");
    $estoque_stats = $estoque_stmt->fetch();
    
    // Lucro total
    $lucro_total = $vendas_stats['valor_total'] - $compras_stats['valor_total'];
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $compras_stats = $vendas_stats = $estoque_stats = ['total' => 0, 'valor_total' => 0, 'itens' => 0];
    $lucro_total = 0;
}

// Últimas compras
$ultimas_compras_stmt = $pdo->query("
    SELECT produto, quantidade, valor_total, data_compra 
    FROM compras 
    ORDER BY data_compra DESC 
    LIMIT 5
");
$ultimas_compras = $ultimas_compras_stmt->fetchAll();

// Últimas vendas
$ultimas_vendas_stmt = $pdo->query("
    SELECT produto, quantidade, valor_vendido, data_venda 
    FROM vendas 
    ORDER BY data_venda DESC 
    LIMIT 5
");
$ultimas_vendas = $ultimas_vendas_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>COISABOA - Dashboard</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent;
        }
        
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --text: #34495e;
            --text-light: #7f8c8d;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --radius: 16px;
            --radius-sm: 12px;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 80px; /* Espaço para a bottom nav */
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 15px;
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-details h1 {
            font-size: 20px;
            color: var(--secondary);
            margin-bottom: 4px;
        }
        
        .user-details p {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 50px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .logout-btn:active {
            transform: scale(0.95);
        }
        
        /* Container Principal */
        .container {
            padding: 0 15px;
        }
        
        /* Cards de Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:active {
            transform: scale(0.98);
        }
        
        .stat-card.grande {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, var(--primary), #2980b9);
            color: white;
        }
        
        .stat-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Seções */
        .section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 15px 20px;
            background: var(--secondary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
        }
        
        .section-action {
            color: var(--light);
            text-decoration: none;
            font-size: 14px;
        }
        
        .section-content {
            padding: 0;
        }
        
        /* Listas */
        .lista-item {
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lista-item:last-child {
            border-bottom: none;
        }
        
        .item-info h4 {
            font-size: 15px;
            margin-bottom: 4px;
            color: var(--text);
        }
        
        .item-info p {
            font-size: 13px;
            color: var(--text-light);
        }
        
        .item-valor {
            font-weight: 700;
            color: var(--success);
        }
        
        .item-valor.negativo {
            color: var(--danger);
        }
        
        .sem-dados {
            padding: 30px 20px;
            text-align: center;
            color: var(--text-light);
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            border-top: 1px solid #ecf0f1;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-light);
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .nav-item.ativo {
            color: var(--primary);
        }
        
        .nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 11px;
            font-weight: 500;
        }
        
        /* Loading States */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsividade */
        @media (max-width: 360px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 15px 12px;
            }
            
            .container {
                padding: 0 12px;
            }
        }
        
        @media (min-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .container {
                max-width: 600px;
                margin: 0 auto;
            }
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Status Colors */
        .status-success { color: var(--success); }
        .status-warning { color: var(--warning); }
        .status-danger { color: var(--danger); }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header fade-in">
        <div class="user-info">
            <div class="user-details">
                <h1>Olá, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?>! 👋</h1>
                <p>Bem-vindo ao COISABOA</p>
            </div>
            <form method="POST" action="logout.php" style="display: inline;">
                <button type="submit" class="logout-btn" onclick="return confirm('Deseja sair?')">
                    Sair
                </button>
            </form>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">
        <!-- Estatísticas -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">R$ <?= number_format($compras_stats['valor_total'], 2, ',', '.') ?></div>
                <div class="stat-label">Total Comprado</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💸</div>
                <div class="stat-value">R$ <?= number_format($vendas_stats['valor_total'], 2, ',', '.') ?></div>
                <div class="stat-label">Total Vendido</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= $estoque_stats['itens'] ?></div>
                <div class="stat-label">Itens Estoque</div>
            </div>
            
            <div class="stat-card grande">
                <div class="stat-icon">📈</div>
                <div class="stat-value">R$ <?= number_format($lucro_total, 2, ',', '.') ?></div>
                <div class="stat-label">Lucro Total</div>
            </div>
        </div>

        <!-- Últimas Compras -->
        <section class="section fade-in">
            <div class="section-header">
                <h3 class="section-title">🛒 Últimas Compras</h3>
                <a href="compras.php" class="section-action">Ver Todas</a>
            </div>
            <div class="section-content">
                <?php if (empty($ultimas_compras)): ?>
                    <div class="sem-dados">
                        Nenhuma compra registrada
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimas_compras as $compra): ?>
                        <div class="lista-item">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($compra['produto']) ?></h4>
                                <p><?= $compra['quantidade'] ?> un • <?= date('d/m', strtotime($compra['data_compra'])) ?></p>
                            </div>
                            <div class="item-valor">
                                R$ <?= number_format($compra['valor_total'], 2, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Últimas Vendas -->
        <section class="section fade-in">
            <div class="section-header">
                <h3 class="section-title">🏷️ Últimas Vendas</h3>
                <a href="vendas.php" class="section-action">Ver Todas</a>
            </div>
            <div class="section-content">
                <?php if (empty($ultimas_vendas)): ?>
                    <div class="sem-dados">
                        Nenhuma venda registrada
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimas_vendas as $venda): ?>
                        <div class="lista-item">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($venda['produto']) ?></h4>
                                <p><?= $venda['quantidade'] ?> un • <?= date('d/m', strtotime($venda['data_venda'])) ?></p>
                            </div>
                            <div class="item-valor status-success">
                                R$ <?= number_format($venda['valor_vendido'], 2, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item ativo">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="compras.php" class="nav-item">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Compras</span>
        </a>
        <a href="vendas.php" class="nav-item">
            <span class="nav-icon">🏷️</span>
            <span class="nav-label">Vendas</span>
        </a>
        <a href="estoque.php" class="nav-item">
            <span class="nav-icon">📦</span>
            <span class="nav-label">Estoque</span>
        </a>
    </nav>

    <script>
        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback tátil para todos os elementos clicáveis
            const clickableElements = document.querySelectorAll('.stat-card, .lista-item, .nav-item, .logout-btn');
            
            clickableElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.opacity = '0.7';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            });
            
            // Prevenir zoom duplo
            document.addEventListener('touchstart', function(e) {
                if (e.touches.length > 1) {
                    e.preventDefault();
                }
            }, { passive: false });
            
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(e) {
                const now = (new Date()).getTime();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
            
            // Loading states para navegação
            const navLinks = document.querySelectorAll('.nav-item, .section-action');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('href') && !this.classList.contains('ativo')) {
                        this.style.opacity = '0.5';
                    }
                });
            });
            
            // Atualizar dados periodicamente (opcional)
            setTimeout(() => {
                // Aqui poderia ter uma atualização AJAX dos dados
                console.log('Dashboard carregado com sucesso!');
            }, 1000);
        });
        
        // Detectar se está em modo PWA/standalone
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true) {
            document.body.classList.add('pwa-mode');
        }
    </script>
</body>
</html>