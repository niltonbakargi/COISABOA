<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/config.php";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Salvar alterações
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $configs = [
        "empresa_nome" => $_POST["empresa_nome"] ?? "",
        "empresa_cnpj" => $_POST["empresa_cnpj"] ?? "",
        "estoque_minimo" => $_POST["estoque_minimo"] ?? "5",
        "notificar_estoque_baixo" => isset($_POST["notificar"]) ? "true" : "false",
        "formas_pagamento" => json_encode($_POST["formas_pagamento"] ?? ["dinheiro"])
    ];

    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, tipo) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo)");

    foreach ($configs as $chave => $valor) {
        $tipo = is_numeric($valor) ? "number" : (in_array($valor, ["true", "false"]) ? "boolean" : (json_decode($valor, true) ? "json" : "string"));
        $stmt->execute([$chave, $valor, $tipo]);
    }

    $mensagem = "✅ Configurações salvas com sucesso!";
}

// Buscar configurações atuais
$config = [];
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
foreach ($stmt as $row) {
    $config[$row["chave"]] = $row["valor"];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Configurações - COISABOA</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { margin-bottom: 20px; }
    form { max-width: 500px; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    input, select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
    button { margin-top: 20px; padding: 10px 20px; border: none; background: #667eea; color: white; border-radius: 5px; cursor: pointer; }
    button:hover { background: #5a6fd8; }
    .msg { margin-top: 15px; padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 5px; color: #16a34a; }
</style>
</head>
<body>
<h2>⚙️ Configurações do Sistema</h2>

<?php if (!empty($mensagem)): ?>
    <div class="msg"><?= $mensagem ?></div>
<?php endif; ?>

<form method="POST">
    <label>Nome da Empresa</label>
    <input type="text" name="empresa_nome" value="<?= htmlspecialchars($config["empresa_nome"] ?? "") ?>">

    <label>CNPJ</label>
    <input type="text" name="empresa_cnpj" value="<?= htmlspecialchars($config["empresa_cnpj"] ?? "") ?>">

    <label>Estoque Mínimo</label>
    <input type="number" name="estoque_minimo" value="<?= htmlspecialchars($config["estoque_minimo"] ?? "5") ?>">

    <label><input type="checkbox" name="notificar" <?= ($config["notificar_estoque_baixo"] ?? "false") === "true" ? "checked" : "" ?>> Notificar estoque baixo</label>

    <label>Formas de Pagamento</label>
    <?php
    $formas = ["dinheiro", "cartao_credito", "cartao_debito", "pix", "transferencia", "boleto"];
    $selecionadas = json_decode($config["formas_pagamento"] ?? "[]", true);
    foreach ($formas as $forma): ?>
        <label><input type="checkbox" name="formas_pagamento[]" value="<?= $forma ?>" <?= in_array($forma, $selecionadas) ? "checked" : "" ?>> <?= ucfirst(str_replace("_", " ", $forma)) ?></label>
    <?php endforeach; ?>

    <button type="submit">💾 Salvar Configurações</button>
</form>

<p style="margin-top:20px;"><a href="painel.php">⬅️ Voltar</a></p>
</body>
</html>
