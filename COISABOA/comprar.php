<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';

if ($_POST['action'] ?? '' === 'comprar') {
    try {
        $pdo = getDB();
        
        $produto = $_POST['produto'];
        $quantidade = $_POST['quantidade'];
        $valor_unitario = $_POST['valor_unitario'];
        $valor_total = $quantidade * $valor_unitario;
        $valor_revenda = $_POST['valor_revenda'];
        $observacoes = $_POST['observacoes'] ?? '';
        
        // 1. Primeiro inserir na tabela COMPRAS (sem imagens ainda)
        $stmt = $pdo->prepare("
            INSERT INTO compras 
            (produto, quantidade, valor_unitario, valor_total, valor_revenda, observacoes, data_compra) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $produto, 
            $quantidade, 
            $valor_unitario, 
            $valor_total, 
            $valor_revenda, 
            $observacoes
        ]);
        
        $compra_id = $pdo->lastInsertId();
        
        // Criar pasta com o ID da compra
        $pasta_produto = "uploads/produtos/$compra_id";
        if (!file_exists($pasta_produto)) {
            mkdir($pasta_produto, 0755, true);
        }
        
        // Upload das imagens do produto (múltiplas)
        $caminho_imagens_produto = '';
        if (!empty($_FILES['imagens']['name'][0])) {
            foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['imagens']['error'][$key] === 0) {
                    $extensao = pathinfo($_FILES['imagens']['name'][$key], PATHINFO_EXTENSION);
                    $nome_arquivo = "foto_" . ($key + 1) . "." . $extensao;
                    $destino = "$pasta_produto/$nome_arquivo";
                    
                    move_uploaded_file($tmp_name, $destino);
                }
            }
            $caminho_imagens_produto = $pasta_produto;
        }
        
        // Upload da imagem do vendedor
        $caminho_imagem_vendedor = '';
        if (!empty($_FILES['imagem_vendedor']['name']) && $_FILES['imagem_vendedor']['error'] === 0) {
            $pasta_vendedor = "uploads/vendedores/$compra_id";
            if (!file_exists($pasta_vendedor)) {
                mkdir($pasta_vendedor, 0755, true);
            }
            
            $extensao_vendedor = pathinfo($_FILES['imagem_vendedor']['name'], PATHINFO_EXTENSION);
            $nome_arquivo_vendedor = "vendedor." . $extensao_vendedor;
            $destino_vendedor = "$pasta_vendedor/$nome_arquivo_vendedor";
            
            move_uploaded_file($_FILES['imagem_vendedor']['tmp_name'], $destino_vendedor);
            $caminho_imagem_vendedor = $pasta_vendedor;
        }
        
        // Atualizar a compra com os caminhos das imagens
        $stmt = $pdo->prepare("UPDATE compras SET imagem_produto = ?, imagem_vendedor = ? WHERE id = ?");
        $stmt->execute([$caminho_imagens_produto, $caminho_imagem_vendedor, $compra_id]);
        
        // 2. Atualizar/Inserir na tabela ESTOQUE
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE produto = ?");
        $stmt->execute([$produto]);
        $produto_existente = $stmt->fetch();
        
        if ($produto_existente) {
            // Atualizar estoque existente
            $nova_quantidade = $produto_existente['quantidade'] + $quantidade;
            $stmt = $pdo->prepare("
                UPDATE estoque SET 
                quantidade = ?, 
                valor_unitario = ?,
                valor_revenda = ?,
                imagem_produto = ?,
                data_atualizacao = NOW()
                WHERE produto = ?
            ");
            $stmt->execute([
                $nova_quantidade,
                $valor_unitario,
                $valor_revenda,
                $caminho_imagens_produto,
                $produto
            ]);
            $estoque_id = $produto_existente['id'];
        } else {
            // Inserir novo produto no estoque
            $stmt = $pdo->prepare("
                INSERT INTO estoque 
                (produto, quantidade, valor_unitario, valor_revenda, imagem_produto) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $produto,
                $quantidade,
                $valor_unitario,
                $valor_revenda,
                $caminho_imagens_produto
            ]);
            $estoque_id = $pdo->lastInsertId();
        }
        
        $mensagem = "✅ Compra registrada! ID: $compra_id";
        if ($caminho_imagens_produto) {
            $mensagem .= " - Pasta Produto: $caminho_imagens_produto";
        }
        if ($caminho_imagem_vendedor) {
            $mensagem .= " - Pasta Vendedor: $caminho_imagem_vendedor";
        }
        
    } catch (Exception $e) {
        $mensagem = "❌ Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprar - COISABOA</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 500px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, button { width: 100%; padding: 10px; margin-bottom: 10px; }
        button { background: #27ae60; color: white; border: none; cursor: pointer; }
        .mensagem { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .sucesso { background: #d4edda; color: #155724; }
        .erro { background: #f8d7da; color: #721c24; }
        .arquivo-info { font-size: 12px; color: #666; margin-top: -8px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>🛒 Comprar Produto</h1>
    
    <?php if ($mensagem): ?>
        <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="comprar">
        
        <div class="form-group">
            <label>Nome do Produto</label>
            <input type="text" name="produto" required>
        </div>
        
        <div class="form-group">
            <label>Quantidade</label>
            <input type="number" name="quantidade" value="1" min="1" required>
        </div>
        
        <div class="form-group">
            <label>Valor Unitário (R$)</label>
            <input type="number" name="valor_unitario" step="0.01" min="0" required>
        </div>
        
        <div class="form-group">
            <label>Valor Revenda (R$)</label>
            <input type="number" name="valor_revenda" step="0.01" min="0" required>
        </div>
        
        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label>Fotos do Produto (múltiplas)</label>
            <input type="file" name="imagens[]" multiple accept="image/*" capture="camera">
            <div class="arquivo-info">Selecione várias fotos do produto (Ctrl+Click)</div>
        </div>
        
        <div class="form-group">
            <label>Foto do Vendedor</label>
            <input type="file" name="imagem_vendedor" accept="image/*" capture="camera">
            <div class="arquivo-info">Foto do vendedor ou comprovante</div>
        </div>
        
        <button type="submit">💾 Salvar Compra</button>
    </form>
    
    <a href="dashboard.php">← Voltar</a>
</body>
</html>