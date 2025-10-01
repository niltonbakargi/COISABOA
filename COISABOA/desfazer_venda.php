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
        SELECT v.*, e.quantidade as estoque_atual 
        FROM vendas v 
        LEFT JOIN estoque e ON v.produto = e.produto 
        ORDER BY v.data_venda DESC 
        LIMIT 50
    ");
    $vendas = $stmt->fetchAll();
    
    // Processar desfazer venda
    if ($_POST['action'] ?? '' === 'desfazer_venda') {
        $venda_id = $_POST['venda_id'];
        
        // Buscar dados da venda
        $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
        $stmt->execute([$venda_id]);
        $venda = $stmt->fetch();
        
        if (!$venda) {
            throw new Exception("Venda não encontrada!");
        }
        
        // Verificar se produto existe no estoque
        $stmt = $pdo->prepare("SELECT * FROM estoque WHERE produto = ?");
        $stmt->execute([$venda['produto']]);
        $produto = $stmt->fetch();
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        if ($produto) {
            // Atualizar estoque (adicionar quantidade) - CORREÇÃO AQUI
            $nova_quantidade = $produto['quantidade'] + $venda['quantidade'];
            $stmt = $pdo->prepare("UPDATE estoque SET quantidade = ? WHERE produto = ?");
            $stmt->execute([$nova_quantidade, $venda['produto']]);
        } else {
            // Recriar produto no estoque - CORREÇÃO AQUI
            $stmt = $pdo->prepare("INSERT INTO estoque (produto, quantidade) VALUES (?, ?)");
            $stmt->execute([$venda['produto'], $venda['quantidade']]);
        }
        
        // Excluir venda
        $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ?");
        $stmt->execute([$venda_id]);
        
        $pdo->commit();
        $mensagem = "✅ Venda desfeita com sucesso! {$venda['quantidade']} unidades de '{$venda['produto']}' retornadas ao estoque.";
        
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensagem = "❌ Erro: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desfazer Venda - COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .tabela { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        .tabela th, .tabela td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #34495e; color: white; }
        .tabela tr:hover { background: #f5f5f5; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; background: white; border-radius: 8px; }
        
        .info-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        
        .form-desfazer { display: inline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Desfazer Venda</h1>
            <div>
                <a href="gerenciar.php" class="btn">← Voltar</a>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">Dashboard</a>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <h3>📋 Como funciona:</h3>
            <p>Ao desfazer uma venda, o sistema:</p>
            <ul>
                <li>➡️ Remove o registro da venda da tabela <strong>vendas</strong></li>
                <li>➡️ Adiciona a quantidade vendida de volta ao <strong>estoque</strong></li>
                <li>➡️ Se o produto não existia mais no estoque, ele é recriado</li>
            </ul>
        </div>

        <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <h2>💰 Últimas Vendas Registradas</h2>
            <p style="color: #7f8c8d; margin-bottom: 20px;">Selecione uma venda para desfazer:</p>
            
            <?php if (!empty($vendas)): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor</th>
                        <th>Estoque Atual</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                        <td><strong><?= $venda['produto'] ?></strong></td>
                        <td><?= $venda['quantidade'] ?> unidades</td>
                        <td>R$ <?= number_format($venda['valor_vendido'], 2, ',', '.') ?></td>
                        <td>
                            <?php if ($venda['estoque_atual'] !== null): ?>
                                <?= $venda['estoque_atual'] ?> unidades
                            <?php else: ?>
                                <span style="color: #e74c3c;">Produto não está em estoque</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="form-desfazer" onsubmit="return confirm('Tem certeza que deseja desfazer a venda de <?= $venda['quantidade'] ?> unidades de \"<?= $venda['produto'] ?>\"?')">
                                <input type="hidden" name="action" value="desfazer_venda">
                                <input type="hidden" name="venda_id" value="<?= $venda['id'] ?>">
                                <button type="submit" class="btn btn-danger">Desfazer Venda</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhuma venda encontrada</h3>
                <p>Não há vendas registradas no sistema.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>