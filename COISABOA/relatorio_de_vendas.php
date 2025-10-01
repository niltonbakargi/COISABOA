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
$total_vendas = 0;
$total_itens = 0;
$total_valor = 0;

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
    
    // Produtos mais vendidos
    $stmt = $pdo->prepare("
        SELECT 
            produto,
            SUM(quantidade) as total_vendido,
            SUM(valor_vendido) as total_valor,
            COUNT(*) as qtd_vendas,
            AVG(valor_vendido / quantidade) as preco_medio
        FROM vendas 
        WHERE DATE(data_venda) BETWEEN ? AND ?
        GROUP BY produto
        ORDER BY total_vendido DESC
        LIMIT 10
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $produtos_mais_vendidos = $stmt->fetchAll();
    
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .filtros { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #e74c3c; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h3 { color: #2c3e50; margin-bottom: 10px; }
        .card .valor { font-size: 24px; font-weight: bold; color: #27ae60; }
        
        .tabela { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
        .tabela tr:hover { background: #f5f5f5; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .secao { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .secao h2 { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ecf0f1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Relatório de Vendas</h1>
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
                        <button type="submit" class="btn btn-danger">🔍 Gerar Relatório</button>
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
                <div class="valor">R$ <?= number_format($total_valor, 2, ',', '.') ?></div>
            </div>
            <div class="card">
                <h3>Vendas Realizadas</h3>
                <div class="valor"><?= $total_vendas ?></div>
            </div>
            <div class="card">
                <h3>Itens Vendidos</h3>
                <div class="valor"><?= $total_itens ?></div>
            </div>
            <div class="card">
                <h3>Ticket Médio</h3>
                <div class="valor">R$ <?= $total_vendas > 0 ? number_format($total_valor / $total_vendas, 2, ',', '.') : '0,00' ?></div>
            </div>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas_por_dia as $venda): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($venda['data'])) ?></td>
                        <td><?= $venda['qtd_vendas'] ?></td>
                        <td><?= $venda['total_itens'] ?></td>
                        <td>R$ <?= number_format($venda['total_valor'], 2, ',', '.') ?></td>
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

        <!-- Produtos Mais Vendidos -->
        <div class="secao">
            <h2>🏆 Top 10 Produtos Mais Vendidos</h2>
            <?php if (!empty($produtos_mais_vendidos)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Posição</th>
                        <th>Produto</th>
                        <th>Quantidade Vendida</th>
                        <th>Total (R$)</th>
                        <th>Vendas</th>
                        <th>Preço Médio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_mais_vendidos as $index => $produto): ?>
                    <tr>
                        <td><strong>#<?= $index + 1 ?></strong></td>
                        <td><?= $produto['produto'] ?></td>
                        <td><?= $produto['total_vendido'] ?></td>
                        <td>R$ <?= number_format($produto['total_valor'], 2, ',', '.') ?></td>
                        <td><?= $produto['qtd_vendas'] ?></td>
                        <td>R$ <?= number_format($produto['preco_medio'], 2, ',', '.') ?></td>
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
    </div>
</body>
</html>