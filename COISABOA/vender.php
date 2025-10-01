<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
$imagens_produto = [];

if ($_POST['action'] ?? '' === 'vender') {
    try {
        $pdo = getDB();
        
        $produto_id = $_POST['produto_id'];
        $quantidade = $_POST['quantidade'];
        $valor_venda = $_POST['valor_venda'];
        
        // Buscar produto
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) throw new Exception("Produto não encontrado!");
        if ($produto['quantidade'] < $quantidade) throw new Exception("Estoque insuficiente!");
        
        // Registrar venda
        $stmt = $pdo->prepare("INSERT INTO vendas (produto, quantidade, valor_vendido, data_venda) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$produto['produto'], $quantidade, $valor_venda]);
        
        // Atualizar estoque
        $nova_quantidade = $produto['quantidade'] - $quantidade;
        if ($nova_quantidade > 0) {
            $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ? WHERE id = ?");
            $stmt->execute([$nova_quantidade, $produto_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
            $stmt->execute([$produto_id]);
        }
        
        $mensagem = "✅ Venda registrada!";
        
    } catch (Exception $e) {
        $mensagem = "❌ " . $e->getMessage();
    }
}

// Buscar produtos
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM estoque ORDER BY produto");
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    $produtos = [];
}

// Se um produto foi selecionado, buscar suas imagens
if ($_POST['produto_id'] ?? '') {
    $produto_id = $_POST['produto_id'];
    $pasta_produto = "uploads/produtos/$produto_id";
    
    if (file_exists($pasta_produto)) {
        $arquivos = scandir($pasta_produto);
        
        foreach ($arquivos as $arquivo) {
            if ($arquivo !== '.' && $arquivo !== '..') {
                $caminho = "$pasta_produto/$arquivo";
                if (is_file($caminho) && @getimagesize($caminho)) {
                    $imagens_produto[] = $caminho;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vender - COISABOA</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 500px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; margin-bottom: 10px; }
        button { background: #27ae60; color: white; border: none; cursor: pointer; }
        .mensagem { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .sucesso { background: #d4edda; color: #155724; }
        .erro { background: #f8d7da; color: #721c24; }
        .imagens { margin: 15px 0; text-align: center; }
        .miniatura { width: 80px; height: 80px; object-fit: cover; margin: 5px; border-radius: 5px; cursor: pointer; border: 2px solid #ddd; }
        .miniatura:hover { border-color: #3498db; }
    </style>
</head>
<body>
    <h1>💰 Vender Produto</h1>
    
    <?php if ($mensagem): ?>
        <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="action" value="vender">
        
        <div class="form-group">
            <label>Produto</label>
            <select name="produto_id" onchange="this.form.submit()" required>
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?= $produto['id'] ?>" <?= ($_POST['produto_id'] ?? '') == $produto['id'] ? 'selected' : '' ?>>
                        <?= $produto['produto'] ?> - Estoque: <?= $produto['quantidade'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Mostrar imagens do produto selecionado -->
        <?php if (!empty($imagens_produto)): ?>
            <div class="imagens">
                <strong>Fotos do Produto:</strong><br>
                <?php foreach ($imagens_produto as $imagem): ?>
                    <img src="<?= $imagem ?>" class="miniatura" onclick="window.open('<?= $imagem ?>', '_blank')">
                <?php endforeach; ?>
            </div>
        <?php elseif ($_POST['produto_id'] ?? ''): ?>
            <div class="imagens">
                <em>Nenhuma imagem encontrada na pasta: uploads/produtos/<?= $_POST['produto_id'] ?></em>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Quantidade</label>
            <input type="number" name="quantidade" value="<?= $_POST['quantidade'] ?? 1 ?>" min="1" required>
        </div>
        
        <div class="form-group">
            <label>Valor da Venda (R$)</label>
            <input type="number" name="valor_venda" value="<?= $_POST['valor_venda'] ?? '' ?>" step="0.01" min="0" required>
        </div>
        
        <button type="submit">💰 Registrar Venda</button>
    </form>
    
    <a href="dashboard.php">← Voltar</a>
</body>
</html>