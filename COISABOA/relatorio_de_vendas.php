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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas - COISABOA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        
        .filtros { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        input, select { padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
        
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
        .card.lucro { border-left-color: #27ae60; }
        .card.vendas { border-left-color: #e74c3c; }
        .card.itens { border-left-color: #f39c12; }
        .card.ticket { border-left-color: #9b59b6; }
        .card h3 { color: #2c3e50; margin-bottom: 10px; font-size: 14px; text-transform: uppercase; }
        .card .valor { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .card .valor.lucro { color: #27ae60; }
        .card .valor.vendas { color: #e74c3c; }
        .card .desc { font-size: 12px; color: #7f8c8d; margin-top: 5px; }
        
        .tabela { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        .tabela th { background: #34495e; color: white; font-weight: 600; }
        .tabela tr:hover { background: #f8f9fa; }
        .tabela .positivo { color: #27ae60; font-weight: bold; }
        .tabela .negativo { color: #e74c3c; font-weight: bold; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .secao { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .secao h2 { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ecf0f1; display: flex; align-items: center; gap: 10px; }
        
        .charts { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .chart-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        .resumo-periodo { background: #34495e; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        
        @media (max-width: 768px) {
            .charts { grid-template-columns: 1fr; }
            .cards { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Relatório de Vendas - COISABOA</h1>
            <div style="display: flex; gap: 10px;">
                <a href="relatorios.php" class="btn">📊 Relatórios</a>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">🏠 Dashboard</a>
                <button onclick="window.print()" class="btn btn-success">🖨️ Imprimir</button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label>📅 Data Início</label>
                        <input type="date" name="data_inicio" value="<?= $data_inicio ?>" required>
                    </div>
                    <div class="form-group">
                        <label>📅 Data Fim</label>
                        <input type="date" name="data_fim" value="<?= $data_fim ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" style="height: 42px;">🔍 Gerar Relatório</button>
                    </div>
                    <div class="form-group">
                        <a href="?data_inicio=<?= date('Y-m-01') ?>&data_fim=<?= date('Y-m-t') ?>" class="btn" style="background: #95a5a6; height: 42px; display: flex; align-items: center; justify-content: center;">Mês Atual</a>
                    </div>
                </div>
            </form>
        </div>

        <?php if (isset($erro)): ?>
            <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <!-- Resumo do Período -->
        <div class="resumo-periodo">
            <h3>📊 Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> à <?= date('d/m/Y', strtotime($data_fim)) ?></h3>
        </div>

        <!-- Cards de Resumo -->
        <div class="cards">
            <div class="card lucro">
                <h3>💰 Lucro Total</h3>
                <div class="valor lucro">R$ <?= number_format($lucro_total, 2, ',', '.') ?></div>
                <div class="desc">Receita - Custo das Mercadorias</div>
            </div>
            <div class="card vendas">
                <h3>📈 Total em Vendas</h3>
                <div class="valor vendas">R$ <?= number_format($total_valor, 2, ',', '.') ?></div>
                <div class="desc">Valor bruto das vendas</div>
            </div>
            <div class="card">
                <h3>🛍️ Vendas Realizadas</h3>
                <div class="valor"><?= $total_vendas ?></div>
                <div class="desc">Quantidade de transações</div>
            </div>
            <div class="card itens">
                <h3>📦 Itens Vendidos</h3>
                <div class="valor"><?= $total_itens ?></div>
                <div class="desc">Total de produtos vendidos</div>
            </div>
            <div class="card ticket">
                <h3>🎫 Ticket Médio</h3>
                <div class="valor">R$ <?= $total_vendas > 0 ? number_format($total_valor / $total_vendas, 2, ',', '.') : '0,00' ?></div>
                <div class="desc">Média por venda</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts">
            <div class="chart-container">
                <h3>📈 Vendas por Dia</h3>
                <canvas id="vendasChart" height="250"></canvas>
            </div>
            <div class="chart-container">
                <h3>💳 Formas de Pagamento</h3>
                <canvas id="pagamentosChart" height="250"></canvas>
            </div>
        </div>

        <!-- Produtos Mais Vendidos -->
        <div class="secao">
            <h2>🏆 Top 10 Produtos Mais Vendidos</h2>
            <?php if (!empty($produtos_mais_vendidos)): ?>
            <table class="tabela">
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
                        <td><strong>#<?= $index + 1 ?></strong></td>
                        <td><?= htmlspecialchars($produto['produto']) ?></td>
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
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhum produto vendido no período</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- Vendas por Dia -->
        <div class="secao">
            <h2>📅 Vendas por Dia</h2>
            <?php if (!empty($vendas_por_dia)): ?>
            <table class="tabela">
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
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhuma venda encontrada no período</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- Formas de Pagamento -->
        <div class="secao">
            <h2>💳 Vendas por Forma de Pagamento</h2>
            <?php if (!empty($vendas_por_forma_pagamento)): ?>
            <table class="tabela">
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
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhuma venda encontrada no período</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Gráfico de Vendas por Dia
        const vendasData = {
            labels: [<?= implode(',', array_map(function($v) { return "'" . date('d/m', strtotime($v['data'])) . "'"; }, $vendas_por_dia)) ?>],
            datasets: [{
                label: 'Valor em Vendas (R$)',
                data: [<?= implode(',', array_column($vendas_por_dia, 'total_valor')) ?>],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
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
                    '#3498db', '#e74c3c', '#27ae60', '#f39c12', 
                    '#9b59b6', '#1abc9c', '#d35400', '#34495e'
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
    </script>
</body>
</html>