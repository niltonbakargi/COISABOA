<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';
$vendas = [];

try {
    $pdo = getDB();
    
    // Buscar últimas vendas
    $stmt = $pdo->query("
        SELECT 
            v.*, 
            COALESCE(e.quantidade, 0) as estoque_atual,
            e.valor_revenda,
            v.valor_vendido as valor_venda_real
        FROM vendas v 
        LEFT JOIN estoque e ON v.produto = e.produto 
        ORDER BY v.data_venda DESC 
        LIMIT 50
    ");
    $vendas = $stmt->fetchAll();
    
    // Processar desfazer venda
    if ($_POST['action'] ?? '' === 'desfazer_venda') {
        $venda_id = $_POST['venda_id'];
        $quantidade_retornar = intval($_POST['quantidade'] ?? 0);
        
        // Buscar dados da venda
        $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
        $stmt->execute([$venda_id]);
        $venda = $stmt->fetch();
        
        if (!$venda) {
            throw new Exception("Venda não encontrada!");
        }
        
        // Validar quantidade
        if ($quantidade_retornar <= 0) {
            throw new Exception("Quantidade deve ser maior que zero!");
        }
        
        if ($quantidade_retornar > $venda['quantidade']) {
            throw new Exception("Quantidade a retornar ({$quantidade_retornar}) é maior que a quantidade vendida ({$venda['quantidade']})!");
        }
        
        // Buscar informações do produto no estoque
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE produto = ?");
        $stmt->execute([$venda['produto']]);
        $produto_estoque = $stmt->fetch();
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // 1. Retornar produtos ao estoque
        if ($produto_estoque) {
            // Produto existe no estoque - atualizar quantidade
            $nova_quantidade = $produto_estoque['quantidade'] + $quantidade_retornar;
            $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ?, data_atualizacao = NOW() WHERE produto = ?");
            $stmt->execute([$nova_quantidade, $venda['produto']]);
        } else {
            // Produto não existe - criar novo registro
            $stmt = $pdo->prepare("
                INSERT INTO estoque 
                (produto, quantidade, valor_unitario, valor_revenda, data_entrada) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            // Usar valores padrão ou da venda
            $valor_unitario = $venda['valor_vendido'] * 0.7; // Estimativa de custo (70% do valor de venda)
            $valor_revenda = $venda['valor_vendido'];
            $stmt->execute([$venda['produto'], $quantidade_retornar, $valor_unitario, $valor_revenda]);
        }
        
        // 2. Atualizar ou excluir a venda
        if ($quantidade_retornar == $venda['quantidade']) {
            // Excluir venda completamente
            $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ?");
            $stmt->execute([$venda_id]);
            
            // Remover imagem do comprador se existir
            $pasta_comprador = "uploads/compradores/{$venda_id}";
            if (file_exists($pasta_comprador)) {
                array_map('unlink', glob("$pasta_comprador/*"));
                rmdir($pasta_comprador);
            }
            
            $mensagem = "✅ Venda desfeita completamente! {$quantidade_retornar} unidade(s) de '{$venda['produto']}' retornadas ao estoque.";
        } else {
            // Atualizar venda com nova quantidade
            $nova_quantidade = $venda['quantidade'] - $quantidade_retornar;
            $novo_valor_total = $nova_quantidade * $venda['valor_vendido'];
            
            $stmt = $pdo->prepare("
                UPDATE vendas SET 
                quantidade = ?, 
                valor_vendido = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nova_quantidade, $venda['valor_vendido'], $venda_id]);
            
            $mensagem = "✅ Venda parcialmente desfeita! {$quantidade_retornar} unidade(s) de '{$venda['produto']}' retornadas ao estoque. Nova quantidade vendida: {$nova_quantidade}.";
        }
        
        // 3. Registrar no histórico
        $stmt = $pdo->prepare("
            INSERT INTO desfazer_vendas 
            (venda_id, produto, quantidade_original, quantidade_retornada, valor_venda, data_desfazer) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $venda_id,
            $venda['produto'],
            $venda['quantidade'],
            $quantidade_retornar,
            $venda['valor_vendido']
        ]);
        
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Desfazer Vendas - COISABOA</title>
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
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 20px;
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
        
        /* Form Elements */
        .form-group {
            margin-bottom: 15px;
        }
        
        .quantidade-input {
            width: 80px;
            padding: 10px;
            border: 2px solid var(--border);
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        
        .quantidade-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Buttons */
        .btn {
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .btn:active {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-warning {
            background: var(--warning);
        }
        
        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .status-em-estoque {
            background: var(--secondary);
            color: white;
        }
        
        .status-sem-estoque {
            background: var(--danger);
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 12px 8px;
            }
            
            .quantidade-input {
                width: 60px;
                padding: 8px;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 0.8rem;
            }
            
            /* Hide some columns on mobile */
            .table th:nth-child(5),
            .table td:nth-child(5),
            .table th:nth-child(6),
            .table td:nth-child(6) {
                display: none;
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
                <i class="fas fa-undo-alt"></i>
                Desfazer Vendas
            </h1>
            <a href="gerenciar.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?> fade-in">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <!-- Informações Importantes -->
        <div class="info-box fade-in">
            <strong><i class="fas fa-info-circle"></i> Como funciona:</strong><br>
            • Retorna produtos ao estoque e ajusta/remove a venda<br>
            • Pode desfazer completamente ou parcialmente uma venda<br>
            • Produtos são adicionados de volta ao estoque automaticamente<br>
            • Histórico é mantido para auditoria e controle
        </div>

        <div class="card fade-in">
            <h2><i class="fas fa-cash-register"></i> Vendas Realizadas</h2>
            <p style="color: var(--gray); margin-bottom: 20px;">Selecione uma venda para desfazer total ou parcialmente:</p>
            
            <?php if (!empty($vendas)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor</th>
                            <th>Estoque Atual</th>
                            <th>Retornar</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td>
                                <?= date('d/m/Y', strtotime($venda['data_venda'])) ?>
                                <br><small style="color: var(--gray);"><?= date('H:i', strtotime($venda['data_venda'])) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($venda['produto']) ?></strong>
                                <?php if ($venda['forma_pagamento']): ?>
                                <br><small style="color: var(--gray);">💳 <?= ucfirst($venda['forma_pagamento']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= $venda['quantidade'] ?></strong> un
                            </td>
                            <td>
                                <strong>R$ <?= number_format($venda['valor_vendido'], 2, ',', '.') ?></strong>
                                <?php if ($venda['quantidade'] > 1): ?>
                                <br><small style="color: var(--gray);">Unit: R$ <?= number_format($venda['valor_vendido'] / $venda['quantidade'], 2, ',', '.') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($venda['estoque_atual'] > 0): ?>
                                    <span class="status-indicator status-em-estoque">
                                        <i class="fas fa-check"></i>
                                        <?= $venda['estoque_atual'] ?> un
                                    </span>
                                <?php else: ?>
                                    <span class="status-indicator status-sem-estoque">
                                        <i class="fas fa-times"></i>
                                        Sem estoque
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="form-desfazer">
                                    <input type="hidden" name="action" value="desfazer_venda">
                                    <input type="hidden" name="venda_id" value="<?= $venda['id'] ?>">
                                    <input type="number" name="quantidade" value="<?= $venda['quantidade'] ?>" min="1" max="<?= $venda['quantidade'] ?>" 
                                           class="quantidade-input" required>
                            </td>
                            <td>
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirmarDesfazer('<?= htmlspecialchars($venda['produto']) ?>', <?= $venda['quantidade'] ?>)">
                                        <i class="fas fa-undo-alt"></i>
                                        Desfazer
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-cash-register"></i>
                <h3>Nenhuma venda encontrada</h3>
                <p>Não há vendas registradas no sistema.</p>
                <a href="vender.php" class="btn" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i>
                    Realizar Primeira Venda
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Aviso Importante -->
        <div class="alerta fade-in">
            <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong><br>
            Esta ação afeta diretamente o estoque e o histórico de vendas:
            <ul style="margin: 10px 0 10px 20px;">
                <li>Produtos serão adicionados de volta ao estoque</li>
                <li>Vendas serão removidas ou ajustadas</li>
                <li>Imagens de compradores serão excluídas</li>
                <li>O histórico de desfazer será mantido</li>
            </ul>
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
        function confirmarDesfazer(produto, quantidadeMaxima) {
            const form = event.target.closest('form');
            const quantidade = form.querySelector('input[name="quantidade"]').value;
            
            if (quantidade <= 0) {
                alert('Quantidade deve ser maior que zero!');
                return false;
            }
            
            if (quantidade > quantidadeMaxima) {
                alert(`Quantidade (${quantidade}) não pode ser maior que a quantidade vendida (${quantidadeMaxima})!`);
                return false;
            }
            
            let mensagem = '';
            if (quantidade == quantidadeMaxima) {
                mensagem = `🚨 DESFAZER VENDA COMPLETA!\n\nVocê está prestes a desfazer COMPLETAMENTE a venda:\n"${produto}"\n\n• ${quantidade} unidade(s) retornarão ao estoque\n• A venda será excluída do sistema\n• Imagens do comprador serão removidas\n\nEsta ação NÃO PODE ser desfeita!\n\nConfirma a exclusão?`;
            } else {
                mensagem = `🔄 DESFAZER VENDA PARCIAL!\n\nVocê está prestes a desfazer ${quantidade} unidade(s) da venda:\n"${produto}"\n\n• ${quantidade} unidade(s) retornarão ao estoque\n• A venda será ajustada para ${quantidadeMaxima - quantidade} unidade(s)\n\nConfirma a operação?`;
            }
            
            return confirm(mensagem);
        }
        
        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback tátil para elementos interativos
            const interactiveElements = document.querySelectorAll('.btn, .quantidade-input');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Auto-selecionar quantidade máxima ao focar no campo
            const quantidadeInputs = document.querySelectorAll('.quantidade-input');
            quantidadeInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.select();
                });
                
                input.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>
</body>
</html>