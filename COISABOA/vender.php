<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';

if ($_POST['action'] ?? '' === 'vender') {
    try {
        $pdo = getDB();
        
        $produto = $_POST['produto'];
        $quantidade = $_POST['quantidade'];
        $valor_vendido = $_POST['valor_vendido'];
        $valor_total = $quantidade * $valor_vendido;
        $forma_pagamento = $_POST['forma_pagamento'] ?? '';
        $observacoes = $_POST['observacoes'] ?? '';
        $data_venda = $_POST['data_venda'] ?? date('Y-m-d H:i:s');
        
        // 1. Primeiro inserir na tabela VENDAS (sem imagem ainda)
        $stmt = $pdo->prepare("
            INSERT INTO vendas 
            (produto, quantidade, valor_vendido, forma_pagamento, observacoes, data_venda) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $produto, 
            $quantidade, 
            $valor_vendido,
            $forma_pagamento,
            $observacoes,
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
            }
        }
        
        // Atualizar a venda com o caminho da imagem
        $stmt = $pdo->prepare("UPDATE vendas SET imagem_comprador = ? WHERE id = ?");
        $stmt->execute([$caminho_imagem_comprador, $venda_id]);
        
        // 2. Atualizar estoque - remover produtos vendidos
        $produtos_removidos = 0;
        
        for ($i = 1; $i <= $quantidade; $i++) {
            // Buscar produto no estoque
            $produto_busca = $quantidade > 1 ? "{$produto} #{$i}" : $produto;
            
            $stmt = $pdo->prepare("SELECT id, quantidade FROM estoque WHERE produto = ? AND quantidade > 0");
            $stmt->execute([$produto_busca]);
            $produto_estoque = $stmt->fetch();
            
            if ($produto_estoque) {
                // Atualizar quantidade no estoque (remover 1 unidade)
                $nova_quantidade = $produto_estoque['quantidade'] - 1;
                $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ? WHERE id = ?");
                $stmt->execute([$nova_quantidade, $produto_estoque['id']]);
                $produtos_removidos++;
            }
        }
        
        $mensagem = "✅ Venda registrada com sucesso!";
        $mensagem .= "<br><strong>ID da Venda:</strong> $venda_id";
        $mensagem .= "<br><strong>Produto:</strong> $produto";
        $mensagem .= "<br><strong>Quantidade:</strong> $quantidade";
        $mensagem .= "<br><strong>Itens removidos do estoque:</strong> $produtos_removidos";
        $mensagem .= "<br><strong>Valor Vendido:</strong> R$ " . number_format($valor_vendido, 2, ',', '.');
        $mensagem .= "<br><strong>Valor Total:</strong> R$ " . number_format($valor_total, 2, ',', '.');
        $mensagem .= "<br><strong>Forma de Pagamento:</strong> " . ($forma_pagamento ?: 'Não informada');
        
        if ($produtos_removidos < $quantidade) {
            $mensagem .= "<br><strong>⚠️ Atenção:</strong> Apenas $produtos_removidos itens foram encontrados no estoque";
        }
        
        if ($caminho_imagem_comprador) {
            $mensagem .= "<br><strong>Foto do comprador:</strong> Salva com sucesso";
        }
        
    } catch (Exception $e) {
        $mensagem = "❌ Erro: " . $e->getMessage();
    }
}

