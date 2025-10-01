<?php
/**
 * COISABOA - Constantes do Sistema
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== INFORMAÇÕES DO SISTEMA ====================

define('SISTEMA_NOME', 'COISABOA');
define('SISTEMA_VERSION', '1.0.0');
define('SISTEMA_DESCRICAO', 'Sistema de Controle Comercial Simplificado');
define('SISTEMA_AUTOR', 'Equipe Coisa Boa');
define('SISTEMA_ANO', '2024');

// ==================== PATHS DO SISTEMA ====================

// Path absoluto do sistema
define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));

// Paths das principais pastas
define('CONFIG_PATH', ROOT_PATH . '/config');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Subpastas específicas
define('UPLOAD_VENDORS_PATH', UPLOAD_PATH . '/vendors');
define('UPLOAD_TEMP_PATH', UPLOAD_PATH . '/temp');
define('CSS_PATH', ASSETS_PATH . '/css');
define('JS_PATH', ASSETS_PATH . '/js');
define('IMAGES_PATH', ASSETS_PATH . '/images');
define('ICONS_PATH', ASSETS_PATH . '/icons');

// ==================== CONFIGURAÇÕES DE UPLOAD ====================

define('MAX_FILE_SIZE', 10485760);          // 10MB em bytes
define('MAX_IMAGE_WIDTH', 1920);            // Largura máxima
define('MAX_IMAGE_HEIGHT', 1080);           // Altura máxima

// Tipos de arquivo permitidos
define('ALLOWED_IMAGE_TYPES', [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg', 
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
]);

// Extensões permitidas (para validação)
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ==================== CONFIGURAÇÕES DE SESSÃO ====================

define('SESSION_NAME', 'COISABOA_SESSION');
define('SESSION_TIMEOUT', 3600);             // 1 hora em segundos
define('SESSION_REGENERATE', 300);           // Regenerar a cada 5 minutos

// ==================== CONFIGURAÇÕES DE SEGURANÇA ====================

define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);           // Para futuras versões

// ==================== CONFIGURAÇÕES DE NEGÓCIO ====================

// Paginação
define('ITEMS_PER_PAGE', 30);
define('MAX_ITEMS_PER_PAGE', 100);

// Estoque
define('ESTOQUE_MINIMO_ALERTA', 5);         // Alerta quando estoque < 5
define('ESTOQUE_ZERADO_ALERTA', 0);         // Alerta quando estoque = 0

// Formas de pagamento
define('FORMAS_PAGAMENTO', [
    'dinheiro' => 'Dinheiro',
    'cartao_credito' => 'Cartão de Crédito',
    'cartao_debito' => 'Cartão de Débito',
    'pix' => 'PIX',
    'transferencia' => 'Transferência',
    'boleto' => 'Boleto'
]);

// ==================== CONFIGURAÇÕES DE VISUALIZAÇÃO ====================

// Cores do sistema (para uso em CSS/relatórios)
define('COR_PRIMARIA', '#667eea');
define('COR_SECUNDARIA', '#764ba2');
define('COR_SUCESSO', '#10b981');
define('COR_ALERTA', '#f59e0b');
define('COR_ERRO', '#ef4444');
define('COR_INFO', '#3b82f6');

// Status do sistema
define('STATUS_ATIVO', 'ativo');
define('STATUS_INATIVO', 'inativo');
define('STATUS_PENDENTE', 'pendente');

// ==================== CONFIGURAÇÕES DE LOG ====================

define('LOG_ENABLED', true);
define('LOG_PATH', ROOT_PATH . '/logs');
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARN, ERROR

// ==================== DETECÇÃO DE AMBIENTE ====================

/**
 * Detecta se está em ambiente de desenvolvimento
 * @return bool
 */
function is_dev_environment() {
    return $_SERVER['SERVER_NAME'] == 'localhost' 
        || $_SERVER['SERVER_ADDR'] == '127.0.0.1'
        || strpos($_SERVER['SERVER_NAME'], '.local') !== false;
}

// Configurações específicas por ambiente
if (is_dev_environment()) {
    // Desenvolvimento
    define('ENVIRONMENT', 'development');
    define('DEBUG_MODE', true);
    define('DISPLAY_ERRORS', true);
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Produção
    define('ENVIRONMENT', 'production');
    define('DEBUG_MODE', false);
    define('DISPLAY_ERRORS', false);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// ==================== INICIALIZAÇÃO ====================

// Incluir funções básicas
require_once INCLUDES_PATH . '/functions.php';

// Timezone padrão
date_default_timezone_set('America/Sao_Paulo');

// Configuração de locale para formatações
setlocale(LC_MONETARY, 'pt_BR.UTF-8');

?>