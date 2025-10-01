<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
$compras = [];

try {
    $pdo = getDB();
    
    // Buscar produtos do estoque (simulando "compras" já que não temos tabela compras)
    $stmt = $pdo->query("
        SELECT 
            id,
            produto,
            quantidade,
            valor_compra,
            data_criacao
        FROM estoque 
        WHERE quantidade > 0
        ORDER BY data_criacao DESC 
        LIMIT 50
    ");
    $compras = $stmt->fetchAll();
    
    // Processar desfazer compra (remover do estoque)
    if ($_POST['action'] ?? '' === 'desfazer_compra') {
        $produto_id = $_POST['produto_id'];
        $quantidade_remover = intval($_POST['quantidade'] ?? 0);
        
        // Buscar dados do produto
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) {
            throw new Exception("Produto não encontrado!");
        }
        
        // Validar quantidade
        if ($quantidade_remover <= 0) {
            throw new Exception("Quantidade deve ser maior que zero!");
        }
        
        if ($quantidade_remover > $produto['quantidade']) {
            throw new Exception("Quantidade a remover ({$quantidade_remover}) é maior que o estoque atual ({$produto['quantidade']})!");
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        if ($quantidade_remover == $produto['quantidade']) {
            // Remover produto completamente se quantidade for igual ao estoque
            $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
            $stmt->execute([$produto_id]);
            $mensagem = "✅ Compra desfeita! Produto '{$produto['produto']}' removido completamente do estoque.";
        } else {
            // Reduzir quantidade no estoque
            $nova_quantidade = $produto['quantidade'] - $quantidade_remover;
            $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ? WHERE id = ?");
            $stmt->execute([$nova_quantidade, $produto_id]);
            $mensagem = "✅ Compra desfeita! Estoque de '{$produto['produto']}' reduzido: {$produto['quantidade']} → {$nova_quantidade} unidades.";
        }
        
        $pdo->commit();
        
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensagem = "❌ Erro: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desfazer Compra - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #d35400; }
        
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .tabela { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
        .tabela tr:hover { background: #f5f5f5; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .info-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #f39c12; }
        
        .form-desfazer { display: inline; }
        .quantidade-input { width: 80px; padding: 5px; border: 1px solid #ddd; border-radius: 3px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Desfazer Compra/Entrada</h1>
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

        <div class="info-card">
            <h3>📋 Como funciona:</h3>
            <p>Ao desfazer uma compra/entrada, o sistema:</p>
            <ul>
                <li>➡️ Remove a quantidade especificada do <strong>estoque</strong></li>
                <li>➡️ Se quantidade = estoque total, o produto é removido completamente</li>
                <li>➡️ Ajusta o saldo atual do produto</li>
            </ul>
            <p><strong>Nota:</strong> Esta funcionalidade simula o desfazer de compras removendo itens do estoque.</p>
        </div>

        <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <h2>📦 Produtos em Estoque (Entradas)</h2>
            <p style="color: #7f8c8d; margin-bottom: 20px;">Selecione um produto para remover quantidade do estoque:</p>
            
            <?php if (!empty($compras)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque Atual</th>
                        <th>Valor Compra</th>
                        <th>Quantidade a Remover</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                    <tr>
                        <td><strong><?= $compra['produto'] ?></strong></td>
                        <td><?= $compra['quantidade'] ?> unidades</td>
                        <td>R$ <?= number_format($compra['valor_compra'], 2, ',', '.') ?></td>
                        <td>
                            <form method="POST" class="form-desfazer">
                                <input type="hidden" name="action" value="desfazer_compra">
                                <input type="hidden" name="produto_id" value="<?= $compra['id'] ?>">
                                <input type="number" name="quantidade" value="1" min="1" max="<?= $compra['quantidade'] ?>" 
                                       class="quantidade-input" required>
                        </td>
                        <td>
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('Tem certeza que deseja remover esta quantidade de \\'<?= $compra['produto'] ?>\\' do estoque?')">
                                    Remover do Estoque
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhum produto em estoque</h3>
                <p>Não há produtos disponíveis para remover.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>