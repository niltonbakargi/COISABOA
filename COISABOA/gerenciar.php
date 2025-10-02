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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Estoque - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        
        .btn { 
            background: #3498db; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 14px;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .card { 
            background: white; 
            padding: 25px; 
            border-radius: 8px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        input, select, textarea { 
            padding: 10px 12px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            width: 100%; 
            font-size: 14px;
        }
        
        .mensagem { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            line-height: 1.6; 
        }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .tabela { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: white; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            font-size: 14px;
        }
        .tabela th, .tabela td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ecf0f1; 
        }
        .tabela th { 
            background: #34495e; 
            color: white; 
            font-weight: 600; 
        }
        .tabela tr:hover { background: #f8f9fa; }
        
        .abas { display: flex; gap: 10px; margin-bottom: 20px; }
        .aba { 
            padding: 15px 25px; 
            background: #ecf0f1; 
            border-radius: 5px; 
            cursor: pointer; 
            border: 2px solid transparent;
        }
        .aba.ativa { 
            background: #3498db; 
            color: white; 
            border-color: #2980b9;
        }
        
        .conteudo-aba { display: none; }
        .conteudo-aba.ativa { display: block; }
        
        .info-produto { 
            background: #e8f4fd; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border-left: 4px solid #3498db;
        }
        
        .badge { 
            display: inline-block; 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: bold; 
            margin-left: 5px; 
        }
        .badge-ajuste { background: #3498db; color: white; }
        .badge-exclusao { background: #e74c3c; color: white; }
        .badge-correcao { background: #f39c12; color: white; }
        
        .alerta { 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            border-radius: 8px; 
            padding: 15px; 
            margin: 15px 0; 
        }
        
        @media (max-width: 768px) {
            .abas { flex-direction: column; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
    <script>
        function mostrarAba(abaId) {
            // Esconder todas as abas
            document.querySelectorAll('.aba').forEach(aba => {
                aba.classList.remove('ativa');
            });
            document.querySelectorAll('.conteudo-aba').forEach(conteudo => {
                conteudo.classList.remove('ativa');
            });
            
            // Mostrar aba selecionada
            document.getElementById('aba-' + abaId).classList.add('ativa');
            document.getElementById('conteudo-' + abaId).classList.add('ativa');
        }
        
        function carregarProduto(produtoId) {
            if (!produtoId) return;
            
            // Buscar informações do produto via AJAX
            fetch('ajax_produto.php?id=' + produtoId)
                .then(response => response.json())
                .then(produto => {
                    document.getElementById('info-produto').innerHTML = `
                        <strong>Produto:</strong> ${produto.produto}<br>
                        <strong>Estoque atual:</strong> ${produto.quantidade} unidades<br>
                        <strong>Valor unitário:</strong> R$ ${parseFloat(produto.valor_unitario).toFixed(2)}<br>
                        <strong>Valor revenda:</strong> R$ ${parseFloat(produto.valor_revenda).toFixed(2)}
                    `;
                    
                    // Preencher campos com valores atuais
                    document.getElementById('nova_quantidade').value = produto.quantidade;
                    document.getElementById('novo_valor_unitario').value = produto.valor_unitario;
                    document.getElementById('novo_valor_revenda').value = produto.valor_revenda;
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
        }
        
        function confirmarExclusao() {
            const produtoSelect = document.getElementById('produto_id');
            const produtoNome = produtoSelect.options[produtoSelect.selectedIndex].text;
            return confirm(`ATENÇÃO! Você está prestes a excluir o produto "${produtoNome}" do estoque.\n\nEsta ação não pode ser desfeita! Digite CONFIRMAR no campo abaixo para prosseguir.`);
        }
        
        // Mostrar primeira aba ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            mostrarAba('ajustar');
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Gerenciar Estoque - COISABOA</h1>
            <div style="display: flex; gap: 10px;">
                <a href="relatorios.php" class="btn">📊 Relatórios</a>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">🏠 Dashboard</a>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="mensagem erro">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <!-- Abas de Navegação -->
        <div class="abas">
            <div class="aba ativa" id="aba-ajustar" onclick="mostrarAba('ajustar')">
                📦 Ajustar Estoque
            </div>
            <div class="aba" id="aba-precos" onclick="mostrarAba('precos')">
                💰 Corrigir Preços
            </div>
            <div class="aba" id="aba-excluir" onclick="mostrarAba('excluir')">
                🗑️ Excluir Produto
            </div>
            <div class="aba" id="aba-historico" onclick="mostrarAba('historico')">
                📋 Histórico
            </div>
        </div>

        <!-- Seleção de Produto -->
        <div class="card">
            <div class="form-group">
                <label>Selecione o Produto</label>
                <select id="produto_id" name="produto_id" onchange="carregarProduto(this.value)" required>
                    <option value="">Selecione um produto...</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?= $produto['id'] ?>">
                            <?= htmlspecialchars($produto['produto']) ?> - Estoque: <?= $produto['quantidade'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="info-produto" class="info-produto">
                Selecione um produto para ver as informações atuais
            </div>
        </div>

        <!-- Conteúdo das Abas -->

        <!-- ABA: Ajustar Estoque -->
        <div id="conteudo-ajustar" class="conteudo-aba ativa">
            <div class="card">
                <h2>📦 Ajuste de Estoque</h2>
                <p class="alerta">
                    <strong>⚠️ Atenção:</strong> Use esta função para corrigir divergências de estoque. 
                    O histórico ficará registrado para auditoria.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="ajustar_estoque">
                    <input type="hidden" name="produto_id" id="produto_id_ajustar">
                    
                    <div class="form-group">
                        <label>Nova Quantidade em Estoque</label>
                        <input type="number" name="nova_quantidade" id="nova_quantidade" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Motivo do Ajuste</label>
                        <textarea name="observacoes" rows="3" placeholder="Ex: Inventário físico, perda, divergência..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">📝 Aplicar Ajuste</button>
                </form>
            </div>
        </div>

        <!-- ABA: Corrigir Preços -->
        <div id="conteudo-precos" class="conteudo-aba">
            <div class="card">
                <h2>💰 Correção de Preços</h2>
                <p class="alerta">
                    <strong>💡 Informação:</strong> Altere os valores de custo e revenda do produto.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="corrigir_precos">
                    <input type="hidden" name="produto_id" id="produto_id_precos">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Novo Valor Unitário (Custo)</label>
                            <input type="number" name="novo_valor_unitario" id="novo_valor_unitario" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Novo Valor de Revenda</label>
                            <input type="number" name="novo_valor_revenda" id="novo_valor_revenda" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Motivo da Correção</label>
                        <textarea name="observacoes" rows="3" placeholder="Ex: Reajuste de preço, promoção..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">💸 Atualizar Preços</button>
                </form>
            </div>
        </div>

        <!-- ABA: Excluir Produto -->
        <div id="conteudo-excluir" class="conteudo-aba">
            <div class="card">
                <h2>🗑️ Exclusão de Produto</h2>
                <div class="alerta" style="background: #f8d7da; border-color: #f5c6cb;">
                    <strong>🚨 ATENÇÃO CRÍTICA:</strong><br>
                    • O produto será removido APENAS do estoque atual<br>
                    • Todas as vendas e compras anteriores serão mantidas<br>
                    • O produto não aparecerá mais para novas vendas<br>
                    • Esta ação NÃO PODE ser desfeita<br>
                    • Registro será mantido para auditoria
                </div>
                
                <form method="POST" onsubmit="return confirmarExclusao()">
                    <input type="hidden" name="acao" value="excluir_produto">
                    <input type="hidden" name="produto_id" id="produto_id_excluir">
                    
                    <div class="form-group">
                        <label>Digite CONFIRMAR para prosseguir</label>
                        <input type="text" name="confirmacao" required 
                               placeholder="Digite CONFIRMAR aqui..." 
                               style="text-transform: uppercase; font-weight: bold;">
                    </div>
                    
                    <div class="form-group">
                        <label>Motivo da Exclusão</label>
                        <textarea name="observacoes" rows="3" placeholder="Ex: Produto descontinuado, erro de cadastro..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">🗑️ Excluir do Estoque</button>
                </form>
            </div>
        </div>

        <!-- ABA: Histórico -->
        <div id="conteudo-historico" class="conteudo-aba">
            <div class="card">
                <h2>📋 Histórico de Operações</h2>
                <p>Últimas 15 operações de gerenciamento</p>
                
                <?php if (!empty($historico)): ?>
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Produto</th>
                            <th>Detalhes</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $operacao): 
                            $badge_class = '';
                            $detalhes = '';
                            
                            switch ($operacao['tipo']) {
                                case 'ajuste':
                                    $badge_class = 'badge-ajuste';
                                    $detalhes = "De {$operacao['quantidade_anterior']} para {$operacao['quantidade_nova']} unidades";
                                    break;
                                case 'exclusao':
                                    $badge_class = 'badge-exclusao';
                                    $detalhes = "Estoque final: {$operacao['quantidade_final']} unidades";
                                    break;
                                case 'correcao':
                                    $badge_class = 'badge-correcao';
                                    $detalhes = "Custo: R$ {$operacao['valor_unitario_anterior']} → R$ {$operacao['valor_unitario_novo']} | Revenda: R$ {$operacao['valor_revenda_anterior']} → R$ {$operacao['valor_revenda_novo']}";
                                    break;
                            }
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($operacao['data'])) ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= strtoupper($operacao['tipo']) ?></span></td>
                            <td><?= htmlspecialchars($operacao['produto_nome']) ?></td>
                            <td><?= $detalhes ?></td>
                            <td><?= htmlspecialchars($operacao['observacoes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>Nenhuma operação registrada</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sincronizar seleção de produto entre abas
        const produtoSelect = document.getElementById('produto_id');
        produtoSelect.addEventListener('change', function() {
            const produtoId = this.value;
            document.getElementById('produto_id_ajustar').value = produtoId;
            document.getElementById('produto_id_precos').value = produtoId;
            document.getElementById('produto_id_excluir').value = produtoId;
            carregarProduto(produtoId);
        });
    </script>
</body>
</html>