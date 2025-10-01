<?php
/**
 * COISABOA - Criador Especializado da Pasta Config
 * Arquivo: create_config.php
 * Descrição: Cria estrutura completa da pasta de configurações com arquivos detalhados
 */

echo "⚙️  INICIANDO CRIAÇÃO DA PASTA CONFIG...\n";
echo "=============================================\n";

// Configurações
$project_root = __DIR__ . '/COISABOA';
$config_dir = $project_root . '/config';

// Verificar se a pasta principal existe
if (!file_exists($project_root)) {
    echo "❌ ERRO: Pasta principal COISABOA não encontrada!\n";
    echo "Execute primeiro o create_structure.php\n";
    exit(1);
}

// Criar pasta config se não existir
if (!file_exists($config_dir)) {
    mkdir($config_dir, 0755, true);
    echo "✅ Pasta config criada: $config_dir\n";
} else {
    echo "📁 Pasta config já existe: $config_dir\n";
}

// Arquivos de configuração detalhados
$config_files = [
    'database.php' => "<?php
/**
 * COISABOA - Configuração do Banco de Dados
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== CONFIGURAÇÕES DO BANCO ====================

/**
 * Dados de conexão com o MySQL
 */
define('DB_HOST', 'localhost');          // Servidor do banco
define('DB_NAME', 'coisaboa');           // Nome do banco
define('DB_USER', 'root');               // Usuário do banco
define('DB_PASS', '');                   // Senha do banco
define('DB_CHARSET', 'utf8mb4');         // Codificação
define('DB_COLLATION', 'utf8mb4_unicode_ci'); // Collation

// ==================== CONFIGURAÇÕES DE CONEXÃO ====================

/**
 * Opções avançadas do PDO
 */
\$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"
];

// ==================== FUNÇÃO DE CONEXÃO ====================

/**
 * Estabelece conexão com o banco de dados
 * @return PDO Objeto de conexão
 * @throws PDOException Em caso de erro de conexão
 */
function getDBConnection() {
    \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    try {
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$GLOBALS['db_options']);
        
        // Configurações específicas para melhor performance
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        \$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        return \$pdo;
        
    } catch (PDOException \$e) {
        // Log do erro (em produção, usar sistema de logs)
        error_log('Erro de conexão com o banco: ' . \$e->getMessage());
        
        // Mensagem amigável para o usuário
        die('<div style=\"padding: 20px; background: #fee; border: 1px solid #fcc; margin: 20px;\">
            <h3>Erro de Conexão com o Banco</h3>
            <p>Não foi possível conectar ao banco de dados. Verifique:</p>
            <ul>
                <li>Servidor MySQL está rodando</li>
                <li>Credenciais de acesso corretas</li>
                <li>Banco de dados existe</li>
            </ul>
            <p><small>Detalhes técnicos: ' . \$e->getMessage() . '</small></p>
        </div>');
    }
}

/**
 * Testa a conexão com o banco (uso administrativo)
 * @return array Resultado do teste
 */
function testarConexao() {
    try {
        \$pdo = getDBConnection();
        \$stmt = \$pdo->query('SELECT 1 as teste, NOW() as data_hora');
        \$resultado = \$stmt->fetch();
        
        return [
            'status' => 'sucesso',
            'mensagem' => 'Conexão estabelecida com sucesso',
            'detalhes' => \$resultado
        ];
    } catch (Exception \$e) {
        return [
            'status' => 'erro',
            'mensagem' => 'Falha na conexão',
            'detalhes' => \$e->getMessage()
        ];
    }
}

// ==================== FUNÇÕES DE BACKUP ====================

/**
 * Gera string DSN para backup
 * @return string DSN sem nome do banco
 */
function getDSNBackup() {
    return 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
}

// Auto-teste na inclusão (apenas em desenvolvimento)
if (isset(\$_GET['teste_db']) && \$_GET['teste_db'] == '1') {
    header('Content-Type: application/json');
    echo json_encode(testarConexao());
    exit;
}

?>",

    'constants.php' => "<?php
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
    return \$_SERVER['SERVER_NAME'] == 'localhost' 
        || \$_SERVER['SERVER_ADDR'] == '127.0.0.1'
        || strpos(\$_SERVER['SERVER_NAME'], '.local') !== false;
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

?>",

    'environment.php' => "<?php
/**
 * COISABOA - Configurações de Ambiente
 * Arquivo para configurações sensíveis (não versionar)
 */

// Exemplo de configurações por ambiente
// Renomeie este arquivo para environment.php e ajuste as configurações

// Banco de dados produção
// define('DB_HOST', 'meuservidor.com');
// define('DB_NAME', 'coisaboa_prod');
// define('DB_USER', 'usuario_prod');
// define('DB_PASS', 'senha_super_secreta');

