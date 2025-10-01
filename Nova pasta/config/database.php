<?php
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
$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// ==================== FUNÇÃO DE CONEXÃO ====================

/**
 * Estabelece conexão com o banco de dados
 * @return PDO Objeto de conexão
 * @throws PDOException Em caso de erro de conexão
 */
function getDBConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $GLOBALS['db_options']);
        
        // Configurações específicas para melhor performance
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log do erro (em produção, usar sistema de logs)
        error_log('Erro de conexão com o banco: ' . $e->getMessage());
        
        // Mensagem amigável para o usuário
        die('<div style="padding: 20px; background: #fee; border: 1px solid #fcc; margin: 20px;">
            <h3>Erro de Conexão com o Banco</h3>
            <p>Não foi possível conectar ao banco de dados. Verifique:</p>
            <ul>
                <li>Servidor MySQL está rodando</li>
                <li>Credenciais de acesso corretas</li>
                <li>Banco de dados existe</li>
            </ul>
            <p><small>Detalhes técnicos: ' . $e->getMessage() . '</small></p>
        </div>');
    }
}

/**
 * Testa a conexão com o banco (uso administrativo)
 * @return array Resultado do teste
 */
function testarConexao() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query('SELECT 1 as teste, NOW() as data_hora');
        $resultado = $stmt->fetch();
        
        return [
            'status' => 'sucesso',
            'mensagem' => 'Conexão estabelecida com sucesso',
            'detalhes' => $resultado
        ];
    } catch (Exception $e) {
        return [
            'status' => 'erro',
            'mensagem' => 'Falha na conexão',
            'detalhes' => $e->getMessage()
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
if (isset($_GET['teste_db']) && $_GET['teste_db'] == '1') {
    header('Content-Type: application/json');
    echo json_encode(testarConexao());
    exit;
}

?>