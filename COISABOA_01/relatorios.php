<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/config/database.php";
$pdo = getDBConnection();

// Variáveis de filtro
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim'] ?? '';

$where_compras = '';
$where_vendas  = '';
$params = [];

// Se tiver período definido, ajusta os filtros
if (!empty($data_inicio) && !empty($data_fim)) {
    $where_compras = "WHERE DATE(data_compra) BETWEEN ? AND ?";
    $where_vendas  = "WHERE DATE(data_venda) BETWEEN ? AND ?";
    $params = [$data_inicio, $data_fim];
}

// Totais gerais ou filtrados
$stmt = $pdo->prepare("SELECT COUNT(*) as qtd, SUM(valor_total) as total FROM compras $where_compras");
$stmt->execute($params);
$compras = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as qtd, SUM(valor_total) as total FROM vendas $where_vendas");
$stmt->execute($params);
$vendas = $stmt->fetch();

// Lucro ou prejuízo
$lucro = ($vendas["total"] ?? 0) - ($compras["total"] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatórios - COISABOA</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8fafc; }
    .card { border: 1px solid #ccc; border-radius: 8px; padding: 20px; margin-bottom: 15px; background: white; }
    h2 { color: #333; }
    .positivo { color: green; font-weight: bold; }
    .negativo { color: red; font-weight: bold; }
    form { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    input { padding: 5px; margin: 5px; }
    button { background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
    button:hover { background: #556cd6; }
</style>
</head>
<body>
<h2>📊 Relatórios</h2>

<!-- Filtro de período -->
<form method="GET">
    <label>Data Início:</label>
    <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
    <label>Data Fim:</label>
    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
    <button type="submit">Filtrar</button>
    <a href="relatorios.php" style="margin-left:10px;">Limpar</a>
</form>

<div class="card">
    <h3>🛒 Compras</h3>
    <p>Total de Compras: <b><?= $compras["qtd"] ?></b></p>
    <p>Valor Total: <b>R$ <?= number_format($compras["total"] ?? 0, 2, ',', '.') ?></b></p>
</div>

<div class="card">
    <h3>💰 Vendas</h3>
    <p>Total de Vendas: <b><?= $vendas["qtd"] ?></b></p>
    <p>Valor Total: <b>R$ <?= number_format($vendas["total"] ?? 0, 2, ',', '.') ?></b></p>
</div>

<div class="card">
    <h3>📈 Resultado</h3>
    <?php if ($lucro >= 0): ?>
        <p>Lucro: <span class="positivo">R$ <?= number_format($lucro, 2, ',', '.') ?></span></p>
    <?php else: ?>
        <p>Prejuízo: <span class="negativo">R$ <?= number_format($lucro, 2, ',', '.') ?></span></p>
    <?php endif; ?>
</div>

<p><a href="painel.php">⬅️ Voltar</a></p>
</body>
</html>
