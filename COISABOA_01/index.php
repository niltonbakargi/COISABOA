<?php
session_start();
require_once __DIR__ . "/config.php";

function getDB() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $senha = trim($_POST["senha"]);

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user["senha"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["nome"];
            $_SESSION["user_nivel"] = $user["nivel"];
            header("Location: painel.php");
            exit;
        } else {
            $error = "Usuário ou senha inválidos!";
        }
    } catch (Exception $e) {
        $error = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Login - COISABOA</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; }
.container { max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h2 { text-align: center; margin-bottom: 20px; }
input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
button:hover { background: #5a6fd8; }
.error { background: #fee; padding: 10px; border: 1px solid #f99; color: #900; border-radius: 5px; margin-bottom: 15px; }
</style>
</head>
<body>
<div class="container">
    <h2>📱 COISABOA</h2>
    <?php if ($error): ?>
        <div class="error">❌ <?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <button type="submit">Entrar</button>
    </form>
    <p style="text-align:center; margin-top:15px; font-size: 14px; color: #555;">
        Login padrão: <br> <b>admin@coisaboa.com</b> / <b>admin123</b>
    </p>
</div>
</body>
</html>
