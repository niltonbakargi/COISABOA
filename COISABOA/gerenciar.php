<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
$acao = $_POST['acao'] ?? '';
$produto_id = $_POST['produto_id'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Buscar produtos em estoque
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM estoque ORDER BY produto");
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    $produtos = [];
    $erro = "Erro ao carregar produtos: " . $e->getMessage();
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($acao) && !empty($produto_id)) {
    try {
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Erro de segurança. Recarregue a página e tente novamente.");
        }

        $pdo = getDB();
        
        // Buscar informações do produto
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) {
            throw new Exception("Produto não encontrado!");
        }
        
        $quantidade = $_POST['quantidade'] ?? 0;
        $observacoes = $_POST['observacoes'] ?? '';
        
        switch ($acao) {
            case 'ajustar_estoque':
                $nova_quantidade = $_POST['nova_quantidade'] ?? 0;
                if ($nova_quantidade < 0) {
                    throw new Exception("A quantidade não pode ser negativa!");
                }
                
                // Registrar ajuste no histórico
                $stmt = $pdo->prepare("
                    INSERT INTO ajustes_estoque 
                    (produto_id, produto_nome, quantidade_anterior, quantidade_nova, observacoes, data_ajuste) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $produto_id,
                    $produto['produto'],
                    $produto['quantidade'],
                    $nova_quantidade,
                    $observacoes
                ]);
                
                // Atualizar estoque
                $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ?, data_atualizacao = NOW() WHERE id = ?");
                $stmt->execute([$nova_quantidade, $produto_id]);
                
                $mensagem = "✅ Estoque ajustado! De {$produto['quantidade']} para $nova_quantidade unidades";
                break;
                
            case 'excluir_produto':
                $confirmacao = $_POST['confirmacao'] ?? '';
                
                if ($confirmacao !== 'CONFIRMAR') {
                    throw new Exception("Digite 'CONFIRMAR' para excluir o produto!");
                }
                
                // Registrar exclusão no histórico
                $stmt = $pdo->prepare("
                    INSERT INTO exclusoes_estoque 
                    (produto_id, produto_nome, quantidade_final, valor_unitario, valor_revenda, observacoes, data_exclusao) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $produto_id,
                    $produto['produto'],
                    $produto['quantidade'],
                    $produto['valor_unitario'],
                    $produto['valor_revenda'],
                    $observacoes
                ]);
                
                // Excluir do estoque (mantém compras e vendas)
                $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
                $stmt->execute([$produto_id]);
                
                $mensagem = "✅ Produto '{$produto['produto']}' excluído do estoque!";
                break;
                
            case 'corrigir_precos':
                $novo_valor_unitario = $_POST['novo_valor_unitario'] ?? 0;
                $novo_valor_revenda = $_POST['novo_valor_revenda'] ?? 0;
                
                if ($novo_valor_unitario < 0 || $novo_valor_revenda < 0) {
                    throw new Exception("Os valores não podem ser negativos!");
                }
                
                // Registrar correção no histórico
                $stmt = $pdo->prepare("
                    INSERT INTO correcoes_preco 
                    (produto_id, produto_nome, valor_unitario_anterior, valor_unitario_novo, 
                     valor_revenda_anterior, valor_revenda_novo, observacoes, data_correcao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $produto_id,
                    $produto['produto'],
                    $produto['valor_unitario'],
                    $novo_valor_unitario,
                    $produto['valor_revenda'],
                    $novo_valor_revenda,
                    $observacoes
                ]);
                
                // Atualizar preços
                $stmt = $pdo->prepare("
                    UPDATE estoque SET 
                    valor_unitario = ?, 
                    valor_revenda = ?, 
                    data_atualizacao = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$novo_valor_unitario, $novo_valor_revenda, $produto_id]);
                
                $mensagem = "✅ Preços atualizados!";
                break;
                
            default:
                throw new Exception("Ação inválida!");
        }
        
    } catch (Exception $e) {
        $mensagem = "❌ " . $e->getMessage();
    }
}

