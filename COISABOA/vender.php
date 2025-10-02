<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
$imagens_produto = [];
$imagens_comprador = [];
$valor_revenda_sugerido = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vender') {
    try {
        $pdo = getDB();
        
        $produto_id = $_POST['produto_id'];
        $quantidade = $_POST['quantidade'];
        $valor_venda = $_POST['valor_venda'];
        $forma_pagamento = $_POST['forma_pagamento'] ?? '';
        $observacoes = $_POST['observacoes'] ?? '';
        $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');
        
        if (empty($produto_id) || empty($quantidade) || empty($valor_venda)) {
            throw new Exception("Todos os campos obrigatórios devem ser preenchidos!");
        }
        
        // Buscar informações do produto
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) throw new Exception("Produto não encontrado!");
        if ($produto['quantidade'] < $quantidade) throw new Exception("Estoque insuficiente!");
        
        // Buscar o valor_revenda da tabela compras para este produto
        $stmt_compras = $pdo->prepare("
            SELECT valor_revenda 
            FROM compras 
            WHERE produto = ? 
            ORDER BY data_compra DESC 
            LIMIT 1
        ");
        $stmt_compras->execute([$produto['produto']]);
        $compra_info = $stmt_compras->fetch();
        
        $valor_revenda_compras = $compra_info ? $compra_info['valor_revenda'] : null;
        
        // Inserir venda com campos básicos
        $stmt = $pdo->prepare("
            INSERT INTO vendas 
            (produto, quantidade, valor_vendido, data_venda) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $produto['produto'], 
            $quantidade, 
            $valor_venda,
            $data_venda
        ]);
        
        $venda_id = $pdo->lastInsertId();
        
        // Upload da imagem do comprador
        $caminho_imagem_comprador = '';
        if (!empty($_FILES['imagem_comprador']['name']) && $_FILES['imagem_comprador']['error'] === 0) {
            $pasta_comprador = "uploads/compradores/$venda_id";
            if (!file_exists($pasta_comprador)) {
                mkdir($pasta_comprador, 0755, true);
            }
            
            $extensao_comprador = pathinfo($_FILES['imagem_comprador']['name'], PATHINFO_EXTENSION);
            $nome_arquivo_comprador = "comprador." . $extensao_comprador;
            $destino_comprador = "$pasta_comprador/$nome_arquivo_comprador";
            
            if (move_uploaded_file($_FILES['imagem_comprador']['tmp_name'], $destino_comprador)) {
                $caminho_imagem_comprador = $pasta_comprador;
                
                // Atualizar venda com caminho da imagem do comprador (se a coluna existir)
                try {
                    $stmt = $pdo->prepare("UPDATE vendas SET imagem_comprador = ? WHERE id = ?");
                    $stmt->execute([$caminho_imagem_comprador, $venda_id]);
                } catch (Exception $e) {
                    // Coluna pode não existir, ignorar erro
                }
            }
        }
        
        // Tentar atualizar campos adicionais se existirem
        try {
            $update_fields = [];
            $update_values = [];
            
            if (!empty($forma_pagamento)) {
                $update_fields[] = "forma_pagamento = ?";
                $update_values[] = $forma_pagamento;
            }
            
            if (!empty($observacoes)) {
                $update_fields[] = "observacoes = ?";
                $update_values[] = $observacoes;
            }
            
            // Adicionar valor_revenda da compra se encontrado
            if ($valor_revenda_compras) {
                try {
                    $update_fields[] = "valor_revenda = ?";
                    $update_values[] = $valor_revenda_compras;
                } catch (Exception $e) {
                    // Coluna pode não existir, ignorar
                }
            }
            
            if (!empty($update_fields)) {
                $update_values[] = $venda_id;
                $stmt = $pdo->prepare("UPDATE vendas SET " . implode(', ', $update_fields) . " WHERE id = ?");
                $stmt->execute($update_values);
            }
        } catch (Exception $e) {
            // Campos podem não existir, ignorar erro
        }
        
        // Atualizar estoque
        $nova_quantidade = $produto['quantidade'] - $quantidade;
        if ($nova_quantidade > 0) {
            $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ? WHERE id = ?");
            $stmt->execute([$nova_quantidade, $produto_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
            $stmt->execute([$produto_id]);
        }
        
        $mensagem = "✅ Venda registrada com sucesso!";
        $mensagem .= "<br><strong>ID da Venda:</strong> $venda_id";
        $mensagem .= "<br><strong>Produto:</strong> " . $produto['produto'];
        $mensagem .= "<br><strong>Quantidade:</strong> $quantidade";
        $mensagem .= "<br><strong>Valor da Venda:</strong> R$ " . number_format($valor_venda, 2, ',', '.');
        if ($valor_revenda_compras) {
            $mensagem .= "<br><strong>Valor Revenda Sugerido:</strong> R$ " . number_format($valor_revenda_compras, 2, ',', '.');
        }
        if ($forma_pagamento) {
            $mensagem .= "<br><strong>Forma de Pagamento:</strong> " . $forma_pagamento;
        }
        if ($caminho_imagem_comprador) {
            $mensagem .= "<br><strong>Foto do comprador:</strong> Salva com sucesso";
        }
        
        $_POST = [];
        
    } catch (Exception $e) {
        $mensagem = "❌ " . $e->getMessage();
    }
}

