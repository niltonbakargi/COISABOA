<?php
/**
 * COISABOA - Instalador do Sistema
 * @version 1.0.1
 * 
 * Este arquivo instala e configura o sistema COISABOA
 * Execute apenas uma vez após o download
 */

// ==================== CONFIGURAÇÕES ====================
define('INSTALLER_VERSION', '1.0.1');
define('MIN_PHP_VERSION', '7.4.0');
define('REQUIRED_EXTENSIONS', ['pdo_mysql', 'mbstring', 'fileinfo']);

// ==================== FUNÇÕES DO INSTALADOR ====================
function checkRequirements() {
    $errors = [];
    $warnings = [];
    
    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
        $errors[] = "PHP versão " . MIN_PHP_VERSION . " ou superior é necessária. Versão atual: " . PHP_VERSION;
    }
    
    // Verificar extensões necessárias
    foreach (REQUIRED_EXTENSIONS as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extensão PHP '$ext' não está instalada.";
        }
    }
    
    // Verificar permissões de escrita
    $writable_dirs = ['uploads', 'config'];
    foreach ($writable_dirs as $dir) {
        $dir_path = __DIR__ . '/' . $dir;
        if (!is_writable($dir_path) && file_exists($dir_path)) {
            $warnings[] = "Pasta '$dir' não tem permissão de escrita. Uploads podem não funcionar.";
        }
    }
    
    // Verificar se pastas necessárias existem
    $required_dirs = ['config', 'uploads'];
    foreach ($required_dirs as $dir) {
        if (!file_exists(__DIR__ . '/' . $dir)) {
            $warnings[] = "Pasta '$dir' não existe. O instalador tentará criá-la.";
        }
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

function testDatabaseConnection($host, $user, $pass, $port = '3306') {
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        return ['success' => true, 'message' => 'Conexão bem-sucedida!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro de conexão: ' . $e->getMessage()];
    }
}

function createDatabase($host, $user, $pass, $dbname, $port = '3306') {
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Criar banco de dados se não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return ['success' => true, 'message' => 'Banco de dados criado com sucesso!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro ao criar banco: ' . $e->getMessage()];
    }
}

