<?php
/**
 * COISABOA - Configuração de Rotas
 * Para futuras versões com URLs amigáveis
 */

$routes = [
    // Páginas principais
    '/' => 'index.php',
    '/dashboard' => 'index.php',
    '/estoque' => 'modules/inventory/estoque.php',
    '/compras' => 'modules/purchases/comprei.php',
    '/vendas' => 'modules/sales/vendi.php',
    
    // APIs (futuras versões)
    '/api/estoque' => 'modules/inventory/api_estoque.php',
    '/api/produtos' => 'modules/inventory/api_produtos.php',
];

// Função para roteamento (simples)
function route($path) {
    global $routes;
    return isset($routes[$path]) ? $routes[$path] : null;
}

?>