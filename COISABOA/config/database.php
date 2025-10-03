<?php
/**
 * COISABOA - Configuração do Sistema
 * Gerado automaticamente em 03/10/2025 13:54:17
 */

// Configurações do Ambiente
define('ENVIRONMENT', 'development'); // development, production
define('SITE_NAME', 'COISABOA App');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'coisaboa_app');  
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Configurações de Upload
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações de Sessão
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// Configurações de Segurança
define('ENCRYPTION_KEY', 'sua_chave_secreta_aqui_altere_em_producao');

// Configuração de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configuração de exibição de erros (baseado no ambiente)
if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
            ini_set('error_log', __DIR__ . '/logs/php_errors.log');
            break;
        case 'production':
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', __DIR__ . '/logs/php_errors.log');
            break;
        default:
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            break;
    }
}

// Função de conexão com o banco
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Erro de conexão: ' . $e->getMessage());
            
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                die('Erro de conexão com o banco de dados: ' . $e->getMessage());
            } else {
                http_response_code(503);
                die('Serviço temporariamente indisponível. Tente novamente em alguns instantes.');
            }
        }
    }
    
    return $pdo;
}

// Função de redirecionamento mobile-friendly
function redirect($url, $statusCode = 303) {
    if (!headers_sent()) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
        exit;
    }
}

// Função de sanitização
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    if ($data === null) {
        return null;
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Função para verificar se está logado
function isLoggedIn() {
    session_start();
    
    if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
        return false;
    }
    
    // Verificar timeout da sessão
    if (isset($_SESSION['LAST_ACTIVITY']) && 
        (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

// Função para requerer login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('index.php');
    }
}

// Função para fazer logout
function logout() {
    session_start();
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    redirect('index.php');
}

// Função para gerar hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

// Função para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Função para criar diretório de uploads se não existir
function initUploadDir() {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Criar arquivo .htaccess para proteger o diretório
    $htaccess = UPLOAD_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
    }
}

// Função para log de atividades
function logActivity($mensagem, $usuario_id = null) {
    $logFile = __DIR__ . '/logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = $usuario_id ?: (isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 'Sistema');
    
    $logEntry = "[{$timestamp}] User:{$user} - {$mensagem}" . PHP_EOL;
    
    // Criar diretório de logs se não existir
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Função para debug (apenas em desenvolvimento)
function debug($data, $exit = true) {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($exit) {
            exit;
        }
    }
}

// Inicialização automática
function initSystem() {
    // Iniciar sessão se não estiver iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Inicializar diretório de uploads
    initUploadDir();
    
    // Verificar e criar estrutura de logs
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

// Inicializar sistema
initSystem();

?>