function installDatabaseSchema($host, $user, $pass, $dbname, $port = '3306') {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // ==================== CRIAÇÃO DAS TABELAS ====================
        
        // Tabela de compras
        $pdo->exec("
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
        ");
        
        // Tabela de vendas
        $pdo->exec("
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
        ");
        
        // Tabela de usuários (AGORA FUNCIONAL)
        $pdo->exec("
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
        ");
        
        // Tabela de configurações
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracoes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                chave VARCHAR(50) UNIQUE NOT NULL,
                valor TEXT NOT NULL,
                tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
                descricao TEXT NULL,
                data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // ==================== DADOS INICIAIS ====================
        
        // Inserir usuário admin padrão (senha: 123456)
        $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT IGNORE INTO usuarios (nome, email, senha, nivel, ativo) 
            VALUES (
                'Administrador', 
                'admin@coisaboa.com', 
                '$senha_hash', 
                'admin', 
                TRUE
            )
        ");
        
        // Inserir configurações padrão
        $configuracoes = [
            ['empresa_nome', 'Minha Empresa', 'string', 'Nome da empresa'],
            ['empresa_cnpj', '', 'string', 'CNPJ da empresa'],
            ['estoque_minimo', '5', 'number', 'Estoque mínimo para alertas'],
            ['notificar_estoque_baixo', 'true', 'boolean', 'Notificar sobre estoque baixo'],
            ['formas_pagamento', '[\"dinheiro\", \"cartao_credito\", \"cartao_debito\", \"pix\"]', 'json', 'Formas de pagamento aceitas']
        ];
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($configuracoes as $config) {
            $stmt->execute($config);
        }
        
        return [
            'success' => true, 
            'message' => 'Estrutura do banco criada com sucesso!',
            'tables' => ['compras', 'vendas', 'usuarios', 'configuracoes']
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro na instalação: ' . $e->getMessage()];
    }
}

function createConfigFile($config) {
    $config_content = "<?php
/**
 * COISABOA - Configuração do Banco de Dados
 * Gerado automaticamente pelo instalador
 */

// Configurações do Banco de Dados
define('DB_HOST', '{$config['host']}');
define('DB_NAME', '{$config['dbname']}');
define('DB_USER', '{$config['user']}');
define('DB_PASS', '{$config['pass']}');
define('DB_PORT', '{$config['port']}');
define('DB_CHARSET', 'utf8mb4');

// Opções do PDO
\\\$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"
];

/**
 * Conexão com o banco de dados
 */
function getDBConnection() {
    \\\$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
    
    try {
        \\\$pdo = new PDO(\\\$dsn, DB_USER, DB_PASS, \\\$GLOBALS['db_options']);
        return \\\$pdo;
    } catch (PDOException \\\$e) {
        error_log('Erro de conexão: ' . \\\$e->getMessage());
        die('<div style=\"padding: 20px; background: #fee; border: 1px solid #fcc; margin: 20px;\">
            <h3>Erro de Conexão com o Banco</h3>
            <p>Não foi possível conectar ao banco de dados.</p>
            <p><small>Detalhes: ' . \\\$e->getMessage() . '</small></p>
        </div>');
    }
}
?>";
    
    // Garantir que a pasta config existe
    if (!file_exists(__DIR__ . '/config')) {
        mkdir(__DIR__ . '/config', 0755, true);
    }
    
    return file_put_contents(__DIR__ . '/config/database.php', $config_content);
}

function createUploadsFolder() {
    $uploads_path = __DIR__ . '/uploads';
    $subfolders = ['vendors', 'temp'];
    
    if (!file_exists($uploads_path)) {
        mkdir($uploads_path, 0755, true);
    }
    
    foreach ($subfolders as $folder) {
        $folder_path = $uploads_path . '/' . $folder;
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0755, true);
        }
        
        // Criar arquivo index.html para proteção
        file_put_contents($folder_path . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden</h1></body></html>');
    }
    
    return true;
}

function finalizeInstallation() {
    // Criar pasta de uploads
    createUploadsFolder();
    
    // Criar arquivo de instalação concluída
    $install_flag = [
        'version' => INSTALLER_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'base_url' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',
        'install_path' => __DIR__
    ];
    
    file_put_contents(__DIR__ . '/config/installed.json', json_encode($install_flag, JSON_PRETTY_PRINT));
    
    return true;
}

function isAlreadyInstalled() {
    $config_file = __DIR__ . '/config/database.php';
    $installed_file = __DIR__ . '/config/installed.json';
    
    // Verificar se ambos os arquivos existem
    if (file_exists($config_file) && file_exists($installed_file)) {
        // Verificar se o arquivo de configuração não está vazio
        $config_content = file_get_contents($config_file);
        if (strlen(trim($config_content)) > 100) {
            return true;
        }
    }
    
    return false;
}

// ==================== PROCESSAMENTO DO INSTALADOR ====================

// Iniciar sessão para mensagens entre steps
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = $_POST['step'] ?? 'welcome';
$errors = [];
$success = [];
$form_data = $_POST;

// Limpar mensagens antigas
if (isset($_SESSION['install_errors'])) {
    $errors = $_SESSION['install_errors'];
    unset($_SESSION['install_errors']);
}

if (isset($_SESSION['install_success'])) {
    $success = $_SESSION['install_success'];
    unset($_SESSION['install_success']);
}

// Verificar se já está instalado
if (isAlreadyInstalled() && $step !== 'reinstall') {
    $step = 'already_installed';
}

// Processar cada passo
switch ($step) {
    case 'welcome':
        $requirements = checkRequirements();
        $errors = array_merge($errors, $requirements['errors']);
        $warnings = $requirements['warnings'];
        break;
        
    case 'database':
        // Validar dados do formulário
        $required = ['host', 'user', 'dbname'];
        foreach ($required as $field) {
            if (empty($form_data[$field])) {
                $errors[] = "O campo '$field' é obrigatório.";
            }
        }
        
        if (empty($errors)) {
            // Testar conexão com o banco
            $connection_test = testDatabaseConnection(
                $form_data['host'],
                $form_data['user'], 
                $form_data['pass'] ?? '',
                $form_data['port'] ?? '3306'
            );
            
            if ($connection_test['success']) {
                $success[] = $connection_test['message'];
                
                // Criar banco de dados
                $db_creation = createDatabase(
                    $form_data['host'],
                    $form_data['user'],
                    $form_data['pass'] ?? '',
                    $form_data['dbname'],
                    $form_data['port'] ?? '3306'
                );
                
                if ($db_creation['success']) {
                    $success[] = $db_creation['message'];
                    $_SESSION['form_data'] = $form_data;
                } else {
                    $errors[] = $db_creation['message'];
                }
            } else {
                $errors[] = $connection_test['message'];
            }
        }
        break;
        
    case 'install':
        if (empty($errors)) {
            // Usar dados da sessão
            $form_data = $_SESSION['form_data'] ?? $form_data;
            
            // Instalar schema do banco
            $installation = installDatabaseSchema(
                $form_data['host'],
                $form_data['user'],
                $form_data['pass'] ?? '',
                $form_data['dbname'],
                $form_data['port'] ?? '3306'
            );
            
            if ($installation['success']) {
                $success[] = $installation['message'];
                
                // Criar arquivo de configuração
                if (createConfigFile($form_data)) {
                    $success[] = 'Arquivo de configuração criado com sucesso!';
                    
                    // Finalizar instalação
                    if (finalizeInstallation()) {
                        $success[] = 'Instalação concluída com sucesso!';
                        $step = 'complete';
                        
                        // Limpar sessão
                        unset($_SESSION['form_data']);
                    }
                } else {
                    $errors[] = 'Erro ao criar arquivo de configuração. Verifique permissões da pasta config/.';
                }
            } else {
                $errors[] = $installation['message'];
            }
        }
        break;
        
    case 'reinstall':
        // Limpar instalação anterior
        @unlink(__DIR__ . '/config/installed.json');
        @unlink(__DIR__ . '/config/database.php');
        $step = 'welcome';
        $success[] = 'Instalação anterior removida. Você pode reinstalar o sistema.';
        break;
}

// Salvar mensagens para o próximo step
if (!empty($errors)) {
    $_SESSION['install_errors'] = $errors;
}
if (!empty($success)) {
    $_SESSION['install_success'] = $success;
}

// ==================== INTERFACE DO INSTALADOR ====================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        .demo-credentials {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>📱 COISABOA</h1>
            <p>Instalador do Sistema</p>
        </div>
        
        <div class="installer-content">
            <!-- Indicador de Passos -->
            <div class="step-indicator">
                <div class="step <?= in_array($step, ['welcome', 'database', 'install', 'complete']) ? 'completed' : '' ?>">1</div>
                <div class="step <?= in_array($step, ['database', 'install', 'complete']) ? 'completed' : '' ?>">2</div>
                <div class="step <?= in_array($step, ['install', 'complete']) ? 'completed' : '' ?>">3</div>
                <div class="step <?= $step === 'complete' ? 'completed active' : '' ?>">4</div>
            </div>
            
            <!-- Mensagens de Erro/Sucesso -->
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">❌ <?= $error ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success">✅ <?= $msg ?></div>
            <?php endforeach; ?>
            
            <?php if (!empty($warnings)): ?>
                <?php foreach ($warnings as $warning): ?>
                    <div class="alert alert-warning">⚠️ <?= $warning ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Conteúdo por Passo -->
            <?php if ($step === 'already_installed'): ?>
                <div class="success-screen">
                    <div class="success-icon">✅</div>
                    <h2>Sistema Já Instalado</h2>
                    <p>O COISABOA já está instalado e configurado.</p>
                    <div style="margin-top: 30px;">
                        <a href="./" class="btn btn-primary">Acessar Sistema</a>
                        <form method="POST" style="display: inline-block; margin-left: 10px;">
                            <input type="hidden" name="step" value="reinstall">
                            <button type="submit" class="btn btn-outline" onclick="return confirm('ATENÇÃO: Isso irá reinstalar o sistema e apagar todos os dados. Tem certeza?')">Reinstalar</button>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($step === 'complete'): ?>
                <div class="success-screen">
                    <div class="success-icon">🎉</div>
                    <h2>Instalação Concluída!</h2>
                    <p>O sistema COISABOA foi instalado com sucesso.</p>
                    
                    <div class="demo-credentials">
                        <h4>👤 Dados de Acesso:</h4>
                        <p><strong>Email:</strong> admin@coisaboa.com</p>
                        <p><strong>Senha:</strong> 123456</p>
                        <p><small>⚠️ Altere esta senha após o primeiro login!</small></p>
                    </div>
                    
                    <?php
                    $installed_data = json_decode(file_get_contents(__DIR__ . '/config/installed.json'), true);
                    if ($installed_data): ?>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                            <h4>Informações da Instalação:</h4>
                            <ul style="list-style: none; margin: 10px 0;">
                                <li><strong>Versão:</strong> <?= $installed_data['version'] ?></li>
                                <li><strong>Data:</strong> <?= $installed_data['timestamp'] ?></li>
                                <li><strong>PHP:</strong> <?= $installed_data['php_version'] ?></li>
                                <li><strong>URL:</strong> <?= $installed_data['base_url'] ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 30px;">
                        <a href="./" class="btn btn-primary" style="font-size: 1.1em; padding: 15px 30px;">
                            🚀 Acessar o Sistema
                        </a>
                    </div>
                </div>
                
            <?php elseif ($step === 'welcome'): ?>
                <h2 style="margin-bottom: 20px;">Bem-vindo ao Instalador</h2>
                <p style="margin-bottom: 20px; color: #6b7280;">
                    Este instalador irá configurar o sistema COISABOA em seu servidor.
                    Verifique se todos os requisitos estão atendidos antes de continuar.
                </p>
                
                <h3 style="margin: 30px 0 15px 0;">Requisitos do Sistema</h3>
                <ul class="requirements-list">
                    <li class="<?= version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=') ? '' : 'error' ?>">
                        PHP <?= MIN_PHP_VERSION ?> ou superior (Atual: <?= PHP_VERSION ?>)
                    </li>
                    <?php foreach (REQUIRED_EXTENSIONS as $ext): ?>
                        <li class="<?= extension_loaded($ext) ? '' : 'error' ?>">
                            Extensão PHP: <?= $ext ?>
                        </li>
                    <?php endforeach; ?>
                    <li class="<?= is_writable(__DIR__ . '/uploads') || !file_exists(__DIR__ . '/uploads') ? '' : 'error' ?>">
                        Permissão de escrita na pasta uploads/
                    </li>
                    <li class="<?= is_writable(__DIR__ . '/config') || !file_exists(__DIR__ . '/config') ? '' : 'error' ?>">
                        Permissão de escrita na pasta config/
                    </li>
                </ul>
                
                <?php if (empty($errors)): ?>
                    <form method="POST" style="margin-top: 30px;">
                        <input type="hidden" name="step" value="database">
                        <button type="submit" class="btn btn-primary">Continuar para Configuração do Banco</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error" style="margin-top: 20px;">
                        Corrija os erros acima antes de continuar.
                    </div>
                <?php endif; ?>
                
            <?php elseif ($step === 'database'): ?>
                <h2 style="margin-bottom: 20px;">Configuração do Banco de Dados</h2>
                <p style="margin-bottom: 20px; color: #6b7280;">
                    Informe os dados de conexão com o MySQL/MariaDB.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="step" value="install">
                    
                    <div class="form-group">
                        <label class="form-label">Servidor do Banco</label>
                        <input type="text" name="host" class="form-control" value="<?= $form_data['host'] ?? 'localhost' ?>" required>
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Normalmente 'localhost' para XAMPP</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Porta</label>
                        <input type="text" name="port" class="form-control" value="<?= $form_data['port'] ?? '3306' ?>">
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Padrão: 3306</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nome do Banco</label>
                        <input type="text" name="dbname" class="form-control" value="<?= $form_data['dbname'] ?? 'coisaboa' ?>" required>
                        <small style="color: #6b7280; display: block; margin-top: 5px;">O banco será criado automaticamente</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usuário</label>
                        <input type="text" name="user" class="form-control" value="<?= $form_data['user'] ?? 'root' ?>" required>
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Normalmente 'root' para XAMPP</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="pass" class="form-control" value="<?= $form_data['pass'] ?? '' ?>">
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Deixe vazio se não tiver senha no XAMPP</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <a href="?step=welcome" class="btn btn-outline">Voltar</a>
                        <button type="submit" class="btn btn-primary">Instalar Sistema</button>
                    </div>
                </form>
                
            <?php elseif ($step === 'install'): ?>
                <h2 style="margin-bottom: 20px;">Instalando o Sistema</h2>
                <p style="margin-bottom: 20px; color: #6b7280;">
                    Configurando o banco de dados e criando a estrutura do sistema...
                </p>
                
                <?php if (empty($errors)): ?>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                        <p>Processando instalação...</p>
                    </div>
                    
                    <form method="POST" id="autoSubmit">
                        <input type="hidden" name="step" value="complete">
                    </form>
                    
                    <script>
                        // Auto-submit após 2 segundos
                        setTimeout(() => {
                            document.getElementById('autoSubmit').submit();
                        }, 2000);
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>