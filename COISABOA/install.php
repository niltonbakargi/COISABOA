<?php
/**
 * COISABOA - Instalador Simplificado
 * Versão otimizada para mobile
 */

// Configurações fixas para uso pessoal
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'coisaboa_app');
define('DB_PORT', '3306');

// ==================== FUNÇÕES SIMPLIFICADAS ====================

function criarPastas() {
    $pastas = [
        'uploads', 
        'uploads/produtos',
        'uploads/vendedores', 
        'uploads/compradores',
        'uploads/temp', 
        'config', 
        'logs'
    ];
    
    foreach ($pastas as $pasta) {
        if (!file_exists($pasta)) {
            mkdir($pasta, 0755, true);
            // Criar arquivo index.html para segurança
            file_put_contents($pasta . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access forbidden</h1></body></html>');
        }
    }
    return true;
}

function criarBancoDados() {
    try {
        // Conectar sem selecionar banco primeiro
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);
        
        // Criar banco se não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Agora conectar ao banco específico
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);
        
        // ==================== TABELAS COMPATÍVEIS COM BD ATUAL ====================
        
        // Tabela de usuários (apenas 1 usuário)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de compras (compatível com estrutura atual)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS compras (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                data_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
                valor_unitario DECIMAL(10,2) DEFAULT 0.00,
                valor_revenda DECIMAL(10,2) DEFAULT 0.00,
                forma_pagamento VARCHAR(50) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL,
                imagem_produto VARCHAR(255) DEFAULT NULL,
                imagem_vendedor VARCHAR(255) DEFAULT NULL,
                valor_total DECIMAL(10,2) DEFAULT 0.00
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de vendas (compatível com estrutura atual)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendas (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_vendido DECIMAL(10,2) NOT NULL,
                valor_estimado DECIMAL(10,2) DEFAULT NULL,
                forma_pagamento VARCHAR(50) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL,
                imagem_comprador VARCHAR(255) DEFAULT NULL,
                data_venda DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de estoque (compatível com estrutura atual)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS estoque (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL DEFAULT 0,
                valor_unitario DECIMAL(10,2) DEFAULT 0.00,
                valor_revenda DECIMAL(10,2) DEFAULT 0.00,
                imagem_produto VARCHAR(255) DEFAULT NULL,
                compra_origem_id INT DEFAULT NULL,
                data_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_produto (produto)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Inserir usuário padrão se não existir
        $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO usuarios (nome, email, senha) 
            VALUES ('Usuario Admin', 'admin@coisaboa.com', ?)
        ");
        $stmt->execute([$senha_hash]);
        
        return ['success' => true, 'message' => 'Banco e tabelas criados/atualizados com sucesso!'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()];
    }
}

function criarArquivoConfig() {
    $config = "<?php
/**
 * COISABOA - Configuração Simplificada
 * Gerado automaticamente em " . date('d/m/Y H:i:s') . "
 */
 
// Configurações do Banco de Dados
define('DB_HOST', '" . DB_HOST . "');
define('DB_NAME', '" . DB_NAME . "');  
define('DB_USER', '" . DB_USER . "');
define('DB_PASS', '" . DB_PASS . "');
define('DB_PORT', '" . DB_PORT . "');

// Configurações do Sistema
define('SITE_NAME', 'COISABOA App');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Função de conexão com o banco
function getDB() {
    try {
        \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=utf8mb4';
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,
            PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
        ]);
        return \$pdo;
    } catch (PDOException \$e) {
        error_log('Erro de conexão: ' . \$e->getMessage());
        http_response_code(500);
        die('Erro de conexão com o banco de dados. Tente novamente.');
    }
}

// Função de redirecionamento mobile-friendly
function redirect(\$url) {
    if (!headers_sent()) {
        header('Location: ' . \$url);
        exit;
    } else {
        echo '<script>window.location.href=\"' . \$url . '\";</script>';
        exit;
    }
}

// Função de sanitização
function sanitize(\$data) {
    if (is_array(\$data)) {
        return array_map('sanitize', \$data);
    }
    return htmlspecialchars(trim(\$data), ENT_QUOTES, 'UTF-8');
}
?>";
    
    return file_put_contents('config/database.php', $config) !== false;
}

function finalizarInstalacao() {
    $info = [
        'instalado_em' => date('Y-m-d H:i:s'),
        'versao' => '2.0',
        'uso' => 'pessoal',
        'mobile_optimized' => true,
        'db_compativel' => true
    ];
    return file_put_contents('config/instalado.json', json_encode($info, JSON_PRETTY_PRINT)) !== false;
}

function jaInstalado() {
    return file_exists('config/database.php') && file_exists('config/instalado.json');
}

