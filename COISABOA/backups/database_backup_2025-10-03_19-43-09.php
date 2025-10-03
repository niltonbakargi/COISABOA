<?php
/**
 * COISABOA - Configuração Simplificada
 * Gerado automaticamente em 03/10/2025 19:34:16
 */
 
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'coisaboa_app');  
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// Configurações do Sistema
define('SITE_NAME', 'COISABOA App');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Função de conexão com o banco
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
        die('Erro de conexão com o banco de dados. Tente novamente.');
    }
}

// Função de redirecionamento mobile-friendly
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }
}

// Função de sanitização
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>