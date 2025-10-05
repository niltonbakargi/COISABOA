<?php
/**
 * PROXY UNIVERSAL - Versão Mais Robusta
 */
header('Content-Type: application/json');
session_start();

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Ler input de múltiplas formas
$endpoint = '';
$inputData = [];

// Método 1: POST padrão
if (!empty($_POST)) {
    $endpoint = $_POST['endpoint'] ?? '';
    $inputData = $_POST;
} 
// Método 2: JSON input
else {
    $jsonInput = file_get_contents('php://input');
    if (!empty($jsonInput)) {
        $decoded = json_decode($jsonInput, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $endpoint = $decoded['endpoint'] ?? '';
            $inputData = $decoded;
        } else {
            // Método 3: form-data tradicional
            parse_str($jsonInput, $parsed);
            $endpoint = $parsed['endpoint'] ?? '';
            $inputData = $parsed;
        }
    }
}

// Método 4: Fallback para GET
if (empty($endpoint)) {
    $endpoint = $_GET['endpoint'] ?? '';
    $inputData = $_GET;
}

// Log para debug
error_log("API Proxy - Endpoint: " . $endpoint);
error_log("API Proxy - Input data: " . print_r($inputData, true));

$mapeamento = [
    'login' => 'login.php',
    'dashboard' => 'dashboard.php', 
    'vender' => 'vender.php',
    'comprar' => 'comprar.php',
    'produtos' => 'incluirproduto.php',
    'estoque' => 'relatorio_de_estoque.php',
    'relatorios' => 'relatorios.php',
    'backup' => 'backup_simples.php',
    'gerenciar' => 'gerenciar.php'
];

if (empty($endpoint)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Endpoint não especificado',
        'debug' => [
            'received_post' => $_POST,
            'received_get' => $_GET,
            'raw_input' => file_get_contents('php://input'),
            'available_endpoints' => array_keys($mapeamento)
        ]
    ]);
    exit;
}

if (isset($mapeamento[$endpoint]) && file_exists($mapeamento[$endpoint])) {
    try {
        // Simular $_POST para o arquivo incluído
        $_POST = $inputData;
        
        ob_start();
        include $mapeamento[$endpoint];
        $output = ob_get_clean();
        
        // Verificar redirect
        if (preg_match('/Location:\s*([^\s]+\.php)/', $output, $matches)) {
            echo json_encode([
                'success' => true,
                'redirect' => trim($matches[1]),
                'message' => 'Redirecionamento detectado'
            ]);
        } 
        // Tentar parsear como JSON
        else if ($json = json_decode($output, true)) {
            echo json_encode($json);
        }
        // Retornar HTML
        else {
            echo json_encode([
                'success' => true,
                'html' => $output,
                'message' => 'HTML retornado'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Endpoint não encontrado: ' . $endpoint,
        'available' => array_keys($mapeamento)
    ]);
}
?>