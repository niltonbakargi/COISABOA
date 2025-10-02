<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once 'config/database.php';

$produto_id = $_GET['id'] ?? '';

if (empty($produto_id)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do produto não informado']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM estoque WHERE id = ?");
    $stmt->execute([$produto_id]);
    $produto = $stmt->fetch();
    
    if ($produto) {
        header('Content-Type: application/json');
        echo json_encode($produto);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Produto não encontrado']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar produto']);
}