// Buscar histórico de operações (últimas 30)
try {
    $pdo = getDB();
    
    // Histórico de ajustes
    $stmt_ajustes = $pdo->query("
        SELECT 'ajuste' as tipo, produto_nome, quantidade_anterior, quantidade_nova, 
               observacoes, data_ajuste as data
        FROM ajustes_estoque 
        ORDER BY data_ajuste DESC 
        LIMIT 10
    ");
    $historico_ajustes = $stmt_ajustes->fetchAll();
    
    // Histórico de exclusões
    $stmt_exclusoes = $pdo->query("
        SELECT 'exclusao' as tipo, produto_nome, quantidade_final, 
               observacoes, data_exclusao as data
        FROM exclusoes_estoque 
        ORDER BY data_exclusao DESC 
        LIMIT 10
    ");
    $historico_exclusoes = $stmt_exclusoes->fetchAll();
    
    // Histórico de correções de preço
    $stmt_correcoes = $pdo->query("
        SELECT 'correcao' as tipo, produto_nome, valor_unitario_anterior, valor_unitario_novo,
               valor_revenda_anterior, valor_revenda_novo, observacoes, data_correcao as data
        FROM correcoes_preco 
        ORDER BY data_correcao DESC 
        LIMIT 10
    ");
    $historico_correcoes = $stmt_correcoes->fetchAll();
    
    // Combinar histórico
    $historico = array_merge($historico_ajustes, $historico_exclusoes, $historico_correcoes);
    usort($historico, function($a, $b) {
        return strtotime($b['data']) - strtotime($a['data']);
    });
    $historico = array_slice($historico, 0, 15);
    
} catch (PDOException $e) {
    $historico = [];
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
    <title>Gerenciar - COISABOA</title>
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
            max-width: 1200px;
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
        
        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        input, select, textarea {
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
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .tab {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 140px;
            justify-content: center;
        }
        
        .tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary-dark);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Info Box */
        .info-box {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 4px solid var(--info);
            font-size: 0.9rem;
        }
        
        .alerta {
            background: #fef3c7;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 4px solid var(--warning);
            font-size: 0.9rem;
        }
        
        .alerta.perigo {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
        }
        
        /* Buttons */
        .btn {
            background: var(--primary);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
            font-size: 1rem;
        }
        
        .btn:active {
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--secondary);
        }
        
        .btn-warning {
            background: var(--warning);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table th {
            background: var(--dark);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-ajuste {
            background: var(--info);
            color: white;
        }
        
        .badge-exclusao {
            background: var(--danger);
            color: white;
        }
        
        .badge-correcao {
            background: var(--warning);
            color: white;
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
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                min-width: auto;
                justify-content: flex-start;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 12px 8px;
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
            
            .card {
                padding: 20px;
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
                <i class="fas fa-cogs"></i>
                Gerenciar Estoque
            </h1>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <?php if (isset($erro)): ?>
            <div class="mensagem erro fade-in">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?> fade-in">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <!-- Seleção de Produto -->
        <div class="card fade-in">
            <div class="form-group">
                <label class="required">Selecione o Produto</label>
                <select id="produto_id" name="produto_id" onchange="carregarProduto(this.value)" required>
                    <option value="">Selecione um produto...</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?= $produto['id'] ?>">
                            <?= htmlspecialchars($produto['produto']) ?> - Estoque: <?= $produto['quantidade'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="info-produto" class="info-box">
                <i class="fas fa-info-circle"></i>
                Selecione um produto para ver as informações atuais
            </div>
        </div>

        <!-- Tabs de Navegação -->
        <div class="tabs fade-in">
            <div class="tab active" onclick="mostrarTab('ajustar')">
                <i class="fas fa-boxes"></i>
                Ajustar Estoque
            </div>
            <div class="tab" onclick="mostrarTab('precos')">
                <i class="fas fa-money-bill-wave"></i>
                Corrigir Preços
            </div>
            <div class="tab" onclick="mostrarTab('excluir')">
                <i class="fas fa-trash"></i>
                Excluir Produto
            </div>
            <div class="tab" onclick="mostrarTab('historico')">
                <i class="fas fa-history"></i>
                Histórico
            </div>
        </div>

        <!-- Conteúdo das Tabs -->

        <!-- TAB: Ajustar Estoque -->
        <div id="tab-ajustar" class="tab-content active fade-in">
            <div class="card">
                <h2><i class="fas fa-boxes"></i> Ajuste de Estoque</h2>
                <div class="alerta">
                    <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong> 
                    Use esta função para corrigir divergências de estoque. O histórico ficará registrado para auditoria.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="ajustar_estoque">
                    <input type="hidden" name="produto_id" id="produto_id_ajustar">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label class="required">Nova Quantidade em Estoque</label>
                        <input type="number" name="nova_quantidade" id="nova_quantidade" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Motivo do Ajuste</label>
                        <textarea name="observacoes" rows="3" placeholder="Ex: Inventário físico, perda, divergência..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-edit"></i>
                        Aplicar Ajuste
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB: Corrigir Preços -->
        <div id="tab-precos" class="tab-content fade-in">
            <div class="card">
                <h2><i class="fas fa-money-bill-wave"></i> Correção de Preços</h2>
                <div class="alerta">
                    <strong><i class="fas fa-info-circle"></i> Informação:</strong> 
                    Altere os valores de custo e revenda do produto.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="corrigir_precos">
                    <input type="hidden" name="produto_id" id="produto_id_precos">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required">Novo Valor Unitário (Custo)</label>
                            <input type="number" name="novo_valor_unitario" id="novo_valor_unitario" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Novo Valor de Revenda</label>
                            <input type="number" name="novo_valor_revenda" id="novo_valor_revenda" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Motivo da Correção</label>
                        <textarea name="observacoes" rows="3" placeholder="Ex: Reajuste de preço, promoção..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt"></i>
                        Atualizar Preços
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB: Excluir Produto -->
        <div id="tab-excluir" class="tab-content fade-in">
            <div class="card">
                <h2><i class="fas fa-trash"></i> Exclusão de Produto</h2>
                <div class="alerta perigo">
                    <strong><i class="fas fa-exclamation-triangle"></i> ATENÇÃO CRÍTICA:</strong><br>
                    • O produto será removido APENAS do estoque atual<br>
                    • Todas as vendas e compras anteriores serão mantidas<br>
                    • O produto não aparecerá mais para novas vendas<br>
                    • Esta ação NÃO PODE ser desfeita<br>
                    • Registro será mantido para auditoria
                </div>
                
                <form method="POST" onsubmit="return confirmarExclusao()">
                    <input type="hidden" name="acao" value="excluir_produto">
                    <input type="hidden" name="produto_id" id="produto_id_excluir">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label class="required">Digite CONFIRMAR para prosseguir</label>
                        <input type="text" name="confirmacao" required 
                               placeholder="Digite CONFIRMAR aqui..." 
                               style="text-transform: uppercase; font-weight: bold; text-align: center;">
                    </div>
                    
                    <div class="form-group">
                        <label>Motivo da Exclusão</label>
                        <textarea name="observacoes" rows="3" placeholder="Ex: Produto descontinuado, erro de cadastro..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Excluir do Estoque
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB: Histórico -->
        <div id="tab-historico" class="tab-content fade-in">
            <div class="card">
                <h2><i class="fas fa-history"></i> Histórico de Operações</h2>
                <p>Últimas 15 operações de gerenciamento</p>
                
                <?php if (!empty($historico)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Produto</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $operacao): 
                                $badge_class = '';
                                $detalhes = '';
                                $icone = '';
                                
                                switch ($operacao['tipo']) {
                                    case 'ajuste':
                                        $badge_class = 'badge-ajuste';
                                        $detalhes = "De {$operacao['quantidade_anterior']} para {$operacao['quantidade_nova']} unidades";
                                        $icone = 'fas fa-boxes';
                                        break;
                                    case 'exclusao':
                                        $badge_class = 'badge-exclusao';
                                        $detalhes = "Estoque final: {$operacao['quantidade_final']} unidades";
                                        $icone = 'fas fa-trash';
                                        break;
                                    case 'correcao':
                                        $badge_class = 'badge-correcao';
                                        $detalhes = "Custo: R$ {$operacao['valor_unitario_anterior']} → R$ {$operacao['valor_unitario_novo']}";
                                        $icone = 'fas fa-money-bill-wave';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($operacao['data'])) ?></td>
                                <td>
                                    <span class="badge <?= $badge_class ?>">
                                        <i class="<?= $icone ?>"></i>
                                        <?= strtoupper($operacao['tipo']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($operacao['produto_nome']) ?></td>
                                <td><?= $detalhes ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: var(--gray);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3>Nenhuma operação registrada</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="comprar.php" class="nav-item">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Compras</span>
        </a>
        <a href="vender.php" class="nav-item">
            <span class="nav-icon">🏷️</span>
            <span class="nav-label">Vendas</span>
        </a>
        <a href="gerenciar.php" class="nav-item ativo">
            <span class="nav-icon">⚙️</span>
            <span class="nav-label">Gerenciar</span>
        </a>
    </nav>

    <script>
        function mostrarTab(tabId) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(conteudo => {
                conteudo.classList.remove('active');
            });
            
            // Mostrar tab selecionada
            document.querySelector(`.tab[onclick="mostrarTab('${tabId}')"]`).classList.add('active');
            document.getElementById(`tab-${tabId}`).classList.add('active');
        }
        
        function carregarProduto(produtoId) {
            if (!produtoId) return;
            
            // Simular carregamento de dados do produto
            const produtoSelect = document.getElementById('produto_id');
            const produtoTexto = produtoSelect.options[produtoSelect.selectedIndex].text;
            
            // Em uma implementação real, você faria uma requisição AJAX aqui
            document.getElementById('info-produto').innerHTML = `
                <i class="fas fa-info-circle"></i>
                <strong>Produto selecionado:</strong> ${produtoTexto}
            `;
            
            // Sincronizar entre formulários
            document.getElementById('produto_id_ajustar').value = produtoId;
            document.getElementById('produto_id_precos').value = produtoId;
            document.getElementById('produto_id_excluir').value = produtoId;
        }
        
        function confirmarExclusao() {
            const produtoSelect = document.getElementById('produto_id');
            if (!produtoSelect.value) {
                alert('Selecione um produto primeiro!');
                return false;
            }
            
            const produtoNome = produtoSelect.options[produtoSelect.selectedIndex].text;
            return confirm(`🚨 ATENÇÃO!\n\nVocê está prestes a excluir o produto:\n"${produtoNome}"\n\nEsta ação NÃO PODE ser desfeita!\n\nDigite CONFIRMAR no campo abaixo para prosseguir.`);
        }
        
        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback tátil para elementos interativos
            const interactiveElements = document.querySelectorAll('.tab, .btn, input, select, textarea');
            
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