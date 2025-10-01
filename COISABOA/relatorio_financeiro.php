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
$lucro_por_dia = [];
$total_vendas = 0;
$total_custo = 0;
$total_lucro = 0;

try {
    $pdo = getDB();
    
    // Vendas e lucro por dia
    $stmt = $pdo->prepare("
        SELECT 
            DATE(v.data_venda) as data,
            COUNT(*) as qtd_vendas,
            SUM(v.quantidade) as total_itens,
            SUM(v.valor_vendido) as total_vendas,
            SUM(v.quantidade * e.valor_compra) as total_custo,
            SUM(v.valor_vendido - (v.quantidade * e.valor_compra)) as total_lucro
        FROM vendas v
        INNER JOIN estoque e ON v.produto = e.produto
        WHERE DATE(v.data_venda) BETWEEN ? AND ?
        GROUP BY DATE(v.data_venda)
        ORDER BY data DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $vendas_por_dia = $stmt->fetchAll();
    
    // Totais gerais
    $stmt = $pdo->prepare("
        SELECT 
            SUM(v.valor_vendido) as total_vendas,
            SUM(v.quantidade * e.valor_compra) as total_custo,
            SUM(v.valor_vendido - (v.quantidade * e.valor_compra)) as total_lucro,
            COUNT(*) as total_vendas_qtd
        FROM vendas v
        INNER JOIN estoque e ON v.produto = e.produto
        WHERE DATE(v.data_venda) BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $totais = $stmt->fetch();
    
    $total_vendas = $totais['total_vendas'] ?? 0;
    $total_custo = $totais['total_custo'] ?? 0;
    $total_lucro = $totais['total_lucro'] ?? 0;
    $total_vendas_qtd = $totais['total_vendas_qtd'] ?? 0;
    
    // Margem por produto
    $stmt = $pdo->prepare("
        SELECT 
            v.produto,
            SUM(v.quantidade) as total_vendido,
            SUM(v.valor_vendido) as total_vendas,
            SUM(v.quantidade * e.valor_compra) as total_custo,
            SUM(v.valor_vendido - (v.quantidade * e.valor_compra)) as total_lucro,
            (SUM(v.valor_vendido - (v.quantidade * e.valor_compra)) / SUM(v.valor_vendido)) * 100 as margem_percentual
        FROM vendas v
        INNER JOIN estoque e ON v.produto = e.produto
        WHERE DATE(v.data_venda) BETWEEN ? AND ?
        GROUP BY v.produto
        HAVING total_vendas > 0
        ORDER BY total_lucro DESC
        LIMIT 15
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $margem_produtos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $erro = "Erro ao gerar relatório: " . $e->getMessage();
}

// Cálculos
$margem_percentual = $total_vendas > 0 ? ($total_lucro / $total_vendas) * 100 : 0;
$ticket_medio = $total_vendas_qtd > 0 ? $total_vendas / $total_vendas_qtd : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219652; }
        
        .filtros { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #27ae60; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h3 { color: #2c3e50; margin-bottom: 10px; }
        .card .valor { font-size: 24px; font-weight: bold; color: #27ae60; }
        .card .negativo { color: #e74c3c; }
        .card .desc { font-size: 14px; color: #7f8c8d; margin-top: 5px; }
        
        .tabela { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
        .tabela tr:hover { background: #f5f5f5; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .secao { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .secao h2 { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ecf0f1; }
        
        .positivo { color: #27ae60; font-weight: bold; }
        .negativo { color: #e74c3c; font-weight: bold; }
        .neutro { color: #f39c12; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💹 Relatório Financeiro</h1>
            <div>
                <a href="relatorios.php" class="btn">← Voltar</a>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">Dashboard</a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" value="<?= $data_inicio ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" value="<?= $data_fim ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">🔍 Gerar Relatório</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (isset($erro)): ?>
            <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <!-- Cards de Resumo -->
        <div class="cards">
            <div class="card">
                <h3>Total em Vendas</h3>
                <div class="valor">R$ <?= number_format($total_vendas, 2, ',', '.') ?></div>
                <div class="desc">Faturamento bruto</div>
            </div>
            <div class="card">
                <h3>Lucro Total</h3>
                <div class="valor <?= $total_lucro < 0 ? 'negativo' : '' ?>">
                    R$ <?= number_format($total_lucro, 2, ',', '.') ?>
                </div>
                <div class="desc">Resultado líquido</div>
            </div>
            <div class="card">
                <h3>Margem de Lucro</h3>
                <div class="valor <?= $margem_percentual < 0 ? 'negativo' : '' ?>">
                    <?= number_format($margem_percentual, 2, ',', '.') ?>%
                </div>
                <div class="desc">Sobre as vendas</div>
            </div>
            <div class="card">
                <h3>Ticket Médio</h3>
                <div class="valor">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                <div class="desc">Por venda</div>
            </div>
        </div>

        <!-- Performance por Dia -->
        <div class="secao">
            <h2>📈 Performance Diária</h2>
            <?php if (!empty($vendas_por_dia)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Vendas</th>
                        <th>Faturamento</th>
                        <th>Custo</th>
                        <th>Lucro</th>
                        <th>Margem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas_por_dia as $dia): ?>
                    <?php $margem_dia = $dia['total_vendas'] > 0 ? ($dia['total_lucro'] / $dia['total_vendas']) * 100 : 0; ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($dia['data'])) ?></td>
                        <td><?= $dia['qtd_vendas'] ?></td>
                        <td>R$ <?= number_format($dia['total_vendas'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($dia['total_custo'], 2, ',', '.') ?></td>
                        <td class="<?= $dia['total_lucro'] < 0 ? 'negativo' : 'positivo' ?>">
                            R$ <?= number_format($dia['total_lucro'], 2, ',', '.') ?>
                        </td>
                        <td class="<?= $margem_dia < 0 ? 'negativo' : 'positivo' ?>">
                            <?= number_format($margem_dia, 2, ',', '.') ?>%
                        </td>
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

        <!-- Margem por Produto -->
        <div class="secao">
            <h2>🏆 Margem por Produto</h2>
            <?php if (!empty($margem_produtos)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd Vendida</th>
                        <th>Faturamento</th>
                        <th>Custo</th>
                        <th>Lucro</th>
                        <th>Margem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($margem_produtos as $produto): ?>
                    <tr>
                        <td><?= $produto['produto'] ?></td>
                        <td><?= $produto['total_vendido'] ?></td>
                        <td>R$ <?= number_format($produto['total_vendas'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($produto['total_custo'], 2, ',', '.') ?></td>
                        <td class="<?= $produto['total_lucro'] < 0 ? 'negativo' : 'positivo' ?>">
                            R$ <?= number_format($produto['total_lucro'], 2, ',', '.') ?>
                        </td>
                        <td class="<?= $produto['margem_percentual'] < 0 ? 'negativo' : 'positivo' ?>">
                            <?= number_format($produto['margem_percentual'], 2, ',', '.') ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhum dado de margem disponível</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>