// Buscar produtos em estoque
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM estoque ORDER BY produto");
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    $produtos = [];
}

// Buscar imagens do produto selecionado e valor_revenda da compra
$produto_selecionado = $_POST['produto_id'] ?? '';
if ($produto_selecionado) {
    // Buscar informações do produto para encontrar a pasta correta
    $stmt = $pdo->prepare("SELECT produto, imagem_produto FROM estoque WHERE id = ?");
    $stmt->execute([$produto_selecionado]);
    $produto_info = $stmt->fetch();
    
    if ($produto_info && !empty($produto_info['imagem_produto'])) {
        $pasta_produto = $produto_info['imagem_produto'];
        
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
    
    // Buscar valor_revenda da tabela compras
    if ($produto_info) {
        $stmt_compras = $pdo->prepare("
            SELECT valor_revenda 
            FROM compras 
            WHERE produto = ? 
            ORDER BY data_compra DESC 
            LIMIT 1
        ");
        $stmt_compras->execute([$produto_info['produto']]);
        $compra_info = $stmt_compras->fetch();
        
        if ($compra_info && !empty($compra_info['valor_revenda'])) {
            $valor_revenda_sugerido = number_format($compra_info['valor_revenda'], 2, ',', '.');
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
        body { font-family: Arial; padding: 20px; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea, button { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #e74c3c; color: white; border: none; cursor: pointer; font-size: 16px; }
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; line-height: 1.6; }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .imagens { margin: 15px 0; text-align: center; }
        .miniatura { width: 100px; height: 100px; object-fit: cover; margin: 5px; border-radius: 5px; cursor: pointer; border: 2px solid #ddd; }
        .miniatura:hover { border-color: #3498db; }
        .arquivo-info { font-size: 12px; color: #666; margin-top: -8px; margin-bottom: 10px; }
        .info-pasta { font-size: 11px; color: #888; margin-top: 5px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .preview-container { margin: 10px 0; }
        .preview-img { max-width: 100px; max-height: 100px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .campo-opcional { opacity: 0.8; }
        .valor-sugerido { background: #e8f5e8; border: 1px solid #27ae60; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .valor-info { font-size: 12px; color: #27ae60; margin-top: -8px; margin-bottom: 10px; }
        .btn-sugerir { background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-left: 10px; }
    </style>
    <script>
        function selecionarProduto(produtoId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'produto_id';
            input.value = produtoId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        
        function usarValorSugerido() {
            const valorSugerido = document.getElementById('valorRevendaSugerido').getAttribute('data-valor');
            if (valorSugerido) {
                document.querySelector('input[name="valor_venda"]').value = valorSugerido;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Preview da imagem do comprador
            document.getElementById('imagemComprador').addEventListener('change', function(e) {
                const preview = document.getElementById('previewComprador');
                preview.innerHTML = '';
                
                if (e.target.files[0] && e.target.files[0].type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'preview-img';
                    img.file = e.target.files[0];
                    preview.appendChild(img);
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        });
    </script>
</head>
<body>
    <h1>💰 Vender Produto</h1>
    
    <?php if ($mensagem): ?>
        <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="vender">
        
        <div class="form-group">
            <label>Produto *</label>
            <select name="produto_id" onchange="selecionarProduto(this.value)" required>
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?= $produto['id'] ?>" <?= $produto_selecionado == $produto['id'] ? 'selected' : '' ?>>
                        <?= $produto['produto'] ?> - Estoque: <?= $produto['quantidade'] ?> - R$ <?= number_format($produto['valor_revenda'], 2, ',', '.') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Mostrar valor revenda sugerido da compra -->
        <?php if (!empty($valor_revenda_sugerido)): ?>
            <div class="valor-sugerido" id="valorRevendaSugerido" data-valor="<?= str_replace(',', '.', $valor_revenda_sugerido) ?>">
                <strong>💡 Valor Revenda Sugerido (da Compra): R$ <?= $valor_revenda_sugerido ?></strong>
                <button type="button" class="btn-sugerir" onclick="usarValorSugerido()">Usar este valor</button>
                <div class="valor-info">Este é o valor de revenda definido quando o produto foi comprado</div>
            </div>
        <?php endif; ?>
        
        <!-- Mostrar imagens do produto selecionado -->
        <?php if (!empty($imagens_produto)): ?>
            <div class="imagens">
                <strong>📸 Fotos do Produto:</strong><br>
                <?php foreach ($imagens_produto as $imagem): ?>
                    <img src="<?= $imagem ?>" class="miniatura" onclick="window.open('<?= $imagem ?>', '_blank')" title="Clique para ampliar">
                <?php endforeach; ?>
            </div>
        <?php elseif ($produto_selecionado): ?>
            <div class="imagens">
                <em>Nenhuma imagem encontrada para este produto</em>
            </div>
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Quantidade *</label>
                <input type="number" name="quantidade" value="<?= $_POST['quantidade'] ?? 1 ?>" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Data da Venda</label>
                <input type="datetime-local" name="data_venda" value="<?= $_POST['data_venda'] ?? date('Y-m-d\TH:i') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Valor da Venda (R$) *</label>
            <input type="number" name="valor_venda" value="<?= $_POST['valor_venda'] ?? '' ?>" step="0.01" min="0" required>
            <?php if (!empty($valor_revenda_sugerido)): ?>
                <div class="valor-info">Valor sugerido da compra: R$ <?= $valor_revenda_sugerido ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group campo-opcional">
            <label>Forma de Pagamento (Opcional)</label>
            <select name="forma_pagamento">
                <option value="">Selecione...</option>
                <option value="dinheiro" <?= ($_POST['forma_pagamento'] ?? '') === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                <option value="cartao_credito" <?= ($_POST['forma_pagamento'] ?? '') === 'cartao_credito' ? 'selected' : '' ?>>Cartão de Crédito</option>
                <option value="cartao_debito" <?= ($_POST['forma_pagamento'] ?? '') === 'cartao_debito' ? 'selected' : '' ?>>Cartão de Débito</option>
                <option value="pix" <?= ($_POST['forma_pagamento'] ?? '') === 'pix' ? 'selected' : '' ?>>PIX</option>
                <option value="transferencia" <?= ($_POST['forma_pagamento'] ?? '') === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                <option value="boleto" <?= ($_POST['forma_pagamento'] ?? '') === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                <option value="outro" <?= ($_POST['forma_pagamento'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
            </select>
        </div>
        
        <div class="form-group campo-opcional">
            <label>Observações (Opcional)</label>
            <textarea name="observacoes" rows="3" placeholder="Observações sobre a venda..."><?= $_POST['observacoes'] ?? '' ?></textarea>
        </div>
        
        <div class="form-group campo-opcional">
            <label>Foto do Comprador/Comprovante (Opcional)</label>
            <input type="file" name="imagem_comprador" accept="image/*" capture="camera" id="imagemComprador">
            <div class="arquivo-info">Foto do comprador ou comprovante de venda</div>
            <div class="preview-container" id="previewComprador"></div>
        </div>
        
        <button type="submit">💰 Registrar Venda</button>
    </form>
    
    <a href="dashboard.php">← Voltar para Dashboard</a>
</body>
</html>