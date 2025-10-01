<?php
/**
 * COISABOA - Configuração Simplificada
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'coisaboa_app');  
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

function getDB() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die('Erro de conexão: ' . $e->getMessage());
    }
}
?>