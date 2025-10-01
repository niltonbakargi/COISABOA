<?php
/**
 * COISABOA - Criador da Estrutura do Sistema
 * Arquivo: create_structure.php
 * Descrição: Cria toda a estrutura de pastas e arquivos básicos do sistema
 */

echo "🚀 INICIANDO CRIAÇÃO DA ESTRUTURA COISABOA...\n";
echo "=============================================\n";

// Configurações
$project_root = __DIR__ . '/COISABOA';
$structure = [
    'config/' => [
        'database.php',
        'constants.php'
    ],
    'uploads/' => [
        'vendors/',
        'temp/'
    ],
    'includes/' => [
        'auth.php',
        'functions.php',
        'validations.php'
    ],
    'modules/' => [
        'purchases/',
        'sales/',
        'inventory/'
    ],
    'assets/' => [
        'css/',
        'js/',
        'images/',
        'icons/'
    ]
];

// Arquivos raiz
$root_files = [
    'index.php',
    'install.php',
    'backup.php',
    '.htaccess',
    'README.md'
];

// Função para criar diretórios
function createDirectory($path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✅ Diretório criado: $path\n";
            // Criar arquivo .htaccess para segurança
            $htaccess_content = "Deny from all";
            file_put_contents($path . '/.htaccess', $htaccess_content);
        } else {
            echo "❌ Erro ao criar diretório: $path\n";
            return false;
        }
    } else {
        echo "📁 Diretório já existe: $path\n";
    }
    return true;
}

// Função para criar arquivo
function createFile($path, $content = '') {
    if (!file_exists($path)) {
        if (file_put_contents($path, $content)) {
            echo "✅ Arquivo criado: $path\n";
        } else {
            echo "❌ Erro ao criar arquivo: $path\n";
            return false;
        }
    } else {
        echo "📄 Arquivo já existe: $path\n";
    }
    return true;
}

// Criar pasta principal
echo "\n📦 CRIANDO PASTA PRINCIPAL...\n";
if (!file_exists($project_root)) {
    mkdir($project_root, 0755);
    echo "✅ Pasta principal criada: $project_root\n";
} else {
    echo "📁 Pasta principal já existe: $project_root\n";
}

// Criar estrutura de pastas
echo "\n📁 CRIANDO ESTRUTURA DE PASTAS...\n";
foreach ($structure as $main_dir => $sub_items) {
    $main_path = $project_root . '/' . $main_dir;
    
    if (createDirectory($main_path)) {
        // Criar subpastas e arquivos
        foreach ($sub_items as $item) {
            $item_path = $main_path . '/' . $item;
            
            if (strpos($item, '/') !== false || strpos($item, '.') === false) {
                // É uma subpasta
                createDirectory($item_path);
            } else {
                // É um arquivo
                createFile($item_path, "<?php\n// COISABOA - " . ucfirst(str_replace('.php', '', $item)) . "\n?>");
            }
        }
    }
}

// Criar arquivos na raiz
echo "\n📄 CRIANDO ARQUIVOS PRINCIPAIS...\n";
foreach ($root_files as $file) {
    $file_path = $project_root . '/' . $file;
    
    switch ($file) {
        case 'index.php':
            $content = "<?php\n/**\n * COISABOA - Dashboard Principal\n * Página inicial do sistema\n */\n\necho 'Bem-vindo ao COISABOA!';\n?>";
            break;
            
        case 'install.php':
            $content = "<?php\n/**\n * COISABOA - Instalador do Sistema\n * Configura banco de dados e tabelas\n */\n\necho 'Instalador COISABOA';\n?>";
            break;
            
        case 'backup.php':
            $content = "<?php\n/**\n * COISABOA - Ferramenta de Backup\n * Backup completo do sistema\n */\n\necho 'Ferramenta de Backup';\n?>";
            break;
            
        case '.htaccess':
            $content = "# COISABOA - Configurações Apache\nRewriteEngine On\nOptions -Indexes\n\n# Segurança\n<Files ~ \"\\.(env|config|log)$\">\n    Deny from all\n</Files>";
            break;
            
        case 'README.md':
            $content = "# COISABOA - Sistema de Controle Comercial\n\nSistema completo para controle de compras, vendas e estoque.\n\n## Instalação\n1. Execute o install.php\n2. Configure o banco de dados\n3. Acesse o sistema\n\n## Estrutura\nVer documentação completa para detalhes.";
            break;
            
        default:
            $content = "<?php\n// COISABOA - " . ucfirst(str_replace('.php', '', $file)) . "\n?>";
    }
    
    createFile($file_path, $content);
}

