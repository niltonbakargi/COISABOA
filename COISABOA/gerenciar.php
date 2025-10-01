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
    <title>Gerenciar - COISABOA</title>
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
        
        .card.danger { border-top: 4px solid #e74c3c; }
        .card.warning { border-top: 4px solid #f39c12; }
        .card.success { border-top: 4px solid #27ae60; }
        .card.info { border-top: 4px solid #3498db; }
        
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #d35400; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219652; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Gerenciar Sistema</h1>
            <a href="dashboard.php" class="btn">← Voltar para Dashboard</a>
        </div>

        <div class="cards">
            <!-- Desfazer Venda -->
            <a href="desfazer_venda.php" style="text-decoration: none;">
                <div class="card danger">
                    <div class="icon">❌</div>
                    <h2>Desfazer Venda</h2>
                    <p>Reverter uma venda registrada e retornar produtos ao estoque</p>
                    <button class="btn btn-danger">Acessar →</button>
                </div>
            </a>

            <!-- Desfazer Compra -->
            <a href="desfazer_compra.php" style="text-decoration: none;">
                <div class="card warning">
                    <div class="icon">🔄</div>
                    <h2>Desfazer Compra</h2>
                    <p>Reverter uma compra registrada e remover produtos do estoque</p>
                    <button class="btn btn-warning">Acessar →</button>
                </div>
            </a>

            <!-- Incluir Produto -->
            <a href="incluirproduto.php" style="text-decoration: none;">
                <div class="card success">
                    <div class="icon">📥</div>
                    <h2>Incluir Produto</h2>
                    <p>Adicionar novo produto ao estoque ou aumentar quantidade existente</p>
                    <button class="btn btn-success">Acessar →</button>
                </div>
            </a>

            <!-- Excluir Produto -->
            <a href="excluirproduto.php" style="text-decoration: none;">
                <div class="card info">
                    <div class="icon">🗑️</div>
                    <h2>Excluir Produto</h2>
                    <p>Remover produto do estoque completamente</p>
                    <button class="btn">Acessar →</button>
                </div>
            </a>
        </div>
    </div>
</body>
</html>