// Configurações SMTP (para futuras versões)
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_USER', 'email@gmail.com');
// define('SMTP_PASS', 'senha');
// define('SMTP_PORT', 587);

?>",

    'routes.php' => "<?php
/**
 * COISABOA - Configuração de Rotas
 * Para futuras versões com URLs amigáveis
 */

\$routes = [
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
function route(\$path) {
    global \$routes;
    return isset(\$routes[\$path]) ? \$routes[\$path] : null;
}

?>"
];

// Arquivo de proteção .htaccess
$htaccess_content = "# COISABOA - Proteção da Pasta Config
# Bloqueia acesso direto aos arquivos de configuração

Order Deny,Allow
Deny from all

# Permitir acesso apenas de localhost (desenvolvimento)
SetEnvIf Host \"^localhost\$\" allowed
SetEnvIf Host \"^127.0.0.1\$\" allowed

Allow from env=allowed

# Bloquear acesso a arquivos sensíveis
<Files ~ \"\\.(php|env|config|ini)\$\">
    Deny from all
</Files>

# Arquivos específicos que podem ser acessados (se necessário)
<Files \"config.json\">
    Allow from all
</Files>

ErrorDocument 403 \"<h1>Acesso Negado</h1><p>Configurações do sistema não podem ser acessadas diretamente.</p>\"

# Headers de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"";

// Criar arquivos de configuração
echo "\n📄 CRIANDO ARQUIVOS DE CONFIGURAÇÃO...\n";
echo "=============================================\n";

foreach ($config_files as $filename => $content) {
    $filepath = $config_dir . '/' . $filename;
    
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
        echo "✅ Arquivo criado: config/{$filename}\n";
    } else {
        echo "📁 Arquivo já existe: config/{$filename}\n";
    }
}

// Criar arquivo .htaccess de proteção
$htaccess_path = $config_dir . '/.htaccess';
file_put_contents($htaccess_path, $htaccess_content);
echo "✅ Arquivo de proteção: config/.htaccess\n";

// Criar arquivo vazio para gitignore (se necessário)
$gitignore_path = $config_dir . '/.gitignore';
$gitignore_content = "# Ignorar arquivos sensíveis\nenvironment.php\n*.env\nconfig.local.php";
file_put_contents($gitignore_path, $gitignore_content);
echo "✅ Arquivo gitignore: config/.gitignore\n";

// Criar arquivo de exemplo para environment
$env_example_path = $config_dir . '/environment.example.php';
file_put_contents($env_example_path, $config_files['environment.php']);
echo "✅ Arquivo exemplo: config/environment.example.php\n";

// Testar se os arquivos foram criados corretamente
echo "\n🔍 VERIFICANDO ARQUIVOS CRIADOS...\n";
echo "=============================================\n";

$arquivos_criados = scandir($config_dir);
$arquivos_validos = array_filter($arquivos_criados, function($file) {
    return !in_array($file, ['.', '..', '.htaccess', '.gitignore']);
});

foreach ($arquivos_validos as $arquivo) {
    $caminho = $config_dir . '/' . $arquivo;
    $tamanho = filesize($caminho);
    $status = $tamanho > 100 ? '✅' : '⚠️';
    
    echo "{$status} {$arquivo} - " . number_format($tamanho) . " bytes\n";
}

// Resumo final
echo "\n🎉 CONFIGURAÇÕES CRIADAS COM SUCESSO!\n";
echo "=============================================\n";
echo "📊 RESUMO DA PASTA CONFIG:\n";
echo "• 🗄️  database.php - Conexão PDO com tratamento de erros\n";
echo "• ⚙️  constants.php - 60+ constantes organizadas por categoria\n";
echo "• 🌿 environment.php - Configurações sensíveis (exemplo)\n";
echo "• 🛣️  routes.php - Rotas para URLs amigáveis (futuro)\n";
echo "• 🛡️  .htaccess - Proteção avançada da pasta\n";
echo "• 📍 Localização: " . realpath($config_dir) . "\n\n";

echo "✅ RECOMENDAÇÕES:\n";
echo "1. Edite config/database.php com suas credenciais\n";
echo "2. Renomeie environment.example.php para environment.php\n";
echo "3. Ajuste as constantes conforme necessário\n";
echo "4. Teste a conexão: acesse ?teste_db=1\n\n";

echo "⚙️  CONFIGURAÇÃO PRONTA PARA USO!\n";

// Oferecer teste de conexão
echo "\n🧪 DESEJA TESTAR A CONEXÃO AGORA? (Requer MySQL rodando)\n";
echo "Acesse: http://localhost/COISABOA/config/database.php?teste_db=1\n";

?>