// Criar arquivos de configuração com conteúdo básico
echo "\n⚙️  CRIANDO ARQUIVOS DE CONFIGURAÇÃO...\n";

// config/database.php
$db_content = "<?php\n/**\n * COISABOA - Configuração do Banco de Dados\n */\n\ndefine('DB_HOST', 'localhost');\ndefine('DB_NAME', 'coisaboa');\ndefine('DB_USER', 'root');\ndefine('DB_PASS', '');\ndefine('DB_CHARSET', 'utf8mb4');\n\n/**\n * Conexão com o Banco de Dados\n */\nfunction getDBConnection() {\n    \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;\n    \n    try {\n        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS);\n        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n        return \$pdo;\n    } catch (PDOException \$e) {\n        die('Erro de conexão: ' . \$e->getMessage());\n    }\n}\n?>";
createFile($project_root . '/config/database.php', $db_content);

// config/constants.php
$constants_content = "<?php\n/**\n * COISABOA - Constantes do Sistema\n */\n\n// Versão do Sistema\ndefine('SISTEMA_VERSION', '1.0.0');\ndefine('SISTEMA_NOME', 'COISABOA');\n\n// Paths\ndefine('ROOT_PATH', __DIR__ . '/..');\ndefine('UPLOAD_PATH', ROOT_PATH . '/uploads');\ndefine('ASSETS_PATH', ROOT_PATH . '/assets');\n\n// Configurações de Upload\ndefine('MAX_FILE_SIZE', 10485760); // 10MB\ndefine('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);\n\n// Session\ndefine('SESSION_TIMEOUT', 3600); // 1 hora\n\n// Paginação\ndefine('ITEMS_PER_PAGE', 50);\n\n// Cores do Sistema (CSS)\ndefine('COR_PRIMARIA', '#667eea');\ndefine('COR_SECUNDARIA', '#764ba2');\n?>";
createFile($project_root . '/config/constants.php', $constants_content);

// Criar arquivos includes básicos
echo "\n📚 CRIANDO ARQUIVOS INCLUDE...\n";

// includes/functions.php
$functions_content = "<?php\n/**\n * COISABOA - Funções Utilitárias\n */\n\n/**\n * Formata valor monetário\n */\nfunction formatarMoeda(\$valor) {\n    return 'R$ ' . number_format(\$valor, 2, ',', '.');\n}\n\n/**\n * Formata data para exibição\n */\nfunction formatarData(\$data) {\n    return date('d/m/Y H:i', strtotime(\$data));\n}\n\n/**\n * Sanitiza string para segurança\n */\nfunction sanitizar(\$dados) {\n    if (is_array(\$dados)) {\n        return array_map('sanitizar', \$dados);\n    }\n    return htmlspecialchars(trim(\$dados), ENT_QUOTES, 'UTF-8');\n}\n\n/**\n * Redirecionamento com mensagem\n */\nfunction redirect(\$url, \$mensagem = null) {\n    if (\$mensagem) {\n        \$_SESSION['mensagem'] = \$mensagem;\n    }\n    header('Location: ' . \$url);\n    exit;\n}\n?>";
createFile($project_root . '/includes/functions.php', $functions_content);

