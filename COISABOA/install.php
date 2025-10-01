<?php
/**
 * COISABOA - Instalador Simplificado
 * Versão pessoal e específica
 */

// Configurações fixas para uso pessoal
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'coisaboa_app');
define('DB_PORT', '3306');

// ==================== FUNÇÕES SIMPLIFICADAS ====================

function criarPastas() {
    $pastas = ['uploads', 'uploads/vendors', 'uploads/temp', 'config', 'logs'];
    
    foreach ($pastas as $pasta) {
        if (!file_exists($pasta)) {
            mkdir($pasta, 0755, true);
        }
    }
    return true;
}

function criarBancoDados() {
    try {
        // Conectar sem selecionar banco primeiro
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Criar banco se não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Agora conectar ao banco específico
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // ==================== TABELAS SIMPLIFICADAS ====================
        
        // Tabela de compras
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS compras (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_pago DECIMAL(10,2) NOT NULL,
                data_compra DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de vendas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendas (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_vendido DECIMAL(10,2) NOT NULL,
                data_venda DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de usuários (apenas 1 usuário)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Inserir usuário padrão
        $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT IGNORE INTO usuarios (nome, email, senha) 
            VALUES ('Usuario', 'admin@coisaboa.com', '$senha_hash')
        ");
        
        return ['success' => true, 'message' => 'Banco e tabelas criados com sucesso!'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

function criarArquivoConfig() {
    $config = "<?php
/**
 * COISABOA - Configuração Simplificada
 */
define('DB_HOST', '" . DB_HOST . "');
define('DB_NAME', '" . DB_NAME . "');  
define('DB_USER', '" . DB_USER . "');
define('DB_PASS', '" . DB_PASS . "');
define('DB_PORT', '" . DB_PORT . "');

function getDB() {
    try {
        \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=utf8mb4';
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return \$pdo;
    } catch (PDOException \$e) {
        die('Erro de conexão: ' . \$e->getMessage());
    }
}
?>";
    
    return file_put_contents('config/database.php', $config);
}

function finalizarInstalacao() {
    $info = [
        'instalado_em' => date('Y-m-d H:i:s'),
        'versao' => '1.0',
        'uso' => 'pessoal'
    ];
    file_put_contents('config/instalado.json', json_encode($info));
    return true;
}

function jaInstalado() {
    return file_exists('config/database.php') && file_exists('config/instalado.json');
}

// ==================== PROCESSAMENTO ====================

session_start();
$step = $_GET['step'] ?? 'inicio';
$mensagem = '';

if (jaInstalado() && $step !== 'reinstalar') {
    $step = 'pronto';
}

// Processar ações
if ($_POST['action'] ?? '' === 'instalar') {
    criarPastas();
    $resultado = criarBancoDados();
    
    if ($resultado['success']) {
        criarArquivoConfig();
        finalizarInstalacao();
        $mensagem = "✅ " . $resultado['message'];
        $step = 'pronto';
    } else {
        $mensagem = "❌ " . $resultado['message'];
    }
}

if ($_GET['action'] ?? '' === 'reinstalar') {
    @unlink('config/database.php');
    @unlink('config/instalado.json');
    $mensagem = "🔄 Reiniciando instalação...";
    $step = 'inicio';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f0f2f5;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            background: #e8f5e8;
            border: 1px solid #4caf50;
        }
        .status.erro {
            background: #ffebee;
            border-color: #f44336;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn.verde {
            background: #27ae60;
        }
        .btn.verde:hover {
            background: #219a52;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: left;
        }
        .credenciais {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 COISABOA</h1>
        <p>Sistema de Gestão Pessoal</p>
        
        <?php if ($mensagem): ?>
            <div class="status <?= strpos($mensagem, '❌') !== false ? 'erro' : '' ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 'inicio'): ?>
            <div class="info">
                <h3>📋 Configuração Automática</h3>
                <p><strong>Banco:</strong> <?= DB_HOST ?>:<?= DB_PORT ?></p>
                <p><strong>Usuário:</strong> <?= DB_USER ?></p>
                <p><strong>Banco:</strong> <?= DB_NAME ?></p>
                <p><em>Configuração para uso pessoal/local</em></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="instalar">
                <button type="submit" class="btn verde">✨ Instalar Agora</button>
            </form>
            
        <?php elseif ($step === 'pronto'): ?>
            <div class="status">
                ✅ Sistema instalado com sucesso!
            </div>
            
            <div class="credenciais">
                <h3>🔑 Acesso do Sistema</h3>
                <p><strong>Email:</strong> admin@coisaboa.com</p>
                <p><strong>Senha:</strong> 123456</p>
                
            </div>
            
                        <div style="margin: 20px 0;">
                <?php
                // Verificar qual arquivo de login existe
                $login_file = 'login.php';
                if (!file_exists($login_file)) {
                    $login_file = 'index.php'; // Fallback para index
                }
                ?>
                <a href="<?= $login_file ?>" class="btn verde">🎯 Acessar Sistema</a>
                <a href="?action=reinstalar" class="btn" 
                   onclick="return confirm('Reinstalar? Isso apagará tudo.')">
                   🔄 Reinstalar
                </a>
            </div>
            
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <small style="color: #666;">
                💡 <strong>Dica:</strong> Use XAMPP/WAMP com MySQL ativo. 
                Senha do MySQL vazia é o padrão.
            </small>
        </div>
    </div>
</body>
</html>