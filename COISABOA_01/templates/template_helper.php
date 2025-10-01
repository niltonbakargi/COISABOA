<?php
/**
 * COISABOA - Helper de Templates
 * @version 1.0.0
 */

/**
 * Renderiza um template incluindo header e footer
 */
function renderTemplate($template, $data = []) {
    // Extrair dados para variáveis
    extract($data);
    
    // Incluir header
    include __DIR__ . '/header.php';
    
    // Incluir template específico
    include __DIR__ . '/' . $template;
    
    // Incluir footer
    include __DIR__ . '/footer.php';
}

/**
 * Renderiza um componente reutilizável
 */
function renderComponent($component, $data = []) {
    extract($data);
    include __DIR__ . '/components/' . $component . '.php';
}

/**
 * Verifica se a página atual é a ativa
 */
function isActivePage($page_name) {
    return ($GLOBALS['current_page'] ?? '') === $page_name;
}

/**
 * Retorna classe CSS para item de menu ativo
 */
function getActiveClass($page_name) {
    return isActivePage($page_name) ? 'active' : '';
}

/**
 * Gera HTML para ícones
 */
function icon($name, $classes = '') {
    $icons = [
        'dashboard' => '📊',
        'compras' => '🛒',
        'vendas' => '💰',
        'estoque' => '📦',
        'config' => '⚙️',
        'user' => '👤',
        'logout' => '🚪',
        'add' => '➕',
        'edit' => '✏️',
        'delete' => '🗑️',
        'search' => '🔍',
        'filter' => '🔧',
        'download' => '📥',
        'upload' => '📤',
        'check' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $icon = $icons[$name] ?? '📄';
    return '<span class="icon ' . $classes . '">' . $icon . '</span>';
}

/**
 * Gera URL para assets com versionamento
 */
function asset($path) {
    $full_path = ASSETS_PATH . '/' . $path;
    
    if (file_exists($full_path)) {
        return '/assets/' . $path . '?v=' . filemtime($full_path);
    }
    
    return '/assets/' . $path;
}

/**
 * Gera URL para páginas do sistema
 */
function url($path = '') {
    $base_url = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $base_url .= $_SERVER['HTTP_HOST'];
    
    if (!empty($path)) {
        $base_url .= '/' . ltrim($path, '/');
    }
    
    return $base_url;
}
?>