// includes/validations.php
$validations_content = "<?php\n/**\n * COISABOA - Validações de Dados\n */\n\n/**\n * Valida se é um email válido\n */\nfunction validarEmail(\$email) {\n    return filter_var(\$email, FILTER_VALIDATE_EMAIL) !== false;\n}\n\n/**\n * Valida se é um número inteiro positivo\n */\nfunction validarInteiroPositivo(\$numero) {\n    return filter_var(\$numero, FILTER_VALIDATE_INT) !== false && \$numero > 0;\n}\n\n/**\n * Valida valor monetário\n */\nfunction validarMonetario(\$valor) {\n    return is_numeric(\$valor) && \$valor >= 0;\n}\n\n/**\n * Valida arquivo de imagem\n */\nfunction validarImagem(\$arquivo) {\n    if (!isset(\$arquivo['error']) || \$arquivo['error'] !== UPLOAD_ERR_OK) {\n        return false;\n    }\n    \n    \$extensao = strtolower(pathinfo(\$arquivo['name'], PATHINFO_EXTENSION));\n    \$tipoPermitido = in_array(\$extensao, ALLOWED_IMAGE_TYPES);\n    \$tamanhoPermitido = \$arquivo['size'] <= MAX_FILE_SIZE;\n    \n    return \$tipoPermitido && \$tamanhoPermitido;\n}\n?>";
createFile($project_root . '/includes/validations.php', $validations_content);

// includes/auth.php
$auth_content = "<?php\n/**\n * COISABOA - Controle de Autenticação\n * (Para versões futuras com multi-usuário)\n */\n\n/**\n * Inicia sessão segura\n */\nfunction iniciarSessao() {\n    if (session_status() === PHP_SESSION_NONE) {\n        session_start();\n        session_regenerate_id(true);\n    }\n}\n\n/**\n * Verifica se usuário está logado\n */\nfunction usuarioLogado() {\n    iniciarSessao();\n    return isset(\$_SESSION['usuario_id']);\n}\n\n/**\n * Protege página requerendo login\n */\nfunction requererLogin() {\n    if (!usuarioLogado()) {\n        redirect('login.php', 'Faça login para acessar esta página.');\n    }\n}\n?>";
createFile($project_root . '/includes/auth.php', $auth_content);

// Criar .htaccess para proteção das pastas
echo "\n🛡️  CONFIGURANDO SEGURANÇA...\n";

$protected_folders = ['config', 'includes', 'modules'];
foreach ($protected_folders as $folder) {
    $htaccess_path = $project_root . '/' . $folder . '/.htaccess';
    $htaccess_content = "Deny from all\n\n# COISABOA - Proteção de pasta " . ucfirst($folder);
    createFile($htaccess_path, $htaccess_content);
}

// Criar arquivo index.html em pastas vazias para evitar listagem
echo "\n📝 CRIANDO ARQUIVOS DE PROTEÇÃO...\n";

$empty_folders = ['uploads/vendors', 'uploads/temp', 'assets/images', 'assets/icons'];
foreach ($empty_folders as $folder) {
    $index_path = $project_root . '/' . $folder . '/index.html';
    $index_content = "<!DOCTYPE html>\n<html>\n<head>\n    <title>Acesso Negado</title>\n</head>\n<body>\n    <h1>Acesso Negado</h1>\n    <p>Esta pasta não pode ser listada.</p>\n</body>\n</html>";
    createFile($index_path, $index_content);
}

// Resumo final
echo "\n🎉 ESTRUTURA CRIADA COM SUCESSO!\n";
echo "=============================================\n";
echo "📊 RESUMO DA ESTRUTURA:\n";
echo "• 📁 Pastas criadas: " . count($structure) . " principais + subpastas\n";
echo "• 📄 Arquivos base: " . count($root_files) . " na raiz\n";
echo "• ⚙️  Configurações: Database e Constants\n";
echo "• 📚 Includes: Functions, Validations, Auth\n";
echo "• 🛡️  Segurança: .htaccess em pastas protegidas\n";
echo "• 📍 Localização: " . realpath($project_root) . "\n\n";

echo "✅ PRÓXIMOS PASSOS:\n";
echo "1. Configure o banco de dados em config/database.php\n";
echo "2. Execute o instalador: php install.php\n";
echo "3. Acesse o sistema via navegador\n\n";

echo "🚀 COISABOA PRONTO PARA USO!\n";
?>