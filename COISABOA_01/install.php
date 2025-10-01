<?php
/**
 * COISABOA - Instalador do Sistema
 * @version 1.0.0
 */

define('MIN_PHP_VERSION', '7.4.0');
define('REQUIRED_EXTENSIONS', ['pdo_mysql', 'mbstring', 'fileinfo', 'gd']);

function checkRequirements() {
    $errors = [];
    $warnings = [];

    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
        $errors[] = "PHP " . MIN_PHP_VERSION . " ou superior é necessário (atual: " . PHP_VERSION . ")";
    }

    foreach (REQUIRED_EXTENSIONS as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extensão PHP '$ext' não encontrada";
        }
    }

    if (!is_writable(__DIR__)) {
        $warnings[] = "A pasta raiz não tem permissão de escrita";
    }

    return ['errors' => $errors, 'warnings' => $warnings];
}

function installDatabase($host, $user, $pass, $dbname, $port = "3306") {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` 
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $pdo->exec("USE `$dbname`");

        // Compras
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS compras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_pago DECIMAL(10,2) NOT NULL,
                valor_total DECIMAL(10,2) NOT NULL,
                valor_proposto DECIMAL(10,2) NOT NULL,
                observacoes TEXT NULL,
                data_compra DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Vendas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_vendido DECIMAL(10,2) NOT NULL,
                valor_total DECIMAL(10,2) NOT NULL,
                forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito') DEFAULT 'dinheiro',
                data_venda DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Usuários
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                nivel ENUM('admin','usuario') DEFAULT 'usuario',
                ativo BOOLEAN DEFAULT TRUE,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Admin padrão (senha: admin123)
        $pdo->exec("
            INSERT IGNORE INTO usuarios (nome, email, senha, nivel, ativo)
            VALUES ('Administrador', 'admin@coisaboa.com',
            '" . password_hash("admin123", PASSWORD_BCRYPT) . "', 'admin', 1);
        ");

        return ['success' => true, 'message' => "Banco e tabelas criados com sucesso!"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============== PROCESSO ==================
$step = $_POST['step'] ?? 'welcome';
$errors = [];
$success = [];
$warnings = [];

if ($step === 'welcome') {
    $req = checkRequirements();
    $errors = $req['errors'];
    $warnings = $req['warnings'];
} elseif ($step === 'install') {
    $host = $_POST['host'] ?? 'localhost';
    $user = $_POST['user'] ?? 'root';
    $pass = $_POST['pass'] ?? '';
    $dbname = $_POST['dbname'] ?? 'coisaboa';
    $port = $_POST['port'] ?? '3306';

    $result = installDatabase($host, $user, $pass, $dbname, $port);
    if ($result['success']) {
        $success[] = $result['message'];
        file_put_contents(__DIR__ . "/config.php", "<?php
define('DB_HOST', '$host');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_NAME', '$dbname');
define('DB_PORT', '$port');
?>");
        $success[] = "Arquivo config.php criado!";
        $step = 'complete';
    } else {
        $errors[] = $result['message'];
        $step = 'welcome';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Instalador COISABOA</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f4; padding:40px; }
.container { background:#fff; padding:30px; border-radius:8px; max-width:600px; margin:auto; }
h1 { margin-bottom:20px; }
.alert { padding:10px; margin-bottom:10px; border-radius:4px; }
.error { background:#fee; border:1px solid #f99; }
.success { background:#efe; border:1px solid #9f9; }
.warning { background:#ffe; border:1px solid #dd0; }
</style>
</head>
<body>
<div class="container">
    <h1>📱 Instalador COISABOA</h1>

    <?php foreach ($errors as $e): ?>
        <div class="alert error">❌ <?= $e ?></div>
    <?php endforeach; ?>

    <?php foreach ($success as $s): ?>
        <div class="alert success">✅ <?= $s ?></div>
    <?php endforeach; ?>

    <?php foreach ($warnings as $w): ?>
        <div class="alert warning">⚠️ <?= $w ?></div>
    <?php endforeach; ?>

    <?php if ($step === 'welcome'): ?>
        <form method="post">
            <input type="hidden" name="step" value="install">
            <label>Servidor:</label><br>
            <input type="text" name="host" value="localhost"><br><br>
            <label>Porta:</label><br>
            <input type="text" name="port" value="3306"><br><br>
            <label>Banco:</label><br>
            <input type="text" name="dbname" value="coisaboa"><br><br>
            <label>Usuário:</label><br>
            <input type="text" name="user" value="root"><br><br>
            <label>Senha:</label><br>
            <input type="password" name="pass"><br><br>
            <button type="submit">🚀 Instalar</button>
        </form>
    <?php elseif ($step === 'complete'): ?>
        <h2>🎉 Instalação concluída!</h2>
        <p>Banco de dados criado e usuário admin adicionado.</p>
        <p>Login: <b>admin@coisaboa.com</b><br>Senha: <b>admin123</b></p>
        <p><a href="index.php">➡️ Acessar o Sistema</a></p>
    <?php endif; ?>
</div>
</body>
</html>
