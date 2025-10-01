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
    
    // Buscar produtos existentes
    $stmt = $pdo->query("SELECT * FROM estoque ORDER BY produto");
    $produtos = $stmt->fetchAll();
    
    // Processar inclusão
    if ($_POST['action'] ?? '' === 'incluir_produto') {
        $produto = trim($_POST['produto']);
        $quantidade = intval($_POST['quantidade']);
        $valor_compra = floatval($_POST['valor_compra'] ?? 0);
        
        if (empty($produto) || $quantidade <= 0) {
            throw new Exception("Preencha todos os campos obrigatórios!");
        }
        
        // Verificar se produto já existe
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE produto = ?");
        $stmt->execute([$produto]);
        $produto_existente = $stmt->fetch();
        
        if ($produto_existente) {
            // Atualizar produto existente
            $nova_quantidade = $produto_existente['quantidade'] + $quantidade;
            $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ?, valor_compra = ? WHERE produto = ?");
            $stmt->execute([$nova_quantidade, $valor_compra, $produto]);
            $mensagem = "✅ Produto atualizado! Estoque: {$produto_existente['quantidade']} → {$nova_quantidade}";
        } else {
            // Inserir novo produto
            $stmt = $pdo->prepare("INSERT INTO estoque (produto, quantidade, valor_compra) VALUES (?, ?, ?)");
            $stmt->execute([$produto, $quantidade, $valor_compra]);
            $mensagem = "✅ Novo produto adicionado ao estoque!";
        }
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
    <title>Incluir Produto - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219652; }
        
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-container { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        
        .tabela { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📥 Incluir Produto</h1>
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

        <div class="form-container">
            <h2>➕ Adicionar/Atualizar Produto</h2>
            <form method="POST">
                <input type="hidden" name="action" value="incluir_produto">
                
                <div class="form-group">
                    <label for="produto">Nome do Produto *</label>
                    <input type="text" id="produto" name="produto" value="<?= $_POST['produto'] ?? '' ?>" required 
                           placeholder="Digite o nome do produto">
                </div>
                
                <div class="form-group">
                    <label for="quantidade">Quantidade *</label>
                    <input type="number" id="quantidade" name="quantidade" value="<?= $_POST['quantidade'] ?? 1 ?>" 
                           min="1" required placeholder="Quantidade a adicionar">
                </div>
                
                <div class="form-group">
                    <label for="valor_compra">Valor de Compra (R$)</label>
                    <input type="number" id="valor_compra" name="valor_compra" value="<?= $_POST['valor_compra'] ?? '' ?>" 
                           step="0.01" min="0" placeholder="0,00">
                </div>
                
                <button type="submit" class="btn btn-success">💾 Salvar Produto</button>
            </form>
        </div>

        <div style="background: white; padding: 25px; border-radius: 10px;">
            <h2>📦 Produtos em Estoque</h2>
            <?php if (!empty($produtos)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Estoque</th>
                        <th>Valor Compra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $prod): ?>
                    <tr>
                        <td><?= $prod['produto'] ?></td>
                        <td><?= $prod['quantidade'] ?></td>
                        <td>R$ <?= number_format($prod['valor_compra'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 20px;">Nenhum produto em estoque</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>