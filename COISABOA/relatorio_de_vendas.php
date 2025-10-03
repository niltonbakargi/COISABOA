<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Datas padrão (últimos 30 dias)
$data_inicio = $_POST['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_POST['data_fim'] ?? date('Y-m-d');

// Buscar dados
$vendas_por_dia = [];
$produtos_mais_vendidos = [];
$vendas_por_forma_pagamento = [];
$total_vendas = 0;
$total_itens = 0;
$total_valor = 0;
$lucro_total = 0;

try {
    $pdo = getDB();
    
    // Vendas por dia
    $stmt = $pdo->prepare("
        SELECT 
            DATE(data_venda) as data,
            COUNT(*) as qtd_vendas,
            SUM(quantidade) as total_itens,
            SUM(valor_vendido) as total_valor
        FROM vendas 
        WHERE DATE(data_venda) BETWEEN ? AND ?
        GROUP BY DATE(data_venda)
        ORDER BY data DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $vendas_por_dia = $stmt->fetchAll();
    
    // Totais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_vendas,
            SUM(quantidade) as total_itens,
            SUM(valor_vendido) as total_valor
        FROM vendas 
        WHERE DATE(data_venda) BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $totais = $stmt->fetch();
    $total_vendas = $totais['total_vendas'] ?? 0;
    $total_itens = $totais['total_itens'] ?? 0;
    $total_valor = $totais['total_valor'] ?? 0;
    
    // Produtos mais vendidos com cálculo de lucro
    $stmt = $pdo->prepare("
        SELECT 
            v.produto,
            SUM(v.quantidade) as total_vendido,
            SUM(v.valor_vendido) as total_valor,
            COUNT(*) as qtd_vendas,
            AVG(v.valor_vendido / v.quantidade) as preco_medio_venda,
            AVG(c.valor_unitario) as custo_medio,
            AVG(c.valor_revenda) as preco_revenda_sugerido,
            SUM(v.valor_vendido) - SUM(v.quantidade * COALESCE(c.valor_unitario, 0)) as lucro_total
        FROM vendas v
        LEFT JOIN compras c ON v.produto = c.produto
        WHERE DATE(v.data_venda) BETWEEN ? AND ?
        GROUP BY v.produto
        ORDER BY total_vendido DESC
        LIMIT 10
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $produtos_mais_vendidos = $stmt->fetchAll();
    
    // Vendas por forma de pagamento
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(forma_pagamento, 'Não informado') as forma_pagamento,
            COUNT(*) as qtd_vendas,
            SUM(quantidade) as total_itens,
            SUM(valor_vendido) as total_valor
        FROM vendas 
        WHERE DATE(data_venda) BETWEEN ? AND ?
        GROUP BY forma_pagamento
        ORDER BY total_valor DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $vendas_por_forma_pagamento = $stmt->fetchAll();
    
    // Calcular lucro total aproximado
    $stmt = $pdo->prepare("
        SELECT 
            SUM(v.valor_vendido) - SUM(v.quantidade * COALESCE(c.valor_unitario, 0)) as lucro_total
        FROM vendas v
        LEFT JOIN compras c ON v.produto = c.produto
        WHERE DATE(v.data_venda) BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $lucro_result = $stmt->fetch();
    $lucro_total = $lucro_result['lucro_total'] ?? 0;
    
} catch (PDOException $e) {
    $erro = "Erro ao gerar relatório: " . $e->getMessage();
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
    <title>Relatório de Vendas - COISABOA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1400px;
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
        
        /* Mensagens */
        .mensagem {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 15px;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .erro {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            border-left: 4px solid var(--card-color);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--card-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        /* Card Colors */
        .card-lucro { --card-color: var(--secondary); }
        .card-vendas { --card-color: var(--primary); }
        .card-transacoes { --card-color: var(--info); }
        .card-itens { --card-color: var(--warning); }
        .card-ticket { --card-color: #9b59b6; }
        
        /* Filtros */
        .filtros-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 25px;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            text-decoration: none;
            height: 48px;
        }
        
        .btn:active {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-success {
            background: var(--secondary);
        }
        
        /* Gráficos */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .chart-card h3 {
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Seções */
        .secao {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 25px;
        }
        
        .secao h2 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table th {
            background: var(--dark);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
        
        /* Status Classes */
        .positivo { color: var(--secondary); font-weight: bold; }
        .negativo { color: var(--danger); font-weight: bold; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success { background: var(--secondary); color: white; }
        .badge-warning { background: var(--warning); color: white; }
        .badge-danger { background: var(--danger); color: white; }
        .badge-info { background: var(--info); color: white; }
        .badge-primary { background: var(--primary); color: white; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Resumo do Período */
        .resumo-periodo {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .resumo-periodo h3 {
            color: var(--dark);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
            
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 12px 8px;
            }
            
            /* Hide some columns on mobile */
            .table th:nth-child(5),
            .table td:nth-child(5),
            .table th:nth-child(6),
            .table td:nth-child(6),
            .table th:nth-child(7),
            .table td:nth-child(7) {
                display: none;
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
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .secao, .chart-card, .filtros-card {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 1.8rem;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header fade-in">
            <h1>
                <i class="fas fa-chart-line"></i>
                Relatório de Vendas
            </h1>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                <a href="relatorios.php" class="btn-voltar">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
                <button onclick="window.print()" class="btn btn-success">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="mensagem erro fade-in">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filtros-card fade-in">
            <form method="POST">
                <div class="filtros-grid">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Início</label>
                        <input type="date" name="data_inicio" value="<?= $data_inicio ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Data Fim</label>
                        <input type="date" name="data_fim" value="<?= $data_fim ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" style="height: 48px;">
                            <i class="fas fa-filter"></i>
                            Aplicar
                        </button>
                    </div>
                    <div class="form-group">
                        <a href="?data_inicio=<?= date('Y-m-01') ?>&data_fim=<?= date('Y-m-t') ?>" class="btn" style="background: var(--gray); height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar"></i>
                            Mês Atual
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Resumo do Período -->
        <div class="resumo-periodo fade-in">
            <h3>
                <i class="fas fa-chart-bar"></i>
                Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> à <?= date('d/m/Y', strtotime($data_fim)) ?>
            </h3>
        </div>

        <!-- Cards de Resumo -->
        <div class="cards-grid fade-in">
            <div class="stat-card card-lucro">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($lucro_total, 2, ',', '.') ?></div>
                <div class="stat-label">Lucro Total</div>
            </div>
            <div class="stat-card card-vendas">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($total_valor, 2, ',', '.') ?></div>
                <div class="stat-label">Total em Vendas</div>
            </div>
            <div class="stat-card card-transacoes">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-value"><?= $total_vendas ?></div>
                <div class="stat-label">Vendas Realizadas</div>
            </div>
            <div class="stat-card card-itens">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-value"><?= $total_itens ?></div>
                <div class="stat-label">Itens Vendidos</div>
            </div>
            <div class="stat-card card-ticket">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-value">R$ <?= $total_vendas > 0 ? number_format($total_valor / $total_vendas, 2, ',', '.') : '0,00' ?></div>
                <div class="stat-label">Ticket Médio</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid fade-in">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Vendas por Dia</h3>
                <canvas id="vendasChart" height="250"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-credit-card"></i> Formas de Pagamento</h3>
                <canvas id="pagamentosChart" height="250"></canvas>
            </div>
        </div>

        <!-- Produtos Mais Vendidos -->
        <div class="secao fade-in">
            <h2><i class="fas fa-trophy"></i> Top 10 Produtos Mais Vendidos</h2>
            <?php if (!empty($produtos_mais_vendidos)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Produto</th>
                            <th>Qtd Vendida</th>
                            <th>Total (R$)</th>
                            <th>Vendas</th>
                            <th>Preço Médio</th>
                            <th>Custo Médio</th>
                            <th>Lucro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_mais_vendidos as $index => $produto): 
                            $lucro_produto = $produto['lucro_total'] ?? 0;
                            $classe_lucro = $lucro_produto >= 0 ? 'positivo' : 'negativo';
                        ?>
                        <tr>
                            <td><span class="badge badge-primary">#<?= $index + 1 ?></span></td>
                            <td><strong><?= htmlspecialchars($produto['produto']) ?></strong></td>
                            <td><?= $produto['total_vendido'] ?></td>
                            <td>R$ <?= number_format($produto['total_valor'], 2, ',', '.') ?></td>
                            <td><?= $produto['qtd_vendas'] ?></td>
                            <td>R$ <?= number_format($produto['preco_medio_venda'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($produto['custo_medio'] ?? 0, 2, ',', '.') ?></td>
                            <td class="<?= $classe_lucro ?>">R$ <?= number_format($lucro_produto, 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Nenhum produto vendido no período</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- Vendas por Dia -->
        <div class="secao fade-in">
            <h2><i class="fas fa-calendar-day"></i> Vendas por Dia</h2>
            <?php if (!empty($vendas_por_dia)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Vendas</th>
                            <th>Itens Vendidos</th>
                            <th>Total (R$)</th>
                            <th>Média por Venda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendas_por_dia as $venda): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($venda['data'])) ?></td>
                            <td><?= $venda['qtd_vendas'] ?></td>
                            <td><?= $venda['total_itens'] ?></td>
                            <td>R$ <?= number_format($venda['total_valor'], 2, ',', '.') ?></td>
                            <td>R$ <?= $venda['qtd_vendas'] > 0 ? number_format($venda['total_valor'] / $venda['qtd_vendas'], 2, ',', '.') : '0,00' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Nenhuma venda encontrada no período</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- Formas de Pagamento -->
        <div class="secao fade-in">
            <h2><i class="fas fa-credit-card"></i> Vendas por Forma de Pagamento</h2>
            <?php if (!empty($vendas_por_forma_pagamento)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Forma de Pagamento</th>
                            <th>Vendas</th>
                            <th>Itens Vendidos</th>
                            <th>Total (R$)</th>
                            <th>% do Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendas_por_forma_pagamento as $pagamento): 
                            $percentual = $total_valor > 0 ? ($pagamento['total_valor'] / $total_valor) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= $pagamento['forma_pagamento'] ?></td>
                            <td><?= $pagamento['qtd_vendas'] ?></td>
                            <td><?= $pagamento['total_itens'] ?></td>
                            <td>R$ <?= number_format($pagamento['total_valor'], 2, ',', '.') ?></td>
                            <td><?= number_format($percentual, 1, ',', '.') ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Nenhuma venda encontrada no período</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
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
        <a href="relatorios.php" class="nav-item ativo">
            <span class="nav-icon">📈</span>
            <span class="nav-label">Relatórios</span>
        </a>
    </nav>

    <script>
        // Gráfico de Vendas por Dia
        const vendasData = {
            labels: [<?= implode(',', array_map(function($v) { return "'" . date('d/m', strtotime($v['data'])) . "'"; }, $vendas_por_dia)) ?>],
            datasets: [{
                label: 'Valor em Vendas (R$)',
                data: [<?= implode(',', array_column($vendas_por_dia, 'total_valor')) ?>],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        };

        new Chart(document.getElementById('vendasChart'), {
            type: 'line',
            data: vendasData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Gráfico de Formas de Pagamento
        const pagamentosData = {
            labels: [<?= implode(',', array_map(function($v) { return "'" . $v['forma_pagamento'] . "'"; }, $vendas_por_forma_pagamento)) ?>],
            datasets: [{
                data: [<?= implode(',', array_column($vendas_por_forma_pagamento, 'total_valor')) ?>],
                backgroundColor: [
                    '#6366f1', '#ef4444', '#10b981', '#f59e0b', 
                    '#06b6d4', '#8b5cf6', '#ec4899', '#1f2937'
                ]
            }]
        };

        new Chart(document.getElementById('pagamentosChart'), {
            type: 'doughnut',
            data: pagamentosData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback tátil para elementos interativos
            const interactiveElements = document.querySelectorAll('.btn, input, select');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>