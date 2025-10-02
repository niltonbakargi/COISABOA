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
        $forma_pagamento = $_POST['forma_pagamento'] ?? '';
        $observacoes = $_POST['observacoes'] ?? '';
        $data_compra = $_POST['data_compra'] ?? date('Y-m-d H:i:s');
        
        // 1. Primeiro inserir na tabela COMPRAS (sem imagens ainda)
        $stmt = $pdo->prepare("
            INSERT INTO compras 
            (produto, quantidade, valor_unitario, valor_total, valor_revenda, forma_pagamento, observacoes, data_compra) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $produto, 
            $quantidade, 
            $valor_unitario, 
            $valor_total, 
            $valor_revenda,
            $forma_pagamento,
            $observacoes,
            $data_compra
        ]);
        
        $compra_id = $pdo->lastInsertId();
        
        // Criar pasta com o ID da compra
        $pasta_produto = "uploads/produtos/$compra_id";
        if (!file_exists($pasta_produto)) {
            mkdir($pasta_produto, 0755, true);
        }
        
        // Upload das imagens do produto (múltiplas)
        $caminho_imagens_produto = '';
        $imagens_salvas = [];
        if (!empty($_FILES['imagens']['name'][0])) {
            foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['imagens']['error'][$key] === 0) {
                    $extensao = pathinfo($_FILES['imagens']['name'][$key], PATHINFO_EXTENSION);
                    $nome_arquivo = "foto_" . ($key + 1) . "." . $extensao;
                    $destino = "$pasta_produto/$nome_arquivo";
                    
                    if (move_uploaded_file($tmp_name, $destino)) {
                        $imagens_salvas[] = $nome_arquivo;
                    }
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
            
            if (move_uploaded_file($_FILES['imagem_vendedor']['tmp_name'], $destino_vendedor)) {
                $caminho_imagem_vendedor = $pasta_vendedor;
            }
        }
        
        // Atualizar a compra com os caminhos das imagens
        $stmt = $pdo->prepare("UPDATE compras SET imagem_produto = ?, imagem_vendedor = ? WHERE id = ?");
        $stmt->execute([$caminho_imagens_produto, $caminho_imagem_vendedor, $compra_id]);
        
        // 2. Inserir produtos INDIVIDUALMENTE no estoque
        $produtos_inseridos = 0;
        
        for ($i = 1; $i <= $quantidade; $i++) {
            // Criar identificador único para cada item
            $produto_individual = $quantidade > 1 ? "{$produto} #{$i}" : $produto;
            
            // Verificar se já existe um produto com esse nome exato
            $stmt = $pdo->prepare("SELECT id FROM estoque WHERE produto = ?");
            $stmt->execute([$produto_individual]);
            $existente = $stmt->fetch();
            
            if (!$existente) {
                // Inserir novo produto individual no estoque
                $stmt = $pdo->prepare("
                    INSERT INTO estoque 
                    (produto, quantidade, valor_unitario, valor_revenda, imagem_produto, compra_origem_id) 
                    VALUES (?, 1, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $produto_individual,
                    $valor_unitario,
                    $valor_revenda,
                    $caminho_imagens_produto,
                    $compra_id
                ]);
                $produtos_inseridos++;
            } else {
                // Se já existe, atualizar a quantidade para 1 (garantir que está disponível)
                $stmt = $pdo->prepare("UPDATE estoque SET quantidade = 1 WHERE id = ?");
                $stmt->execute([$existente['id']]);
                $produtos_inseridos++;
            }
        }
        
        $mensagem = "✅ Compra registrada com sucesso!";
        $mensagem .= "<br><strong>ID da Compra:</strong> $compra_id";
        $mensagem .= "<br><strong>Produto:</strong> $produto";
        $mensagem .= "<br><strong>Quantidade:</strong> $quantidade";
        $mensagem .= "<br><strong>Itens criados no estoque:</strong> $produtos_inseridos";
        $mensagem .= "<br><strong>Valor Unitário:</strong> R$ " . number_format($valor_unitario, 2, ',', '.');
        $mensagem .= "<br><strong>Valor Total:</strong> R$ " . number_format($valor_total, 2, ',', '.');
        $mensagem .= "<br><strong>Valor Revenda:</strong> R$ " . number_format($valor_revenda, 2, ',', '.');
        $mensagem .= "<br><strong>Forma de Pagamento:</strong> " . ($forma_pagamento ?: 'Não informada');
        
        if ($quantidade > 1) {
            $mensagem .= "<br><strong>💡 Observação:</strong> $quantidade itens individuais criados no estoque";
        }
        
        if ($imagens_salvas) {
            $mensagem .= "<br><strong>Fotos do produto:</strong> " . count($imagens_salvas) . " imagem(ns) salvas";
        }
        if ($caminho_imagem_vendedor) {
            $mensagem .= "<br><strong>Foto do vendedor:</strong> Salva com sucesso";
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
        body { font-family: Arial; padding: 20px; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select, button { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #27ae60; color: white; border: none; cursor: pointer; font-size: 16px; }
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; line-height: 1.6; }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .arquivo-info { font-size: 12px; color: #666; margin-top: -8px; margin-bottom: 10px; }
        .info-pasta { font-size: 11px; color: #888; margin-top: 5px; }
        .valor-info { font-size: 12px; color: #3498db; margin-top: -5px; margin-bottom: 10px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .preview-container { margin: 10px 0; }
        .preview-img { max-width: 100px; max-height: 100px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .campo-destaque { border: 2px solid #27ae60; background-color: #f8fff8; }
        .info-individual { background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #3498db; }
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
            <label>Nome do Produto *</label>
            <input type="text" name="produto" id="produto" required>
            <div class="valor-info">Ex: iPhone 14, Tênis Nike, etc.</div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Quantidade *</label>
                <input type="number" name="quantidade" id="quantidade" value="1" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Data da Compra</label>
                <input type="datetime-local" name="data_compra" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
        </div>
        
        <div id="infoIndividual" class="info-individual" style="display: none;">
            <strong>💡 Sistema de Itens Individuais:</strong><br>
            Cada unidade será cadastrada separadamente no estoque com numeração automática.
            Exemplo: "iPhone 14 #1", "iPhone 14 #2", etc.
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Valor Unitário (R$) *</label>
                <input type="number" name="valor_unitario" step="0.01" min="0" required id="valorUnitario">
                <div class="valor-info">Valor pago por cada unidade</div>
            </div>
            
            <div class="form-group">
                <label>Valor Revenda (R$) *</label>
                <input type="number" name="valor_revenda" step="0.01" min="0" required class="campo-destaque">
                <div class="valor-info">Valor que você pretende vender cada unidade</div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Valor Total Calculado</label>
            <input type="text" id="valorTotalDisplay" disabled style="background: #f5f5f5; font-weight: bold;">
            <div class="valor-info" id="infoValorTotal">O valor total será calculado automaticamente</div>
        </div>
        
        <div class="form-group">
            <label>Forma de Pagamento</label>
            <select name="forma_pagamento">
                <option value="">Selecione...</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="pix">PIX</option>
                <option value="transferencia">Transferência</option>
                <option value="boleto">Boleto</option>
                <option value="outro">Outro</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" rows="4" placeholder="Observações adicionais sobre a compra..."></textarea>
        </div>
        
        <div class="form-group">
            <label>Fotos do Produto (múltiplas)</label>
            <input type="file" name="imagens[]" multiple accept="image/*" capture="camera" id="imagensProduto">
            <div class="arquivo-info">Selecione várias fotos do produto (Ctrl+Click)</div>
            <div class="info-pasta">As fotos serão aplicadas a todos os itens do estoque</div>
            <div class="preview-container" id="previewProduto"></div>
        </div>
        
        <div class="form-group">
            <label>Foto do Vendedor/Comprovante</label>
            <input type="file" name="imagem_vendedor" accept="image/*" capture="camera" id="imagemVendedor">
            <div class="arquivo-info">Foto do vendedor ou comprovante de pagamento</div>
            <div class="info-pasta">A foto será salva em uma pasta com o ID da compra</div>
            <div class="preview-container" id="previewVendedor"></div>
        </div>
        
        <button type="submit">💾 Salvar Compra</button>
    </form>
    
    <a href="dashboard.php">← Voltar para Dashboard</a>

    <script>
        // Cálculo automático do valor total
        document.addEventListener('DOMContentLoaded', function() {
            const quantidade = document.querySelector('input[name="quantidade"]');
            const valorUnitario = document.querySelector('input[name="valor_unitario"]');
            const valorTotalDisplay = document.getElementById('valorTotalDisplay');
            const infoValorTotal = document.getElementById('infoValorTotal');
            const infoIndividual = document.getElementById('infoIndividual');
            
            function calcularValorTotal() {
                const qtd = parseFloat(quantidade.value) || 0;
                const valor = parseFloat(valorUnitario.value) || 0;
                const total = qtd * valor;
                
                // Mostrar/ocultar info de itens individuais
                if (qtd > 1) {
                    infoIndividual.style.display = 'block';
                } else {
                    infoIndividual.style.display = 'none';
                }
                
                if (total > 0) {
                    valorTotalDisplay.value = 'R$ ' + total.toFixed(2).replace('.', ',');
                    infoValorTotal.textContent = 'Quantidade: ' + qtd + ' × Valor Unitário: R$ ' + valor.toFixed(2).replace('.', ',') + ' = Total: R$ ' + total.toFixed(2).replace('.', ',');
                    infoValorTotal.style.color = '#27ae60';
                } else {
                    valorTotalDisplay.value = '';
                    infoValorTotal.textContent = 'O valor total será calculado automaticamente';
                    infoValorTotal.style.color = '#3498db';
                }
            }
            
            quantidade.addEventListener('input', calcularValorTotal);
            valorUnitario.addEventListener('input', calcularValorTotal);
            
            // Preview das imagens
            document.getElementById('imagensProduto').addEventListener('change', function(e) {
                const preview = document.getElementById('previewProduto');
                preview.innerHTML = '';
                
                for (let file of e.target.files) {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.className = 'preview-img';
                        img.file = file;
                        preview.appendChild(img);
                        
                        const reader = new FileReader();
                        reader.onload = (function(aImg) {
                            return function(e) {
                                aImg.src = e.target.result;
                            };
                        })(img);
                        reader.readAsDataURL(file);
                    }
                }
            });
            
            document.getElementById('imagemVendedor').addEventListener('change', function(e) {
                const preview = document.getElementById('previewVendedor');
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
            
            // Calcular inicialmente
            calcularValorTotal();
        });
    </script>
</body>
</html>