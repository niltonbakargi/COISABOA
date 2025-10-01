<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Painel - COISABOA</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; }
.container { max-width: 800px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h2 { margin-bottom: 20px; }
a { display:inline-block; margin-top:20px; color:#667eea; text-decoration:none; }
a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
    <h2>👋 Bem-vindo, <?= htmlspecialchars($_SESSION["user_name"]) ?>!</h2>
    <p>Você está logado como <b><?= $_SESSION["user_nivel"] ?></b>.</p>

    <h3>📊 Módulos disponíveis</h3>
    <ul>
        <li><a href="compras.php">Gerenciar Compras</a></li>
        <li><a href="vendas.php">Gerenciar Vendas</a></li>
        <li><a href="relatorios.php">Relatórios</a></li>
        <li><a href="configuracoes.php">Configurações</a></li>

    </ul>

    <a href="sair.php">🚪 Sair</a>
</div>
</body>
</html>
