<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
$produtos = [];

try {
    $pdo = getDB();
    
    // Buscar produtos
    $stmt = $pdo->query("SELECT * FROM estoque ORDER BY produto");
    $produtos = $stmt->fetchAll();
    
    // Processar exclusão
    if ($_POST['action'] ?? '' === 'excluir_produto') {
        $produto_id = $_POST['produto_id'];
        
        // Buscar dados do produto
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) {
            throw new Exception("Produto não encontrado!");
        }
        
        // Confirmar exclusão
        $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
        $stmt->execute([$produto_id]);
        
        $mensagem = "✅ Produto '{$produto['produto']}' excluído permanentemente do estoque!";
    }
    
} catch (Exception $e) {
    $mensagem = "❌ Erro: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Produto - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .tabela { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
        .tabela tr:hover { background: #f5f5f5; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .alerta { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeaa7; }
        
        .form-excluir { display: inline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗑️ Excluir Produto</h1>
            <div>
                <a href="gerenciar.php" class="btn">← Voltar</a>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">Dashboard</a>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <div class="alerta">
            <strong>⚠️ Atenção:</strong> Esta ação é permanente e não pode ser desfeita. 
            O produto será removido completamente do estoque.
        </div>

        <div style="background: white; padding: 25px; border-radius: 10px;">
            <h2>📦 Produtos em Estoque</h2>
            <p style="color: #7f8c8d; margin-bottom: 20px;">Selecione um produto para excluir:</p>
            
            <?php if (!empty($produtos)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque Atual</th>
                        <th>Valor Compra</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td><?= $produto['produto'] ?></td>
                        <td><?= $produto['quantidade'] ?> unidades</td>
                        <td>R$ <?= number_format($produto['valor_compra'], 2, ',', '.') ?></td>
                        <td>
                            <form method="POST" class="form-excluir" onsubmit="return confirm('ATENÇÃO! Tem certeza que deseja excluir permanentemente o produto \"<?= $produto['produto'] ?>\"?')">
                                <input type="hidden" name="action" value="excluir_produto">
                                <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                <button type="submit" class="btn btn-danger">Excluir</button>
                            </form>
                        </td>
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