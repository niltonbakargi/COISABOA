<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Buscar estatísticas rápidas para os cards
$pdo = getDB();
try {
    // Total de vendas
    $vendas_stmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(valor_vendido), 0) as valor_total FROM vendas");
    $vendas_stats = $vendas_stmt->fetch();
    
    // Total em estoque
    $estoque_stmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(quantidade), 0) as itens FROM estoque WHERE quantidade > 0");
    $estoque_stats = $estoque_stmt->fetch();
    
    // Lucro total
    $compras_stmt = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) as total FROM compras");
    $compras_total = $compras_stmt->fetch()['total'];
    $lucro_total = $vendas_stats['valor_total'] - $compras_total;
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $vendas_stats = ['total' => 0, 'valor_total' => 0];
    $estoque_stats = ['total' => 0, 'itens' => 0];
    $lucro_total = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Relatórios - COISABOA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #6b7280;
            --border: #e5e7eb;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            padding: 20px 15px 80px 15px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: var(--primary);
        }
        
        .btn-voltar {
            background: var(--gray);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-voltar:active {
            transform: translateY(-2px);
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
        }
        
        .card:active {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--card-color);
        }
        
        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .card-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .card-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.03);
            border-radius: 12px;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--card-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-btn {
            background: var(--card-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .card-btn:active {
            transform: scale(0.95);
        }
        
        /* Card Colors */
        .card-vendas { --card-color: var(--danger); }
        .card-estoque { --card-color: var(--info); }
        .card-financeiro { --card-color: var(--secondary); }
        
        /* Quick Stats */
        .quick-stats {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 25px;
        }
        
        .quick-stats h2 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid var(--stat-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--stat-color);
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 0.85rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-vendas { --stat-color: var(--danger); }
        .stat-estoque { --stat-color: var(--info); }
        .stat-lucro { --stat-color: var(--secondary); }
        .stat-compras { --stat-color: var(--warning); }
        
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
            border-top: 1px solid var(--border);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--gray);
            transition: all 0.3s ease;
            flex: 1;
            padding: 8px 0;
        }
        
        .nav-item.ativo {
            color: var(--primary);
        }
        
        .nav-icon {
            font-size: 1.3rem;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .card {
                padding: 25px;
            }
            
            .quick-stats {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px 10px 80px 10px;
            }
            
            .container {
                max-width: 100%;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .stat-item {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .card, .quick-stats {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header fade-in">
            <h1>
                <i class="fas fa-chart-line"></i>
                Relatórios
            </h1>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats fade-in">
            <h2>
                <i class="fas fa-chart-bar"></i>
                Resumo Geral
            </h2>
            <div class="stats-grid">
                <div class="stat-card stat-vendas">
                    <div class="value"><?= $vendas_stats['total'] ?></div>
                    <div class="label">Vendas Realizadas</div>
                </div>
                <div class="stat-card stat-estoque">
                    <div class="value"><?= $estoque_stats['itens'] ?></div>
                    <div class="label">Itens em Estoque</div>
                </div>
                <div class="stat-card stat-lucro">
                    <div class="value">R$ <?= number_format($lucro_total, 2, ',', '.') ?></div>
                    <div class="label">Lucro Total</div>
                </div>
                <div class="stat-card stat-compras">
                    <div class="value">R$ <?= number_format($vendas_stats['valor_total'], 2, ',', '.') ?></div>
                    <div class="label">Faturamento</div>
                </div>
            </div>
        </div>

        <!-- Cards de Relatórios -->
        <div class="cards-grid">
            <!-- Relatório de Vendas -->
            <a href="relatorio_de_vendas.php" class="card card-vendas fade-in">
                <div class="card-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="card-title">Relatório de Vendas</div>
                <div class="card-description">
                    Análise detalhada de vendas por período, produtos mais vendidos, performance comercial e métricas de conversão.
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $vendas_stats['total'] ?></div>
                        <div class="stat-label">Vendas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">R$ <?= number_format($vendas_stats['valor_total'], 2, ',', '.') ?></div>
                        <div class="stat-label">Faturamento</div>
                    </div>
                </div>
                <button class="card-btn">
                    <i class="fas fa-chart-bar"></i>
                    Acessar Relatório
                </button>
            </a>

            <!-- Relatório de Estoque -->
            <a href="relatorio_de_estoque.php" class="card card-estoque fade-in">
                <div class="card-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="card-title">Relatório de Estoque</div>
                <div class="card-description">
                    Situação completa do estoque, produtos em falta, valor investido, produtos sem movimento e giro de estoque.
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $estoque_stats['total'] ?></div>
                        <div class="stat-label">Produtos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $estoque_stats['itens'] ?></div>
                        <div class="stat-label">Itens</div>
                    </div>
                </div>
                <button class="card-btn">
                    <i class="fas fa-warehouse"></i>
                    Acessar Relatório
                </button>
            </a>

            <!-- Relatório Financeiro -->
            <a href="relatorio_financeiro.php" class="card card-financeiro fade-in">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">Relatório Financeiro</div>
                <div class="card-description">
                    Lucro, margens de contribuição, fluxo de caixa, análise de rentabilidade e indicadores financeiros.
                </div>
                <div class="card-stats">
                    <div class="stat-item">
                        <div class="stat-value">R$ <?= number_format($lucro_total, 2, ',', '.') ?></div>
                        <div class="stat-label">Lucro</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?= $vendas_stats['valor_total'] > 0 ? number_format(($lucro_total / $vendas_stats['valor_total']) * 100, 1, ',', '.') : '0' ?>%
                        </div>
                        <div class="stat-label">Margem</div>
                    </div>
                </div>
                <button class="card-btn">
                    <i class="fas fa-dollar-sign"></i>
                    Acessar Relatório
                </button>
            </a>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="comprar.php" class="nav-item">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Compras</span>
        </a>
        <a href="vender.php" class="nav-item">
            <span class="nav-icon">🏷️</span>
            <span class="nav-label">Vendas</span>
        </a>
        <a href="relatorios.php" class="nav-item ativo">
            <span class="nav-icon">📈</span>
            <span class="nav-label">Relatórios</span>
        </a>
    </nav>

    <script>
        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback tátil para cards
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                card.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                });
                
                card.addEventListener('touchend', function() {
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
        });
    </script>
</body>
</html>