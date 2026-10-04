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
    
    // Buscar compras do banco de dados
    $stmt = $pdo->query("
        SELECT 
            id,
            produto,
            quantidade,
            valor_unitario,
            valor_total,
            data_compra,
            forma_pagamento
        FROM compras 
        ORDER BY data_compra DESC 
        LIMIT 50
    ");
    $compras = $stmt->fetchAll();
    
    // Processar desfazer compra
    if ($_POST['action'] ?? '' === 'desfazer_compra') {
        $compra_id = $_POST['compra_id'];
        $quantidade_remover = intval($_POST['quantidade'] ?? 0);
        
        // Buscar dados da compra
        $stmt = $pdo->prepare("SELECT * FROM compras WHERE id = ?");
        $stmt->execute([$compra_id]);
        $compra = $stmt->fetch();
        
        if (!$compra) {
            throw new Exception("Compra não encontrada!");
        }
        
        // Validar quantidade
        if ($quantidade_remover <= 0) {
            throw new Exception("Quantidade deve ser maior que zero!");
        }
        
        if ($quantidade_remover > $compra['quantidade']) {
            throw new Exception("Quantidade a remover ({$quantidade_remover}) é maior que a quantidade comprada ({$compra['quantidade']})!");
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // 1. Remover produtos individuais do estoque
        $produtos_removidos = 0;
        
        for ($i = 1; $i <= $quantidade_remover; $i++) {
            $produto_nome = $compra['quantidade'] > 1 ? "{$compra['produto']} #{$i}" : $compra['produto'];
            
            $stmt = $pdo->prepare("DELETE FROM estoque WHERE produto = ? AND compra_origem_id = ? LIMIT 1");
            $stmt->execute([$produto_nome, $compra_id]);
            
            if ($stmt->rowCount() > 0) {
                $produtos_removidos++;
            }
        }
        
        // 2. Atualizar ou excluir a compra
        if ($quantidade_remover == $compra['quantidade']) {
            // Excluir compra completamente
            $stmt = $pdo->prepare("DELETE FROM compras WHERE id = ?");
            $stmt->execute([$compra_id]);
            
            // Remover imagens associadas
            $pasta_produto = "uploads/produtos/{$compra_id}";
            $pasta_vendedor = "uploads/vendedores/{$compra_id}";
            
            if (file_exists($pasta_produto)) {
                array_map('unlink', glob("$pasta_produto/*"));
                rmdir($pasta_produto);
            }
            if (file_exists($pasta_vendedor)) {
                array_map('unlink', glob("$pasta_vendedor/*"));
                rmdir($pasta_vendedor);
            }
            
            $mensagem = "✅ Compra desfeita completamente! Produto '{$compra['produto']}' removido do sistema.";
        } else {
            // Atualizar compra com nova quantidade
            $nova_quantidade = $compra['quantidade'] - $quantidade_remover;
            $novo_valor_total = $nova_quantidade * $compra['valor_unitario'];
            
            $stmt = $pdo->prepare("
                UPDATE compras SET 
                quantidade = ?, 
                valor_total = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nova_quantidade, $novo_valor_total, $compra_id]);
            
            $mensagem = "✅ Compra parcialmente desfeita! {$quantidade_remover} unidade(s) removida(s) de '{$compra['produto']}'. Nova quantidade: {$nova_quantidade}.";
        }
        
        // Registrar no histórico
        $stmt = $pdo->prepare("
            INSERT INTO desfazer_compras 
            (compra_id, produto, quantidade_original, quantidade_removida, valor_total, data_desfazer) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $compra_id,
            $compra['produto'],
            $compra['quantidade'],
            $quantidade_remover,
            $compra['valor_total']
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
    <title>Desfazer Compra - COISABOA</title>
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
        
        .btn-warning {
            background: var(--warning);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completa {
            background: var(--secondary);
            color: white;
        }
        
        .status-parcial {
            background: var(--warning);
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
            
            .table th:nth-child(4),
            .table td:nth-child(4) {
                display: none;
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
                <i class="fas fa-undo"></i>
                Desfazer Compras
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
            • Remove produtos do estoque e ajusta a compra original<br>
            • Pode desfazer completamente ou parcialmente uma compra<br>
            • Itens removidos são excluídos do sistema permanentemente<br>
            • Histórico é mantido para auditoria
        </div>

        <div class="card fade-in">
            <h2><i class="fas fa-shopping-cart"></i> Compras Realizadas</h2>
            <p style="color: var(--gray); margin-bottom: 20px;">Selecione uma compra para desfazer total ou parcialmente:</p>
            
            <?php if (!empty($compras)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor Total</th>
                            <th>Data</th>
                            <th>Remover</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $compra): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($compra['produto']) ?></strong>
                                <?php if ($compra['forma_pagamento']): ?>
                                <br><small style="color: var(--gray);">💳 <?= ucfirst($compra['forma_pagamento']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= $compra['quantidade'] ?></strong> un
                            </td>
                            <td>
                                <strong>R$ <?= number_format($compra['valor_total'], 2, ',', '.') ?></strong>
                                <br><small style="color: var(--gray);">Unit: R$ <?= number_format($compra['valor_unitario'], 2, ',', '.') ?></small>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($compra['data_compra'])) ?>
                                <br><small style="color: var(--gray);"><?= date('H:i', strtotime($compra['data_compra'])) ?></small>
                            </td>
                            <td>
                                <form method="POST" class="form-desfazer">
                                    <input type="hidden" name="action" value="desfazer_compra">
                                    <input type="hidden" name="compra_id" value="<?= $compra['id'] ?>">
                                    <input type="number" name="quantidade" value="1" min="1" max="<?= $compra['quantidade'] ?>" 
                                           class="quantidade-input" required>
                            </td>
                            <td>
                                    <button type="submit" class="btn btn-warning" 
                                            onclick="return confirmarDesfazer('<?= htmlspecialchars($compra['produto']) ?>', <?= $compra['quantidade'] ?>)">
                                        <i class="fas fa-trash"></i>
                                        Remover
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
                <i class="fas fa-shopping-cart"></i>
                <h3>Nenhuma compra encontrada</h3>
                <p>Não há compras registradas no sistema.</p>
                <a href="comprar.php" class="btn" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i>
                    Fazer Primeira Compra
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Aviso Importante -->
        <div class="alerta fade-in">
            <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong><br>
            Esta ação não pode ser desfeita. Ao remover itens:
            <ul style="margin: 10px 0 10px 20px;">
                <li>Produtos serão excluídos permanentemente do estoque</li>
                <li>Imagens associadas serão removidas</li>
                <li>Valores totais serão recalculados</li>
                <li>O histórico será mantido para auditoria</li>
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
                alert(`Quantidade (${quantidade}) não pode ser maior que a quantidade comprada (${quantidadeMaxima})!`);
                return false;
            }
            
            let mensagem = '';
            if (quantidade == quantidadeMaxima) {
                mensagem = `🚨 DESFAZER COMPRA COMPLETA!\n\nVocê está prestes a remover COMPLETAMENTE a compra:\n"${produto}"\n\n• ${quantidade} unidade(s) serão removidas do estoque\n• A compra será excluída do sistema\n• Imagens serão removidas\n\nEsta ação NÃO PODE ser desfeita!\n\nConfirma a exclusão?`;
            } else {
                mensagem = `🔄 DESFAZER COMPRA PARCIAL!\n\nVocê está prestes a remover ${quantidade} unidade(s) da compra:\n"${produto}"\n\n• ${quantidade} unidade(s) serão removidas do estoque\n• A compra será atualizada para ${quantidadeMaxima - quantidade} unidade(s)\n\nConfirma a remoção?`;
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
            
            // Focar no campo de quantidade ao tocar no botão
            const quantidadeInputs = document.querySelectorAll('.quantidade-input');
            quantidadeInputs.forEach(input => {
                input.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>
</body>
</html>