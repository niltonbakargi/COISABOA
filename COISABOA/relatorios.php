<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .card h2 { color: #2c3e50; margin-bottom: 15px; }
        .card p { color: #7f8c8d; margin-bottom: 20px; }
        .card .icon { font-size: 48px; margin-bottom: 15px; }
        
        .card.vendas { border-top: 4px solid #e74c3c; }
        .card.estoque { border-top: 4px solid #3498db; }
        .card.financeiro { border-top: 4px solid #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Relatórios</h1>
            <a href="dashboard.php" class="btn">← Voltar para Dashboard</a>
        </div>

        <div class="cards">
            <!-- Relatório de Vendas -->
            <a href="relatorio_de_vendas.php" style="text-decoration: none;">
                <div class="card vendas">
                    <div class="icon">💰</div>
                    <h2>Relatório de Vendas</h2>
                    <p>Análise de vendas por período, produtos mais vendidos e performance comercial</p>
                    <button class="btn" style="background: #e74c3c;">Acessar →</button>
                </div>
            </a>

            <!-- Relatório de Estoque -->
            <a href="relatorio_de_estoque.php" style="text-decoration: none;">
                <div class="card estoque">
                    <div class="icon">📦</div>
                    <h2>Relatório de Estoque</h2>
                    <p>Situação do estoque, produtos em falta, valor investido e produtos sem movimento</p>
                    <button class="btn" style="background: #3498db;">Acessar →</button>
                </div>
            </a>

            <!-- Relatório Financeiro -->
            <a href="relatorio_financeiro.php" style="text-decoration: none;">
                <div class="card financeiro">
                    <div class="icon">💹</div>
                    <h2>Relatório Financeiro</h2>
                    <p>Lucro, margens, fluxo de caixa e análise de rentabilidade</p>
                    <button class="btn" style="background: #27ae60;">Acessar →</button>
                </div>
            </a>
        </div>
    </div>
</body>
</html>