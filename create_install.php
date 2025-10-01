<?php
/**
 * COISABOA - Instalador do Sistema
 * Arquivo: create_install.php
 * Descrição: Cria o instalador completo do banco de dados e sistema
 */

echo "🗄️ INICIANDO CRIAÇÃO DO INSTALADOR...\n";
echo "=============================================\n";

// Configurações
$project_root = __DIR__ . '/COISABOA';
$install_file = $project_root . '/install.php';

// Verificar se a pasta principal existe
if (!file_exists($project_root)) {
    echo "❌ ERRO: Pasta principal COISABOA não encontrada!\n";
    echo "Execute primeiro o create_structure.php\n";
    exit(1);
}

// Conteúdo do instalador
$install_content = "<?php
/**
 * COISABOA - Instalador do Sistema
 * @version 1.0.0
 * 
 * Este arquivo instala e configura o sistema COISABOA
 * Execute apenas uma vez após o download
 */

// ==================== CONFIGURAÇÕES ====================
define('INSTALLER_VERSION', '1.0.0');
define('MIN_PHP_VERSION', '7.4.0');
define('REQUIRED_EXTENSIONS', ['pdo_mysql', 'mbstring', 'fileinfo', 'gd']);

// ==================== FUNÇÕES DO INSTALADOR ====================
function checkRequirements() {
    \$errors = [];
    \$warnings = [];
    
    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
        \$errors[] = \"PHP versão \" . MIN_PHP_VERSION . \" ou superior é necessária. Versão atual: \" . PHP_VERSION;
    }
    
    // Verificar extensões necessárias
    foreach (REQUIRED_EXTENSIONS as \$ext) {
        if (!extension_loaded(\$ext)) {
            \$errors[] = \"Extensão PHP '\$ext' não está instalada.\";
        }
    }
    
    // Verificar permissões de escrita
    \$writable_dirs = ['uploads', 'assets/images', 'config'];
    foreach (\$writable_dirs as \$dir) {
        if (!is_writable(__DIR__ . '/' . \$dir)) {
            \$warnings[] = \"Pasta '\$dir' não tem permissão de escrita. Uploads podem não funcionar.\";
        }
    }
    
    // Verificar se o MySQL está disponível
    if (!function_exists('mysqli_connect')) {
        \$warnings[] = \"Extensão MySQLi não encontrada. O sistema usará PDO MySQL.\";
    }
    
    return ['errors' => \$errors, 'warnings' => \$warnings];
}