// Buscar produtos disponíveis no estoque para sugestões
$pdo = getDB();
$produtos_estoque = $pdo->query("
    SELECT DISTINCT produto, valor_revenda 
    FROM estoque 
    WHERE quantidade > 0 
    ORDER BY produto
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Vender - COISABOA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #6b7280;
            --border: #e5e7eb;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            padding: 20px 15px 80px 15px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: var(--primary);
        }
        
        .btn-voltar {
            background: var(--gray);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-voltar:active {
            transform: translateY(-2px);
        }
        
        /* Form Container */
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        
        /* Mensagens */
        .mensagem {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 15px;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .sucesso {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .erro {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .aviso {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .required::after {
            content: " *";
            color: var(--danger);
        }
        
        input, textarea, select {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        input[type="file"] {
            padding: 12px;
            background: #f8fafc;
        }
        
        .campo-destaque {
            border-color: var(--secondary);
            background-color: #f0fdf9;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Info Texts */
        .info-text {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 8px;
            line-height: 1.4;
        }
        
        .info-text.primario {
            color: var(--primary);
        }
        
        .info-text.sucesso {
            color: var(--secondary);
        }
        
        .info-text.aviso {
            color: var(--warning);
        }
        
        /* Preview Images */
        .preview-container {
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .preview-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--border);
        }
        
        /* Info Box */
        .info-box {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            border-left: 4px solid var(--info);
            font-size: 0.9rem;
        }
        
        .estoque-box {
            background: #f0fdf9;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            border-left: 4px solid var(--secondary);
            font-size: 0.9rem;
        }
        
        /* Lista de Produtos */
        .produto-item {
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .produto-item:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }
        
        .produto-item:active {
            transform: scale(0.98);
        }
        
        .produto-nome {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .produto-valor {
            font-size: 0.85rem;
            color: var(--secondary);
            font-weight: 600;
        }
        
        /* Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:active {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            border-top: 1px solid var(--border);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--gray);
            transition: all 0.3s ease;
            flex: 1;
            padding: 8px 0;
        }
        
        .nav-item.ativo {
            color: var(--primary);
        }
        
        .nav-icon {
            font-size: 1.3rem;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .form-container {
                padding: 20px;
            }
            
            input, textarea, select {
                padding: 12px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px 10px 80px 10px;
            }
            
            .container {
                max-width: 100%;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .preview-img {
                width: 60px;
                height: 60px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header fade-in">
            <h1>
                <i class="fas fa-cash-register"></i>
                Nova Venda
            </h1>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : (strpos($mensagem, '⚠️') !== false ? 'aviso' : 'erro') ?> fade-in">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="form-container fade-in">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="vender">
                
                <?php if (!empty($produtos_estoque)): ?>
                <div class="estoque-box">
                    <strong>📦 Produtos Disponíveis no Estoque</strong>
                    <div style="margin-top: 10px; max-height: 150px; overflow-y: auto;">
                        <?php foreach ($produtos_estoque as $prod): ?>
                            <div class="produto-item" onclick="document.getElementById('produto').value = '<?= htmlspecialchars($prod['produto']) ?>'; document.getElementById('valor_vendido').value = '<?= $prod['valor_revenda'] ?>'; calcularValorTotal();">
                                <div class="produto-nome"><?= htmlspecialchars($prod['produto']) ?></div>
                                <div class="produto-valor">R$ <?= number_format($prod['valor_revenda'], 2, ',', '.') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="info-text" style="margin-top: 10px;">💡 Clique em um produto para preencher automaticamente</div>
                </div>
                <?php else: ?>
                <div class="mensagem aviso">
                    <strong>⚠️ Estoque Vazio</strong><br>
                    Não há produtos disponíveis no estoque. Faça uma compra primeiro.
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="required">Nome do Produto</label>
                    <input type="text" name="produto" id="produto" placeholder="Ex: iPhone 14, Tênis Nike, etc." required list="produtos-sugestoes">
                    <datalist id="produtos-sugestoes">
                        <?php foreach ($produtos_estoque as $prod): ?>
                            <option value="<?= htmlspecialchars($prod['produto']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div class="info-text">Digite o nome exato do produto do estoque</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Quantidade</label>
                        <input type="number" name="quantidade" id="quantidade" value="1" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Data da Venda</label>
                        <input type="datetime-local" name="data_venda" value="<?= date('Y-m-d\TH:i') ?>">
                        <div class="info-text">Data e hora da venda</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="required">Valor Vendido (R$)</label>
                    <input type="number" name="valor_vendido" step="0.01" min="0" placeholder="0.00" required id="valor_vendido" class="campo-destaque">
                    <div class="info-text primario">Valor recebido pela venda</div>
                </div>
                
                <div class="form-group">
                    <label>Valor Total Calculado</label>
                    <input type="text" id="valorTotalDisplay" disabled style="background: #f8fafc; font-weight: bold; color: var(--dark);">
                    <div class="info-text sucesso" id="infoValorTotal">O valor total será calculado automaticamente</div>
                </div>
                
                <div class="form-group">
                    <label>Forma de Pagamento</label>
                    <select name="forma_pagamento">
                        <option value="">Selecione a forma de pagamento...</option>
                        <option value="dinheiro">💵 Dinheiro</option>
                        <option value="cartao_credito">💳 Cartão de Crédito</option>
                        <option value="cartao_debito">🏦 Cartão de Débito</option>
                        <option value="pix">📱 PIX</option>
                        <option value="transferencia">🔁 Transferência</option>
                        <option value="boleto">📄 Boleto</option>
                        <option value="outro">❓ Outro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observações</label>
                    <textarea name="observacoes" rows="4" placeholder="Observações adicionais sobre a venda..."></textarea>
                    <div class="info-text">Opcional: informações extras sobre a venda</div>
                </div>
                
                <div class="form-group">
                    <label>Foto do Comprador/Comprovante</label>
                    <input type="file" name="imagem_comprador" accept="image/*" capture="camera" id="imagemComprador">
                    <div class="info-text">Foto do comprador ou comprovante de recebimento</div>
                    <div class="preview-container" id="previewComprador"></div>
                </div>
                
                <button type="submit" class="btn-submit" <?= empty($produtos_estoque) ? 'disabled' : '' ?>>
                    <i class="fas fa-cash-register"></i>
                    <?= empty($produtos_estoque) ? 'Estoque Vazio' : 'Registrar Venda' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="compras.php" class="nav-item">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Compras</span>
        </a>
        <a href="vendas.php" class="nav-item ativo">
            <span class="nav-icon">🏷️</span>
            <span class="nav-label">Vendas</span>
        </a>
        <a href="estoque.php" class="nav-item">
            <span class="nav-icon">📦</span>
            <span class="nav-label">Estoque</span>
        </a>
    </nav>

    <script>
        // Cálculo automático do valor total
        document.addEventListener('DOMContentLoaded', function() {
            const quantidade = document.querySelector('input[name="quantidade"]');
            const valorVendido = document.querySelector('input[name="valor_vendido"]');
            const valorTotalDisplay = document.getElementById('valorTotalDisplay');
            const infoValorTotal = document.getElementById('infoValorTotal');
            
            function calcularValorTotal() {
                const qtd = parseFloat(quantidade.value) || 0;
                const valor = parseFloat(valorVendido.value) || 0;
                const total = qtd * valor;
                
                if (total > 0) {
                    valorTotalDisplay.value = 'R$ ' + total.toFixed(2).replace('.', ',');
                    infoValorTotal.innerHTML = '<strong>Quantidade:</strong> ' + qtd + ' × <strong>Valor Vendido:</strong> R$ ' + valor.toFixed(2).replace('.', ',') + ' = <strong>Total: R$ ' + total.toFixed(2).replace('.', ',') + '</strong>';
                    infoValorTotal.style.color = 'var(--secondary)';
                } else {
                    valorTotalDisplay.value = '';
                    infoValorTotal.textContent = 'O valor total será calculado automaticamente';
                    infoValorTotal.style.color = 'var(--primary)';
                }
            }
            
            quantidade.addEventListener('input', calcularValorTotal);
            valorVendido.addEventListener('input', calcularValorTotal);
            
            // Preview da imagem
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
            
            // Calcular inicialmente
            calcularValorTotal();
            
            // Feedback tátil para elementos interativos
            const interactiveElements = document.querySelectorAll('input, select, textarea, button, .produto-item');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>