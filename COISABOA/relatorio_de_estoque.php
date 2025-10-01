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
$estoque_completo = [];
$estoque_baixo = [];
$produtos_sem_movimento = [];
$valor_total_estoque = 0;

try {
    $pdo = getDB();
    
    // Estoque completo
    $stmt = $pdo->prepare("
        SELECT 
            id,
            produto,
            quantidade,
            valor_compra,
            (quantidade * valor_compra) as valor_total
        FROM estoque 
        ORDER BY quantidade ASC
    ");
    $stmt->execute();
    $estoque_completo = $stmt->fetchAll();
    
    // Estoque baixo (<10 unidades)
    $stmt = $pdo->prepare("
        SELECT 
            produto,
            quantidade,
            valor_compra,
            (quantidade * valor_compra) as valor_total
        FROM estoque 
        WHERE quantidade < 10
        ORDER BY quantidade ASC
    ");
    $stmt->execute();
    $estoque_baixo = $stmt->fetchAll();
    
    // Produtos sem movimento
    $stmt = $pdo->prepare("
        SELECT e.*, (e.quantidade * e.valor_compra) as valor_total
        FROM estoque e
        LEFT JOIN vendas v ON e.produto = v.produto AND DATE(v.data_venda) BETWEEN ? AND ?
        WHERE v.id IS NULL
        ORDER BY e.quantidade DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $produtos_sem_movimento = $stmt->fetchAll();
    
    // Valor total em estoque
    $stmt = $pdo->prepare("
        SELECT SUM(quantidade * valor_compra) as valor_total
        FROM estoque
    ");
    $stmt->execute();
    $total = $stmt->fetch();
    $valor_total_estoque = $total['valor_total'] ?? 0;
    
} catch (PDOException $e) {
    $erro = "Erro ao gerar relatório: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Estoque - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        
        .filtros { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card.alert { border-left-color: #e74c3c; }
        .card.warning { border-left-color: #f39c12; }
        .card h3 { color: #2c3e50; margin-bottom: 10px; }
        .card .valor { font-size: 24px; font-weight: bold; color: #27ae60; }
        .card .desc { font-size: 14px; color: #7f8c8d; margin-top: 5px; }
        
        .tabela { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
        .tabela tr:hover { background: #f5f5f5; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .secao { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .secao h2 { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ecf0f1; }
        
        .estoque-baixo { color: #e74c3c; font-weight: bold; }
        .estoque-normal { color: #27ae60; }
        .estoque-medio { color: #f39c12; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Relatório de Estoque</h1>
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
                        <label>Período Análise (Sem Movimento)</label>
                        <input type="date" name="data_inicio" value="<?= $data_inicio ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" value="<?= $data_fim ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">🔍 Atualizar Relatório</button>
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
                <h3>Valor Total em Estoque</h3>
                <div class="valor">R$ <?= number_format($valor_total_estoque, 2, ',', '.') ?></div>
                <div class="desc">Investimento total</div>
            </div>
            <div class="card <?= count($estoque_baixo) > 0 ? 'alert' : '' ?>">
                <h3>Produtos com Estoque Baixo</h3>
                <div class="valor"><?= count($estoque_baixo) ?></div>
                <div class="desc">Menos de 10 unidades</div>
            </div>
            <div class="card warning">
                <h3>Produtos sem Movimento</h3>
                <div class="valor"><?= count($produtos_sem_movimento) ?></div>
                <div class="desc">Últimos 30 dias</div>
            </div>
            <div class="card">
                <h3>Itens no Estoque</h3>
                <div class="valor"><?= count($estoque_completo) ?></div>
                <div class="desc">Produtos diferentes</div>
            </div>
        </div>

        <!-- Estoque Baixo -->
        <?php if (!empty($estoque_baixo)): ?>
        <div class="secao">
            <h2>🚨 Produtos com Estoque Baixo</h2>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque Atual</th>
                        <th>Valor Unitário</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estoque_baixo as $produto): ?>
                    <tr>
                        <td><?= $produto['produto'] ?></td>
                        <td class="estoque-baixo"><?= $produto['quantidade'] ?> unidades</td>
                        <td>R$ <?= number_format($produto['valor_compra'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($produto['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Produtos sem Movimento -->
        <?php if (!empty($produtos_sem_movimento)): ?>
        <div class="secao">
            <h2>📭 Produtos sem Movimento</h2>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque Atual</th>
                        <th>Valor Unitário</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_sem_movimento as $produto): ?>
                    <tr>
                        <td><?= $produto['produto'] ?></td>
                        <td class="<?= $produto['quantidade'] < 10 ? 'estoque-baixo' : ($produto['quantidade'] < 20 ? 'estoque-medio' : 'estoque-normal') ?>">
                            <?= $produto['quantidade'] ?> unidades
                        </td>
                        <td>R$ <?= number_format($produto['valor_compra'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($produto['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Estoque Completo -->
        <div class="secao">
            <h2>📦 Estoque Completo</h2>
            <?php if (!empty($estoque_completo)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque</th>
                        <th>Valor Compra</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estoque_completo as $produto): ?>
                    <tr>
                        <td><?= $produto['produto'] ?></td>
                        <td class="<?= $produto['quantidade'] < 10 ? 'estoque-baixo' : ($produto['quantidade'] < 20 ? 'estoque-medio' : 'estoque-normal') ?>">
                            <?= $produto['quantidade'] ?> unidades
                        </td>
                        <td>R$ <?= number_format($produto['valor_compra'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($produto['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhum produto em estoque</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>