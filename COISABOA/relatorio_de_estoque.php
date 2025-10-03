<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Filtros
$filtro_produto = $_POST['filtro_produto'] ?? '';
$filtro_estoque_baixo = $_POST['filtro_estoque_baixo'] ?? '';
$ordenacao = $_POST['ordenacao'] ?? 'produto';

// Buscar dados do estoque
$estoque = [];
$total_estoque = 0;
$total_valor_estoque = 0;
$total_valor_revenda = 0;
$produtos_estoque_baixo = 0;
$produtos_sem_estoque = 0;

try {
    $pdo = getDB();
    
    // Construir query base
    $sql = "
        SELECT 
            e.*,
            (e.quantidade * e.valor_unitario) as valor_total_estoque,
            (e.quantidade * e.valor_revenda) as valor_total_revenda,
            (e.quantidade * e.valor_revenda) - (e.quantidade * e.valor_unitario) as lucro_potencial,
            ((e.valor_revenda - e.valor_unitario) / e.valor_unitario * 100) as margem_lucro_percentual,
            COALESCE(
                (SELECT SUM(v.quantidade) 
                 FROM vendas v 
                 WHERE v.produto = e.produto 
                 AND v.data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ), 0
            ) as vendas_30_dias
        FROM estoque e
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtro_produto)) {
        $sql .= " AND e.produto LIKE ?";
        $params[] = "%$filtro_produto%";
    }
    
    if ($filtro_estoque_baixo === 'baixo') {
        $sql .= " AND e.quantidade <= 5 AND e.quantidade > 0";
    } elseif ($filtro_estoque_baixo === 'zerado') {
        $sql .= " AND e.quantidade = 0";
    }
    
    // Ordenação
    $ordenacoes_validas = [
        'produto' => 'e.produto ASC',
        'quantidade' => 'e.quantidade DESC',
        'valor_unitario' => 'e.valor_unitario DESC',
        'valor_revenda' => 'e.valor_revenda DESC',
        'lucro_potencial' => 'lucro_potencial DESC',
        'margem_lucro' => 'margem_lucro_percentual DESC',
        'vendas_recentes' => 'vendas_30_dias DESC'
    ];
    
    $ordenacao_sql = $ordenacoes_validas[$ordenacao] ?? 'e.produto ASC';
    $sql .= " ORDER BY $ordenacao_sql";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estoque = $stmt->fetchAll();
    
    // Calcular totais e estatísticas
    foreach ($estoque as $item) {
        $total_estoque += $item['quantidade'];
        $total_valor_estoque += $item['valor_total_estoque'];
        $total_valor_revenda += $item['valor_total_revenda'];
        
        if ($item['quantidade'] <= 5 && $item['quantidade'] > 0) {
            $produtos_estoque_baixo++;
        } elseif ($item['quantidade'] == 0) {
            $produtos_sem_estoque++;
        }
    }
    
    // Buscar produtos mais vendidos (para sugestões de reposição)
    $stmt_vendas = $pdo->prepare("
        SELECT 
            produto,
            SUM(quantidade) as total_vendido,
            COUNT(*) as qtd_vendas
        FROM vendas 
        WHERE data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY produto 
        ORDER BY total_vendido DESC 
        LIMIT 10
    ");
    $stmt_vendas->execute();
    $produtos_mais_vendidos = $stmt_vendas->fetchAll();
    
    // Buscar produtos que nunca venderam
    $stmt_nao_vendidos = $pdo->prepare("
        SELECT e.produto, e.quantidade, e.data_atualizacao
        FROM estoque e
        LEFT JOIN vendas v ON e.produto = v.produto
        WHERE v.id IS NULL
        ORDER BY e.data_atualizacao DESC
        LIMIT 10
    ");
    $stmt_nao_vendidos->execute();
    $produtos_nao_vendidos = $stmt_nao_vendidos->fetchAll();
    
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
    <title>Relatório de Estoque - COISABOA</title>
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
        .card-total { --card-color: var(--primary); }
        .card-valor { --card-color: var(--secondary); }
        .card-revenda { --card-color: var(--info); }
        .card-lucro { --card-color: var(--warning); }
        
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
            grid-template-columns: 2fr 1fr 1fr auto;
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
        
        /* Alertas */
        .alertas-card {
            background: #fef3c7;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 4px solid var(--warning);
        }
        
        .alerta-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 12px;
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
        .estoque-alto { background: #d5f4e6; }
        .estoque-medio { background: #fffacd; }
        .estoque-baixo { background: #ffcccb; }
        .estoque-zero { background: #ffb3b3; color: #721c24; }
        
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
            .table th:nth-child(8),
            .table td:nth-child(8),
            .table th:nth-child(9),
            .table td:nth-child(9) {
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
                <i class="fas fa-boxes"></i>
                Relatório de Estoque
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
                        <label><i class="fas fa-search"></i> Buscar Produto</label>
                        <input type="text" name="filtro_produto" value="<?= htmlspecialchars($filtro_produto) ?>" 
                               placeholder="Digite o nome do produto...">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-filter"></i> Filtro Estoque</label>
                        <select name="filtro_estoque_baixo">
                            <option value="">Todos os produtos</option>
                            <option value="baixo" <?= $filtro_estoque_baixo === 'baixo' ? 'selected' : '' ?>>Estoque Baixo (≤ 5)</option>
                            <option value="zerado" <?= $filtro_estoque_baixo === 'zerado' ? 'selected' : '' ?>>Estoque Zerado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-sort"></i> Ordenar por</label>
                        <select name="ordenacao">
                            <option value="produto" <?= $ordenacao === 'produto' ? 'selected' : '' ?>>Nome do Produto</option>
                            <option value="quantidade" <?= $ordenacao === 'quantidade' ? 'selected' : '' ?>>Quantidade</option>
                            <option value="valor_unitario" <?= $ordenacao === 'valor_unitario' ? 'selected' : '' ?>>Valor Unitário</option>
                            <option value="valor_revenda" <?= $ordenacao === 'valor_revenda' ? 'selected' : '' ?>>Valor Revenda</option>
                            <option value="lucro_potencial" <?= $ordenacao === 'lucro_potencial' ? 'selected' : '' ?>>Lucro Potencial</option>
                            <option value="margem_lucro" <?= $ordenacao === 'margem_lucro' ? 'selected' : '' ?>>Margem de Lucro</option>
                            <option value="vendas_recentes" <?= $ordenacao === 'vendas_recentes' ? 'selected' : '' ?>>Vendas Recentes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-filter"></i>
                            Aplicar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alertas de Estoque -->
        <?php if ($produtos_estoque_baixo > 0 || $produtos_sem_estoque > 0): ?>
        <div class="alertas-card fade-in">
            <h3><i class="fas fa-exclamation-triangle"></i> Alertas de Estoque</h3>
            <?php if ($produtos_estoque_baixo > 0): ?>
                <div class="alerta-item">
                    <i class="fas fa-arrow-down" style="color: var(--warning);"></i>
                    <div>
                        <strong>Estoque Baixo:</strong> <?= $produtos_estoque_baixo ?> produto(s) com estoque ≤ 5 unidades
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($produtos_sem_estoque > 0): ?>
                <div class="alerta-item">
                    <i class="fas fa-times-circle" style="color: var(--danger);"></i>
                    <div>
                        <strong>Estoque Zerado:</strong> <?= $produtos_sem_estoque ?> produto(s) sem estoque
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Cards de Resumo -->
        <div class="cards-grid fade-in">
            <div class="stat-card card-total">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-value"><?= $total_estoque ?></div>
                <div class="stat-label">Total em Estoque</div>
            </div>
            <div class="stat-card card-valor">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($total_valor_estoque, 2, ',', '.') ?></div>
                <div class="stat-label">Valor do Estoque</div>
            </div>
            <div class="stat-card card-revenda">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($total_valor_revenda, 2, ',', '.') ?></div>
                <div class="stat-label">Valor de Revenda</div>
            </div>
            <div class="stat-card card-lucro">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">R$ <?= number_format($total_valor_revenda - $total_valor_estoque, 2, ',', '.') ?></div>
                <div class="stat-label">Lucro Potencial</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid fade-in">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Distribuição por Categoria de Estoque</h3>
                <canvas id="estoqueChart" height="250"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Valor por Produto (Top 10)</h3>
                <canvas id="valorChart" height="250"></canvas>
            </div>
        </div>

        <!-- Produtos Mais Vendidos vs Não Vendidos -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
            <div class="secao fade-in">
                <h2><i class="fas fa-trophy"></i> Produtos Mais Vendidos (30 dias)</h2>
                <?php if (!empty($produtos_mais_vendidos)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Vendidos</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos_mais_vendidos as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto['produto']) ?></td>
                                <td><strong><?= $produto['total_vendido'] ?></strong></td>
                                <td>
                                    <a href="comprar.php?produto=<?= urlencode($produto['produto']) ?>" 
                                       class="btn btn-success" style="padding: 8px 12px; font-size: 0.8rem;">
                                        <i class="fas fa-plus"></i>
                                        Repor
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>Nenhuma venda nos últimos 30 dias</h3>
                </div>
                <?php endif; ?>
            </div>

            <div class="secao fade-in">
                <h2><i class="fas fa-clock"></i> Produtos Não Vendidos</h2>
                <?php if (!empty($produtos_nao_vendidos)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Estoque</th>
                                <th>Atualização</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos_nao_vendidos as $produto): 
                                $classe_estoque = '';
                                if ($produto['quantidade'] == 0) $classe_estoque = 'estoque-zero';
                                elseif ($produto['quantidade'] <= 5) $classe_estoque = 'estoque-baixo';
                            ?>
                            <tr class="<?= $classe_estoque ?>">
                                <td><?= htmlspecialchars($produto['produto']) ?></td>
                                <td><strong><?= $produto['quantidade'] ?></strong></td>
                                <td><?= $produto['data_atualizacao'] ? date('d/m/Y', strtotime($produto['data_atualizacao'])) : 'Nunca' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Todos os produtos já foram vendidos</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estoque Completo -->
        <div class="secao fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h2><i class="fas fa-list"></i> Estoque Completo (<?= count($estoque) ?> produtos)</h2>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="badge badge-success">Alto (> 10)</span>
                    <span class="badge badge-warning">Médio (6-10)</span>
                    <span class="badge badge-danger">Baixo (1-5)</span>
                    <span class="badge badge-info">Zerado (0)</span>
                </div>
            </div>
            
            <?php if (!empty($estoque)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Estoque</th>
                            <th>Custo Unit.</th>
                            <th>Revenda Unit.</th>
                            <th>Valor Total</th>
                            <th>Lucro</th>
                            <th>Margem</th>
                            <th>Vendas (30d)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estoque as $item): 
                            $classe_estoque = '';
                            if ($item['quantidade'] == 0) {
                                $classe_estoque = 'estoque-zero';
                            } elseif ($item['quantidade'] <= 5) {
                                $classe_estoque = 'estoque-baixo';
                            } elseif ($item['quantidade'] <= 10) {
                                $classe_estoque = 'estoque-medio';
                            } else {
                                $classe_estoque = 'estoque-alto';
                            }
                            
                            $margem_lucro = $item['margem_lucro_percentual'] ?? 0;
                            $classe_margem = $margem_lucro >= 0 ? 'positivo' : 'negativo';
                        ?>
                        <tr class="<?= $classe_estoque ?>">
                            <td>
                                <strong><?= htmlspecialchars($item['produto']) ?></strong>
                                <?php if ($item['quantidade'] == 0): ?>
                                    <span class="badge badge-info">ZERADO</span>
                                <?php elseif ($item['quantidade'] <= 5): ?>
                                    <span class="badge badge-danger">BAIXO</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $item['quantidade'] ?></strong></td>
                            <td>R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['valor_revenda'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['valor_total_estoque'], 2, ',', '.') ?></td>
                            <td class="<?= $item['lucro_potencial'] >= 0 ? 'positivo' : 'negativo' ?>">
                                R$ <?= number_format($item['lucro_potencial'], 2, ',', '.') ?>
                            </td>
                            <td class="<?= $classe_margem ?>">
                                <?= number_format($margem_lucro, 1, ',', '.') ?>%
                            </td>
                            <td><?= $item['vendas_30_dias'] ?></td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="comprar.php?produto=<?= urlencode($item['produto']) ?>" 
                                       class="btn btn-success" style="padding: 6px 10px; font-size: 0.75rem;">
                                        <i class="fas fa-cart-plus"></i>
                                    </a>
                                    <a href="vender.php?produto=<?= urlencode($item['produto']) ?>" 
                                       class="btn btn-warning" style="padding: 6px 10px; font-size: 0.75rem;">
                                        <i class="fas fa-cash-register"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Nenhum produto encontrado</h3>
                <p>Tente ajustar os filtros de busca</p>
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
        // Dados para gráficos
        const categoriasEstoque = {
            'Zerado': <?= $produtos_sem_estoque ?>,
            'Baixo (1-5)': <?= $produtos_estoque_baixo ?>,
            'Médio (6-10)': <?= count(array_filter($estoque, fn($item) => $item['quantidade'] > 5 && $item['quantidade'] <= 10)) ?>,
            'Alto (>10)': <?= count(array_filter($estoque, fn($item) => $item['quantidade'] > 10)) ?>
        };

        // Gráfico de Distribuição de Estoque
        new Chart(document.getElementById('estoqueChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(categoriasEstoque),
                datasets: [{
                    data: Object.values(categoriasEstoque),
                    backgroundColor: ['#ef4444', '#f59e0b', '#06b6d4', '#10b981']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Gráfico de Valor por Produto (Top 10)
        const topProdutos = <?= json_encode(array_slice($estoque, 0, 10)) ?>;
        new Chart(document.getElementById('valorChart'), {
            type: 'bar',
            data: {
                labels: topProdutos.map(p => p.produto.substring(0, 15) + (p.produto.length > 15 ? '...' : '')),
                datasets: [{
                    label: 'Valor do Estoque (R$)',
                    data: topProdutos.map(p => p.valor_total_estoque),
                    backgroundColor: '#6366f1'
                }, {
                    label: 'Valor de Revenda (R$)',
                    data: topProdutos.map(p => p.valor_total_revenda),
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
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