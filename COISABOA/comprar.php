<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
if (isset($_SESSION['flash_sucesso'])) {
    $mensagem = '✅ ' . $_SESSION['flash_sucesso'];
    unset($_SESSION['flash_sucesso']);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_POST['action'] ?? '' === 'comprar') {
    try {
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Erro de segurança. Recarregue a página e tente novamente.");
        }

        $pdo = getDB();

        $produto = trim($_POST['produto'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $valor_unitario = (float)($_POST['valor_unitario'] ?? 0);
        $valor_total = $quantidade * $valor_unitario;
        $valor_revenda = (float)($_POST['valor_revenda'] ?? 0);
        $forma_pagamento = trim($_POST['forma_pagamento'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');
        $data_compra = $_POST['data_compra'] ?? date('Y-m-d H:i:s');

        if (empty($produto)) throw new Exception("Nome do produto é obrigatório.");
        if ($quantidade <= 0) throw new Exception("Quantidade deve ser maior que zero.");
        if ($valor_unitario < 0) throw new Exception("Valor unitário não pode ser negativo.");
        if ($valor_revenda < 0) throw new Exception("Valor de revenda não pode ser negativo.");
        
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
                    $extensao = strtolower(pathinfo($_FILES['imagens']['name'][$key], PATHINFO_EXTENSION));
                    $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo     = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);

                    if (!in_array($extensao, $allowed_ext) || !in_array($mime_type, $allowed_mime)) {
                        continue;
                    }

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
            $extensao_vendedor = strtolower(pathinfo($_FILES['imagem_vendedor']['name'], PATHINFO_EXTENSION));
            $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo        = finfo_open(FILEINFO_MIME_TYPE);
            $mime_vendedor = finfo_file($finfo, $_FILES['imagem_vendedor']['tmp_name']);
            finfo_close($finfo);

            if (in_array($extensao_vendedor, $allowed_ext) && in_array($mime_vendedor, $allowed_mime)) {
                $pasta_vendedor = "uploads/vendedores/$compra_id";
                if (!file_exists($pasta_vendedor)) {
                    mkdir($pasta_vendedor, 0755, true);
                }

                $nome_arquivo_vendedor = "vendedor." . $extensao_vendedor;
                $destino_vendedor = "$pasta_vendedor/$nome_arquivo_vendedor";

                if (move_uploaded_file($_FILES['imagem_vendedor']['tmp_name'], $destino_vendedor)) {
                    $caminho_imagem_vendedor = $pasta_vendedor;
                }
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
        
        $_SESSION['flash_sucesso'] = "Compra #{$compra_id} registrada! {$produto} × {$quantidade} — R$ " . number_format($valor_total, 2, ',', '.') . ". {$produtos_inseridos} item(ns) adicionado(s) ao estoque.";
        header('Location: comprar.php');
        exit;

    } catch (Exception $e) {
        $mensagem = "❌ Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Comprar - COISABOA</title>
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
        
        /* Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--secondary), #059669);
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
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
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
                <i class="fas fa-shopping-bag"></i>
                Nova Compra
            </h1>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?> fade-in">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="form-container fade-in">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="comprar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label class="required">Nome do Produto</label>
                    <input type="text" name="produto" id="produto" placeholder="Ex: iPhone 14, Tênis Nike, etc." required>
                    <div class="info-text">Digite o nome completo do produto</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Quantidade</label>
                        <input type="number" name="quantidade" id="quantidade" value="1" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Data da Compra</label>
                        <input type="datetime-local" name="data_compra" value="<?= date('Y-m-d\TH:i') ?>">
                        <div class="info-text">Data e hora da compra</div>
                    </div>
                </div>
                
                <div id="infoIndividual" class="info-box" style="display: none;">
                    <strong>💡 Sistema de Itens Individuais</strong><br>
                    Cada unidade será cadastrada separadamente no estoque com numeração automática.
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Valor Unitário (R$)</label>
                        <input type="number" name="valor_unitario" step="0.01" min="0" placeholder="0.00" required id="valorUnitario">
                        <div class="info-text">Valor pago por cada unidade</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Valor Revenda (R$)</label>
                        <input type="number" name="valor_revenda" step="0.01" min="0" placeholder="0.00" required class="campo-destaque">
                        <div class="info-text primario">Valor que você pretende vender</div>
                    </div>
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
                    <textarea name="observacoes" rows="4" placeholder="Observações adicionais sobre a compra..."></textarea>
                    <div class="info-text">Opcional: informações extras sobre a compra</div>
                </div>
                
                <div class="form-group">
                    <label>Fotos do Produto</label>
                    <input type="file" name="imagens[]" multiple accept="image/*" capture="camera" id="imagensProduto">
                    <div class="info-text">Selecione várias fotos do produto</div>
                    <div class="info-text">💡 Segure para selecionar múltiplas imagens</div>
                    <div class="preview-container" id="previewProduto"></div>
                </div>
                
                <div class="form-group">
                    <label>Foto do Vendedor/Comprovante</label>
                    <input type="file" name="imagem_vendedor" accept="image/*" capture="camera" id="imagemVendedor">
                    <div class="info-text">Foto do vendedor ou comprovante de pagamento</div>
                    <div class="preview-container" id="previewVendedor"></div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>
                    Salvar Compra
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
        <a href="comprar.php" class="nav-item ativo">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Compras</span>
        </a>
        <a href="vender.php" class="nav-item">
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
                    infoValorTotal.innerHTML = '<strong>Quantidade:</strong> ' + qtd + ' × <strong>Valor Unitário:</strong> R$ ' + valor.toFixed(2).replace('.', ',') + ' = <strong>Total: R$ ' + total.toFixed(2).replace('.', ',') + '</strong>';
                    infoValorTotal.style.color = 'var(--secondary)';
                } else {
                    valorTotalDisplay.value = '';
                    infoValorTotal.textContent = 'O valor total será calculado automaticamente';
                    infoValorTotal.style.color = 'var(--primary)';
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

            // Desabilitar botão no submit para evitar duplo envio
            document.querySelector('form').addEventListener('submit', function() {
                const btn = this.querySelector('.btn-submit');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                }
            });

            // Feedback tátil para elementos interativos
            const interactiveElements = document.querySelectorAll('input, select, textarea, button');
            
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