function testarConexao() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_TIMEOUT => 10
        ]);
        return ['success' => true, 'message' => 'Conexão MySQL OK'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Sem conexão MySQL: ' . $e->getMessage()];
    }
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
    $testeConexao = testarConexao();
    if (!$testeConexao['success']) {
        $mensagem = "❌ " . $testeConexao['message'];
    } else {
        criarPastas();
        $resultado = criarBancoDados();
        
        if ($resultado['success']) {
            if (criarArquivoConfig() && finalizarInstalacao()) {
                $mensagem = "✅ " . $resultado['message'];
                $step = 'pronto';
            } else {
                $mensagem = "❌ Erro ao criar arquivos de configuração";
            }
        } else {
            $mensagem = "❌ " . $resultado['message'];
        }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#3498db">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Instalar COISABOA</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            text-align: center;
            margin: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 12px;
            background: #e8f5e8;
            border: 2px solid #4caf50;
            font-size: 14px;
            text-align: left;
        }
        
        .status.erro {
            background: #ffebee;
            border-color: #f44336;
        }
        
        .status.aviso {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            width: 90%;
            max-width: 280px;
        }
        
        .btn:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.4);
        }
        
        .btn.verde {
            background: linear-gradient(135deg, #27ae60, #219a52);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn.vermelho {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
            border-left: 4px solid #2196f3;
        }
        
        .credenciais {
            background: #fff3cd;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        
        .info h3, .credenciais h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 18px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .info-value {
            color: #7f8c8d;
        }
        
        .dica {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            color: #666;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
                margin: 10px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .btn {
                padding: 14px 28px;
                font-size: 15px;
            }
        }
        
        .loading {
            display: none;
            margin: 10px 0;
        }
        
        .loading.spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🚀</div>
        <h1>COISABOA</h1>
        <p class="subtitle">Sistema de Gestão Pessoal</p>
        
        <?php if ($mensagem): ?>
            <div class="status <?= strpos($mensagem, '❌') !== false ? 'erro' : (strpos($mensagem, '🔄') !== false ? 'aviso' : '') ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 'inicio'): ?>
            <div class="info">
                <h3>📋 Configuração Automática</h3>
                <div class="info-item">
                    <span class="info-label">Servidor:</span>
                    <span class="info-value"><?= DB_HOST ?>:<?= DB_PORT ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Usuário:</span>
                    <span class="info-value"><?= DB_USER ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Banco:</span>
                    <span class="info-value"><?= DB_NAME ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipo:</span>
                    <span class="info-value">Uso pessoal</span>
                </div>
            </div>
            
            <div class="dica">
                💡 <strong>Pronto para uso mobile:</strong> Interface otimizada para celular e tablets
            </div>
            
            <form method="POST" id="installForm">
                <input type="hidden" name="action" value="instalar">
                <button type="submit" class="btn verde" id="installBtn">
                    ✨ Instalar Agora
                </button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Instalando, aguarde...</p>
            </div>
            
        <?php elseif ($step === 'pronto'): ?>
            <div class="status">
                ✅ Sistema instalado com sucesso!
            </div>
            
            <div class="credenciais">
                <h3>🔑 Acesso do Sistema</h3>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value">admin@coisaboa.com</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Senha:</span>
                    <span class="info-value">123456</span>
                </div>
            </div>
            
            <div style="margin: 25px 0;">
                <?php
                // Verificar qual arquivo de login existe
                $login_file = 'login.php';
                if (!file_exists($login_file)) {
                    $login_file = 'index.php';
                }
                ?>
                <a href="<?= $login_file ?>" class="btn verde">🎯 Acessar Sistema</a>
                <a href="?action=reinstalar" class="btn vermelho" 
                   onclick="return confirm('Tem certeza? Isso reiniciará toda a instalação.')">
                   🔄 Reinstalar
                </a>
            </div>
            
        <?php endif; ?>
        
        <div class="dica">
            <?php if ($step === 'pronto'): ?>
                📱 <strong>Otimizado para mobile:</strong> Use no celular como um app!
            <?php else: ?>
                💡 <strong>Dica:</strong> Use XAMPP/WAMP com MySQL ativo
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            const installForm = document.getElementById('installForm');
            const installBtn = document.getElementById('installBtn');
            const loading = document.getElementById('loading');
            
            if (installForm) {
                installForm.addEventListener('submit', function() {
                    installBtn.style.display = 'none';
                    loading.style.display = 'block';
                });
            }
            
            // Prevenir zoom duplo em botões
            document.addEventListener('touchstart', function() {}, {passive: true});
            
            // Feedback tátil melhorado
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                });
                
                btn.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            });
        });
    </script>
</body>
</html>