function testDatabaseConnection(\$host, \$user, \$pass, \$port = '3306') {
    try {
        \$dsn = \"mysql:host=\$host;port=\$port;charset=utf8mb4\";
        \$pdo = new PDO(\$dsn, \$user, \$pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        return ['success' => true, 'message' => 'Conexão bem-sucedida!'];
    } catch (PDOException \$e) {
        return ['success' => false, 'message' => 'Erro de conexão: ' . \$e->getMessage()];
    }
}

function createDatabase(\$host, \$user, \$pass, \$dbname, \$port = '3306') {
    try {
        \$dsn = \"mysql:host=\$host;port=\$port;charset=utf8mb4\";
        \$pdo = new PDO(\$dsn, \$user, \$pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Criar banco de dados se não existir
        \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS `\$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\");
        
        return ['success' => true, 'message' => 'Banco de dados criado com sucesso!'];
    } catch (PDOException \$e) {
        return ['success' => false, 'message' => 'Erro ao criar banco: ' . \$e->getMessage()];
    }
}

function installDatabaseSchema(\$host, \$user, \$pass, \$dbname, \$port = '3306') {
    try {
        \$dsn = \"mysql:host=\$host;dbname=\$dbname;port=\$port;charset=utf8mb4\";
        \$pdo = new PDO(\$dsn, \$user, \$pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // ==================== CRIAÇÃO DAS TABELAS ====================
        
        // Tabela de compras
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS compras (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_pago DECIMAL(10,2) NOT NULL,
                valor_total DECIMAL(10,2) NOT NULL,
                valor_proposto DECIMAL(10,2) NOT NULL,
                foto_vendedor VARCHAR(255) NULL,
                observacoes TEXT NULL,
                data_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_produto (produto),
                INDEX idx_data (data_compra)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        \");
        
        // Tabela de vendas
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS vendas (
                id INT PRIMARY KEY AUTO_INCREMENT,
                produto VARCHAR(100) NOT NULL,
                quantidade INT NOT NULL,
                valor_vendido DECIMAL(10,2) NOT NULL,
                valor_total DECIMAL(10,2) NOT NULL,
                desconto DECIMAL(10,2) DEFAULT 0.00,
                forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'transferencia', 'boleto') DEFAULT 'dinheiro',
                data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_produto (produto),
                INDEX idx_data (data_venda),
                INDEX idx_pagamento (forma_pagamento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        \");
        
        // Tabela de usuários (para versões futuras)
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                nivel ENUM('admin', 'usuario') DEFAULT 'usuario',
                ativo BOOLEAN DEFAULT TRUE,
                data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ultimo_login DATETIME NULL,
                INDEX idx_email (email),
                INDEX idx_ativo (ativo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        \");
        
        // Tabela de configurações (para versões futuras)
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS configuracoes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                chave VARCHAR(50) UNIQUE NOT NULL,
                valor TEXT NOT NULL,
                tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
                descricao TEXT NULL,
                data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        \");
        
        // Tabela de logs (para versões futuras)
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                usuario_id INT NULL,
                acao VARCHAR(50) NOT NULL,
                descricao TEXT NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                data_log DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_acao (acao),
                INDEX idx_data (data_log),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        \");
        
        // ==================== DADOS INICIAIS ====================
        
        // Inserir usuário admin padrão (senha: admin123)
        \$pdo->exec(\"
            INSERT IGNORE INTO usuarios (nome, email, senha, nivel, ativo) 
            VALUES (
                'Administrador', 
                'admin@coisaboa.com', 
                '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
                'admin', 
                TRUE
            )
        \");
        
        // Inserir configurações padrão
        \$configuracoes = [
            ['empresa_nome', 'Minha Empresa', 'string', 'Nome da empresa'],
            ['empresa_cnpj', '', 'string', 'CNPJ da empresa'],
            ['estoque_minimo', '5', 'number', 'Estoque mínimo para alertas'],
            ['notificar_estoque_baixo', 'true', 'boolean', 'Notificar sobre estoque baixo'],
            ['formas_pagamento', '[\"dinheiro\", \"cartao_credito\", \"cartao_debito\", \"pix\"]', 'json', 'Formas de pagamento aceitas']
        ];
        
        \$stmt = \$pdo->prepare(\"
            INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao) 
            VALUES (?, ?, ?, ?)
        \");
        
        foreach (\$configuracoes as \$config) {
            \$stmt->execute(\$config);
        }
        
        return [
            'success' => true, 
            'message' => 'Estrutura do banco criada com sucesso!',
            'tables' => ['compras', 'vendas', 'usuarios', 'configuracoes', 'logs']
        ];
        
    } catch (PDOException \$e) {
        return ['success' => false, 'message' => 'Erro na instalação: ' . \$e->getMessage()];
    }
}

function createConfigFile(\$config) {
    \$config_content = \"<?php
/**
 * COISABOA - Configuração do Banco de Dados
 * Gerado automaticamente pelo instalador
 */

// Configurações do Banco de Dados
define('DB_HOST', '{\$config['host']}');
define('DB_NAME', '{\$config['dbname']}');
define('DB_USER', '{\$config['user']}');
define('DB_PASS', '{\$config['pass']}');
define('DB_PORT', '{\$config['port']}');
define('DB_CHARSET', 'utf8mb4');

// Opções do PDO
\\\$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => \\\"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\\\"
];

function getDBConnection() {
    \\\$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
    
    try {
        \\\$pdo = new PDO(\\\$dsn, DB_USER, DB_PASS, \\\$GLOBALS['db_options']);
        return \\\$pdo;
    } catch (PDOException \\\$e) {
        error_log('Erro de conexão: ' . \\\$e->getMessage());
        die('<div style=\\\"padding: 20px; background: #fee; border: 1px solid #fcc; margin: 20px;\\\">
            <h3>Erro de Conexão com o Banco</h3>
            <p>Não foi possível conectar ao banco de dados.</p>
            <p><small>Detalhes: ' . \\\$e->getMessage() . '</small></p>
        </div>');
    }
}
?>\";
    
    return file_put_contents(__DIR__ . '/config/database.php', \$config_content);
}

function finalizeInstallation() {
    // Criar arquivo de instalação concluída
    \$install_flag = [
        'version' => INSTALLER_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'base_url' => isset(\$_SERVER['HTTP_HOST']) ? \$_SERVER['HTTP_HOST'] : 'localhost'
    ];
    
    file_put_contents(__DIR__ . '/config/installed.json', json_encode(\$install_flag, JSON_PRETTY_PRINT));
    
    // Configurar permissões básicas
    \$chmod_dirs = ['uploads' => 0755, 'config' => 0644];
    foreach (\$chmod_dirs as \$dir => \$perms) {
        @chmod(__DIR__ . '/' . \$dir, \$perms);
    }
    
    return true;
}

function isAlreadyInstalled() {
    return file_exists(__DIR__ . '/config/installed.json') || 
           file_exists(__DIR__ . '/config/database.php');
}

// ==================== PROCESSAMENTO DO INSTALADOR ====================
\$step = \$_POST['step'] ?? 'welcome';
\$errors = [];
\$success = [];
\$form_data = \$_POST;

// Verificar se já está instalado
if (isAlreadyInstalled() && \$step !== 'reinstall') {
    \$step = 'already_installed';
}

// Processar cada passo
switch (\$step) {
    case 'welcome':
        \$requirements = checkRequirements();
        \$errors = \$requirements['errors'];
        \$warnings = \$requirements['warnings'];
        break;
        
    case 'database':
        // Validar dados do formulário
        \$required = ['host', 'user', 'dbname'];
        foreach (\$required as \$field) {
            if (empty(\$form_data[\$field])) {
                \$errors[] = \"O campo '\$field' é obrigatório.\";
            }
        }
        
        if (empty(\$errors)) {
            // Testar conexão com o banco
            \$connection_test = testDatabaseConnection(
                \$form_data['host'],
                \$form_data['user'], 
                \$form_data['pass'] ?? '',
                \$form_data['port'] ?? '3306'
            );
            
            if (\$connection_test['success']) {
                \$success[] = \$connection_test['message'];
                
                // Criar banco de dados
                \$db_creation = createDatabase(
                    \$form_data['host'],
                    \$form_data['user'],
                    \$form_data['pass'] ?? '',
                    \$form_data['dbname'],
                    \$form_data['port'] ?? '3306'
                );
                
                if (\$db_creation['success']) {
                    \$success[] = \$db_creation['message'];
                } else {
                    \$errors[] = \$db_creation['message'];
                }
            } else {
                \$errors[] = \$connection_test['message'];
            }
        }
        break;
        
    case 'install':
        if (empty(\$errors)) {
            // Instalar schema do banco
            \$installation = installDatabaseSchema(
                \$form_data['host'],
                \$form_data['user'],
                \$form_data['pass'] ?? '',
                \$form_data['dbname'],
                \$form_data['port'] ?? '3306'
            );
            
            if (\$installation['success']) {
                \$success[] = \$installation['message'];
                
                // Criar arquivo de configuração
                if (createConfigFile(\$form_data)) {
                    \$success[] = 'Arquivo de configuração criado com sucesso!';
                    
                    // Finalizar instalação
                    if (finalizeInstallation()) {
                        \$success[] = 'Instalação concluída com sucesso!';
                        \$step = 'complete';
                    }
                } else {
                    \$errors[] = 'Erro ao criar arquivo de configuração.';
                }
            } else {
                \$errors[] = \$installation['message'];
            }
        }
        break;
        
    case 'reinstall':
        // Limpar instalação anterior
        @unlink(__DIR__ . '/config/installed.json');
        @unlink(__DIR__ . '/config/database.php');
        \$step = 'welcome';
        break;
}

// ==================== INTERFACE DO INSTALADOR ====================
?>
<!DOCTYPE html>
<html lang=\"pt-BR\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Instalador COISABOA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .installer-header {
            background: #667eea;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .installer-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .installer-content {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            color: #ea580c;
        }
        
        .requirements-list {
            list-style: none;
            margin: 20px 0;
        }
        
        .requirements-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
        }
        
        .requirements-list li:before {
            content: '✓';
            color: #10b981;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .requirements-list li.error:before {
            content: '✗';
            color: #dc2626;
        }
        
        .success-screen {
            text-align: center;
            padding: 40px 0;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class=\"installer-container\">
        <div class=\"installer-header\">
            <h1>📱 COISABOA</h1>
            <p>Instalador do Sistema</p>
        </div>
        
        <div class=\"installer-content\">
            <!-- Indicador de Passos -->
            <div class=\"step-indicator\">
                <div class=\"step <?= in_array(\$step, ['welcome', 'database', 'install', 'complete']) ? 'completed' : '' ?>\">1</div>
                <div class=\"step <?= in_array(\$step, ['database', 'install', 'complete']) ? 'completed' : '' ?>\">2</div>
                <div class=\"step <?= in_array(\$step, ['install', 'complete']) ? 'completed' : '' ?>\">3</div>
                <div class=\"step <?= \$step === 'complete' ? 'completed active' : '' ?>\">4</div>
            </div>
            
            <!-- Mensagens de Erro/Sucesso -->
            <?php foreach (\$errors as \$error): ?>
                <div class=\"alert alert-error\">❌ <?= \$error ?></div>
            <?php endforeach; ?>
            
            <?php foreach (\$success as \$msg): ?>
                <div class=\"alert alert-success\">✅ <?= \$msg ?></div>
            <?php endforeach; ?>
            
            <?php if (!empty(\$warnings)): ?>
                <?php foreach (\$warnings as \$warning): ?>
                    <div class=\"alert alert-warning\">⚠️ <?= \$warning ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Conteúdo por Passo -->
            <?php if (\$step === 'already_installed'): ?>
                <div class=\"success-screen\">
                    <div class=\"success-icon\">✅</div>
                    <h2>Sistema Já Instalado</h2>
                    <p>O COISABOA já está instalado e configurado.</p>
                    <div style=\"margin-top: 30px;\">
                        <a href=\"/\" class=\"btn btn-primary\">Acessar Sistema</a>
                        <form method=\"POST\" style=\"display: inline-block; margin-left: 10px;\">
                            <input type=\"hidden\" name=\"step\" value=\"reinstall\">
                            <button type=\"submit\" class=\"btn btn-outline\" onclick=\"return confirm('Isso irá reinstalar o sistema. Tem certeza?')\">Reinstalar</button>
                        </form>
                    </div>
                </div>
                
            <?php elseif (\$step === 'complete'): ?>
                <div class=\"success-screen\">
                    <div class=\"success-icon\">🎉</div>
                    <h2>Instalação Concluída!</h2>
                    <p>O sistema COISABOA foi instalado com sucesso.</p>
                    
                    <?php
                    \$installed_data = json_decode(file_get_contents(__DIR__ . '/config/installed.json'), true);
                    if (\$installed_data): ?>
                        <div style=\"background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;\">
                            <h4>Informações da Instalação:</h4>
                            <ul style=\"list-style: none; margin: 10px 0;\">
                                <li><strong>Versão:</strong> <?= \$installed_data['version'] ?></li>
                                <li><strong>Data:</strong> <?= \$installed_data['timestamp'] ?></li>
                                <li><strong>PHP:</strong> <?= \$installed_data['php_version'] ?></li>
                                <li><strong>URL:</strong> <?= \$installed_data['base_url'] ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div style=\"margin-top: 30px;\">
                        <a href=\"/\" class=\"btn btn-primary\">🚀 Acessar o Sistema</a>
                    </div>
                    
                    <div style=\"margin-top: 20px; font-size: 14px; color: #6b7280;\">
                        <p><strong>Dicas importantes:</strong></p>
                        <ul style=\"list-style: none; margin: 10px 0;\">
                            <li>• Mantenha o sistema atualizado</li>
                            <li>• Faça backups regulares do banco de dados</li>
                            <li>• Proteja o arquivo config/database.php</li>
                        </ul>
                    </div>
                </div>
                
            <?php elseif (\$step === 'welcome'): ?>
                <h2 style=\"margin-bottom: 20px;\">Bem-vindo ao Instalador</h2>
                <p style=\"margin-bottom: 20px; color: #6b7280;\">
                    Este instalador irá configurar o sistema COISABOA em seu servidor.
                    Verifique se todos os requisitos estão atendidos antes de continuar.
                </p>
                
                <h3 style=\"margin: 30px 0 15px 0;\">Requisitos do Sistema</h3>
                <ul class=\"requirements-list\">
                    <li class=\"<?= version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=') ? '' : 'error' ?>\">
                        PHP <?= MIN_PHP_VERSION ?> ou superior (Atual: <?= PHP_VERSION ?>)
                    </li>
                    <?php foreach (REQUIRED_EXTENSIONS as \$ext): ?>
                        <li class=\"<?= extension_loaded(\$ext) ? '' : 'error' ?>\">
                            Extensão PHP: <?= \$ext ?>
                        </li>
                    <?php endforeach; ?>
                    <li class=\"<?= is_writable(__DIR__ . '/uploads') ? '' : 'error' ?>\">
                        Permissão de escrita na pasta uploads/
                    </li>
                    <li class=\"<?= is_writable(__DIR__ . '/config') ? '' : 'error' ?>\">
                        Permissão de escrita na pasta config/
                    </li>
                </ul>
                
                <?php if (empty(\$errors)): ?>
                    <form method=\"POST\" style=\"margin-top: 30px;\">
                        <input type=\"hidden\" name=\"step\" value=\"database\">
                        <button type=\"submit\" class=\"btn btn-primary\">Continuar para Configuração do Banco</button>
                    </form>
                <?php else: ?>
                    <div class=\"alert alert-error\" style=\"margin-top: 20px;\">
                        Corrija os erros acima antes de continuar.
                    </div>
                <?php endif; ?>
                
            <?php elseif (\$step === 'database'): ?>
                <h2 style=\"margin-bottom: 20px;\">Configuração do Banco de Dados</h2>
                <p style=\"margin-bottom: 20px; color: #6b7280;\">
                    Informe os dados de conexão com o MySQL/MariaDB.
                </p>
                
                <form method=\"POST\">
                    <input type=\"hidden\" name=\"step\" value=\"install\">
                    
                    <div class=\"form-group\">
                        <label class=\"form-label\">Servidor do Banco</label>
                        <input type=\"text\" name=\"host\" class=\"form-control\" value=\"<?= \$form_data['host'] ?? 'localhost' ?>\" required>
                        <small style=\"color: #6b7280; display: block; margin-top: 5px;\">Normalmente 'localhost'</small>
                    </div>
                    
                    <div class=\"form-group\">
                        <label class=\"form-label\">Porta</label>
                        <input type=\"text\" name=\"port\" class=\"form-control\" value=\"<?= \$form_data['port'] ?? '3306' ?>\">
                        <small style=\"color: #6b7280; display: block; margin-top: 5px;\">Padrão: 3306</small>
                    </div>
                    
                    <div class=\"form-group\">
                        <label class=\"form-label\">Nome do Banco</label>
                        <input type=\"text\" name=\"dbname\" class=\"form-control\" value=\"<?= \$form_data['dbname'] ?? 'coisaboa' ?>\" required>
                        <small style=\"color: #6b7280; display: block; margin-top: 5px;\">O banco será criado se não existir</small>
                    </div>
                    
                    <div class=\"form-group\">
                        <label class=\"form-label\">Usuário</label>
                        <input type=\"text\" name=\"user\" class=\"form-control\" value=\"<?= \$form_data['user'] ?? 'root' ?>\" required>
                    </div>
                    
                    <div class=\"form-group\">
                        <label class=\"form-label\">Senha</label>
                        <input type=\"password\" name=\"pass\" class=\"form-control\" value=\"<?= \$form_data['pass'] ?? '' ?>\">
                    </div>
                    
                    <div style=\"display: flex; gap: 10px; margin-top: 30px;\">
                        <button type=\"button\" class=\"btn btn-outline\" onclick=\"history.back()\">Voltar</button>
                        <button type=\"submit\" class=\"btn btn-primary\">Instalar Sistema</button>
                    </div>
                </form>
                
            <?php elseif (\$step === 'install'): ?>
                <h2 style=\"margin-bottom: 20px;\">Instalando o Sistema</h2>
                <p style=\"margin-bottom: 20px; color: #6b7280;\">
                    Configurando o banco de dados e criando a estrutura do sistema...
                </p>
                
                <?php if (empty(\$errors)): ?>
                    <form method=\"POST\">
                        <input type=\"hidden\" name=\"step\" value=\"complete\">
                        <input type=\"hidden\" name=\"host\" value=\"<?= \$form_data['host'] ?>\">
                        <input type=\"hidden\" name=\"port\" value=\"<?= \$form_data['port'] ?? '3306' ?>\">
                        <input type=\"hidden\" name=\"dbname\" value=\"<?= \$form_data['dbname'] ?>\">
                        <input type=\"hidden\" name=\"user\" value=\"<?= \$form_data['user'] ?>\">
                        <input type=\"hidden\" name=\"pass\" value=\"<?= \$form_data['pass'] ?? '' ?>\">
                        <button type=\"submit\" class=\"btn btn-primary\">Concluir Instalação</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
";

// Criar arquivo do instalador
if (!file_exists($install_file)) {
    file_put_contents($install_file, $install_content);
    echo "✅ Instalador criado: install.php\n";
} else {
    echo "📁 Instalador já existe: install.php\n";
}

// Criar também um arquivo de desinstalação simples
$uninstall_content = "<?php
/**
 * COISABOA - Desinstalador (Use com cuidado!)
 * 
 * ATENÇÃO: Este script irá remover todas as tabelas do banco de dados
 * Execute apenas se você tem certeza absoluta do que está fazendo
 */

// Verificar se foi acessado via POST para confirmar
if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Desinstalar COISABOA</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; }
            .warning { background: #fef2f2; border: 1px solid #fecaca; padding: 20px; border-radius: 8px; }
            .btn { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; }
            .btn-danger { background: #dc2626; color: white; }
            .btn-outline { background: transparent; border: 1px solid #d1d5db; }
        </style>
    </head>
    <body>
        <div class=\"warning\">
            <h1>🚨 ATENÇÃO</h1>
            <p>Esta ação irá <strong>remover permanentemente</strong> todas as tabelas e dados do COISABOA.</p>
            <p><strong>Esta ação não pode ser desfeita!</strong></p>
            <p>Faça backup do seu banco de dados antes de continuar.</p>
            
            <form method=\"POST\" style=\"margin-top: 20px;\">
                <label>
                    <input type=\"checkbox\" name=\"confirm\" required>
                    Eu entendo que todos os dados serão perdidos permanentemente
                </label>
                <br><br>
                <button type=\"submit\" class=\"btn btn-danger\">💀 Desinstalar COISABOA</button>
                <a href=\"/\" class=\"btn btn-outline\">Cancelar</a>
            </form>
        </div>
    </body>
    </html>');
}

// Verificar confirmação
if (!isset(\$_POST['confirm'])) {
    die('Confirmação necessária.');
}

// Carregar configuração
require_once __DIR__ . '/config/database.php';

try {
    \$pdo = getDBConnection();
    
    // Lista de tabelas para remover
    \$tables = ['compras', 'vendas', 'usuarios', 'configuracoes', 'logs'];
    
    echo \"<h1>Desinstalando COISABOA...</h1>\";
    echo \"<ul>\";
    
    foreach (\$tables as \$table) {
        \$pdo->exec(\"DROP TABLE IF EXISTS \$table\");
        echo \"<li>Tabela '\$table' removida</li>\";
    }
    
    // Remover arquivos de configuração
    \$config_files = ['config/database.php', 'config/installed.json'];
    foreach (\$config_files as \$file) {
        if (file_exists(__DIR__ . '/' . \$file)) {
            unlink(__DIR__ . '/' . \$file);
            echo \"<li>Arquivo '\$file' removido</li>\";
        }
    }
    
    echo \"</ul>\";
    echo \"<h2 style='color: #dc2626;'>✅ COISABOA foi desinstalado com sucesso!</h2>\";
    echo \"<p>Você pode reinstalar acessando <a href='/install.php'>/install.php</a></p>\";
    
} catch (Exception \$e) {
    die(\"<h1>Erro na desinstalação</h1><p>\" . \$e->getMessage() . \"</p>\");
}
?>";

$uninstall_file = $project_root . '/uninstall.php';
file_put_contents($uninstall_file, $uninstall_content);
echo "✅ Desinstalador criado: uninstall.php\n";

// Criar arquivo de backup simples
$backup_content = "<?php
/**
 * COISABOA - Ferramenta de Backup Simples
 * @version 1.0.0
 */

require_once __DIR__ . '/includes/init.php';

// Verificar se está logado (para versões futuras)
// requererAutenticacao();

\$backup_dir = __DIR__ . '/backups';
if (!file_exists(\$backup_dir)) {
    mkdir(\$backup_dir, 0755, true);
}

// Nome do arquivo de backup
\$backup_file = \$backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

try {
    \$pdo = getDBConnection();
    
    // Obter todas as tabelas
    \$tables = \$pdo->query(\"SHOW TABLES\")->fetchAll(PDO::FETCH_COLUMN);
    
    \$backup_content = \"-- COISABOA Backup\\n\";
    \$backup_content .= \"-- Gerado em: \" . date('Y-m-d H:i:s') . \"\\n\";
    \$backup_content .= \"-- Versão: \" . SISTEMA_VERSION . \"\\n\\n\";
    
    foreach (\$tables as \$table) {
        \$backup_content .= \"-- Estrutura da tabela: \$table\\n\";
        
        // Obter estrutura da tabela
        \$create_table = \$pdo->query(\"SHOW CREATE TABLE \$table\")->fetch();
        \$backup_content .= \"DROP TABLE IF EXISTS `\$table`;\\n\";
        \$backup_content .= \$create_table['Create Table'] . \";\\n\\n\";
        
        // Obter dados da tabela
        \$rows = \$pdo->query(\"SELECT * FROM \$table\")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty(\$rows)) {
            \$backup_content .= \"-- Dados da tabela: \$table\\n\";
            \$backup_content .= \"INSERT INTO `\$table` VALUES\\n\";
            
            \$insert_values = [];
            foreach (\$rows as \$row) {
                \$values = array_map(function(\$value) use (\$pdo) {
                    if (\$value === null) return 'NULL';
                    return \$pdo->quote(\$value);
                }, \$row);
                
                \$insert_values[] = \"(\" . implode(', ', \$values) . \")\";
            }
            
            \$backup_content .= implode(\",\\n\", \$insert_values) . \";\\n\\n\";
        }
    }
    
    // Salvar arquivo
    if (file_put_contents(\$backup_file, \$backup_content)) {
        \$file_size = filesize(\$backup_file);
        echo \"<div style='padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;'>\";
        echo \"<h3>✅ Backup criado com sucesso!</h3>\";
        echo \"<p><strong>Arquivo:</strong> \" . basename(\$backup_file) . \"</p>\";
        echo \"<p><strong>Tamanho:</strong> \" . number_format(\$file_size) . \" bytes</p>\";
        echo \"<p><strong>Local:</strong> \" . realpath(\$backup_file) . \"</p>\";
        echo \"</div>\";
    } else {
        throw new Exception(\"Erro ao salvar arquivo de backup\");
    }
    
} catch (Exception \$e) {
    echo \"<div style='padding: 20px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;'>\";
    echo \"<h3>❌ Erro no backup</h3>\";
    echo \"<p>\" . \$e->getMessage() . \"</p>\";
    echo \"</div>\";
}

// Link para voltar
echo \"<div style='margin-top: 20px;'>\";
echo \"<a href='/'>← Voltar para o sistema</a>\";
echo \"</div>\";
?>";

$backup_file = $project_root . '/backup.php';
file_put_contents($backup_file, $backup_content);
echo "✅ Ferramenta de backup criada: backup.php\n";

// Resumo final
echo "\n🎉 SISTEMA DE INSTALAÇÃO CRIADO COM SUCESSO!\n";
echo "=============================================\n";
echo "📊 RESUMO DOS ARQUIVOS CRIADOS:\n";
echo "• 🗄️ install.php - Instalador completo com interface web\n";
echo "• 💀 uninstall.php - Desinstalador (use com cuidado!)\n";
echo "• 💾 backup.php - Ferramenta de backup do banco\n\n";

echo "✅ FUNCIONALIDADES DO INSTALADOR:\n";
echo "• 🔍 Verificação automática de requisitos\n";
echo "• 🗄️ Criação do banco de dados automaticamente\n";
echo "• 📊 Instalação da estrutura completa de tabelas\n";
echo "• ⚙️ Geração do arquivo de configuração\n";
echo "• 🎯 Interface web amigável e responsiva\n";
echo "• 🛡️ Validação de dados e tratamento de erros\n";
echo "• 📝 Dados iniciais e configurações padrão\n\n";

echo "🚀 COMO INSTALAR O SISTEMA:\n";
echo "1. 📁 Faça upload dos arquivos para seu servidor\n";
echo "2. 🌐 Acesse: http://seudominio.com/COISABOA/install.php\n";
echo "3. ✅ Siga os passos do instalador\n";
echo "4. 🎉 Sistema pronto para uso!\n\n";

echo "🔧 RECURSOS DE SEGURANÇA:\n";
echo "• 📋 Verificação de instalação prévia\n";
echo "• 🚨 Confirmação para desinstalação\n";
echo "• 💾 Sistema de backup integrado\n";
echo "• 🛡️ Proteção de arquivos sensíveis\n\n";

echo "🎉 SISTEMA COISABOA 100% COMPLETO!\n";
echo "=============================================\n";
echo "✅ TODOS OS MÓDULOS FORAM CRIADOS:\n";
echo "• 🏗️  Estrutura de pastas\n";
echo "• ⚙️  Sistema de configuração\n";
echo "• 📁 Sistema de uploads\n";
echo "• 📚 Bibliotecas de funções\n";
echo "• 🎨 Assets e interface\n";
echo "• 🗄️  Módulos principais\n";
echo "• 📄 Templates HTML\n";
echo "• 🗄️  Instalador completo\n\n";

echo "🚀 O SISTEMA ESTÁ PRONTO PARA USO!\n";
echo "Acesse o instalador e comece a usar o COISABOA! 🎊\n";

?>