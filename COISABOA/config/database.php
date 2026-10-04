<?php
/**
 * FEIRAVERDE - Configuração de Banco de Dados
 * Adaptado para servidor Hostinger
 */

// ==================== CONFIGURAÇÕES DO BANCO DE DADOS ====================

define('DB_HOST', 'localhost');
define('DB_NAME', 'b62a4147_coisaboa_app');   // nome completo do banco na Hostinger
define('DB_USER', 'b62a4147_coisaboa_user');  // usuário completo da Hostinger
define('DB_PASS', 'SUA_SENHA_AQUI');          // senha criada no painel
define('DB_PORT', '3306');

// ==================== CONFIGURAÇÕES DO SISTEMA ====================

define('SITE_NAME', 'Feira Verde');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// ==================== FUNÇÃO DE CONEXÃO COM O BANCO ====================

function getDB() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Erro de conexão: ' . $e->getMessage());
        http_response_code(500);
        die('❌ Erro de conexão com o banco de dados. Verifique as credenciais ou contate o suporte.');
    }
}

// ==================== OUTRAS FUNÇÕES ÚTEIS ====================

function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
