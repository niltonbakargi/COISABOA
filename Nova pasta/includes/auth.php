<?php
/**
 * COISABOA - Sistema de Autenticação e Sessão
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== CONTROLE DE SESSÃO ====================

/**
 * Inicia sessão segura
 */
function iniciarSessaoSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações seguras
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerar ID periodicamente
        if (!isset($_SESSION['ultima_regeneracao'])) {
            $_SESSION['ultima_regeneracao'] = time();
        } else if (time() - $_SESSION['ultima_regeneracao'] > SESSION_REGENERATE) {
            session_regenerate_id(true);
            $_SESSION['ultima_regeneracao'] = time();
        }
    }
}

/**
 * Verifica timeout da sessão
 */
function verificarTimeoutSessao() {
    if (isset($_SESSION['ultima_atividade']) && 
        (time() - $_SESSION['ultima_atividade'] > SESSION_TIMEOUT)) {
        destruirSessao();
        return false;
    }
    
    $_SESSION['ultima_atividade'] = time();
    return true;
}

/**
 * Destrói sessão completamente
 */
function destruirSessao() {
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    
    session_destroy();
}

// ==================== AUTENTICAÇÃO (FUTURAS VERSÕES) ====================

/**
 * Verifica se usuário está logado
 * @return bool Está logado
 */
function usuarioLogado() {
    iniciarSessaoSegura();
    verificarTimeoutSessao();
    
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Exige autenticação para acessar página
 */
function requererAutenticacao() {
    if (!usuarioLogado()) {
        $_SESSION['url_redirect'] = $_SERVER['REQUEST_URI'];
        redirect('login.php', 'Faça login para acessar esta página.', 'error');
    }
}

/**
 * Realiza login do usuário
 * @param int $usuarioId ID do usuário
 * @param string $usuarioNome Nome do usuário
 */
function fazerLogin($usuarioId, $usuarioNome) {
    iniciarSessaoSegura();
    
    $_SESSION['usuario_id'] = $usuarioId;
    $_SESSION['usuario_nome'] = $usuarioNome;
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['ultima_atividade'] = time();
    
    // Log de login
    logAtividade('login', 'Usuário ' . $usuarioNome . ' fez login');
}

/**
 * Realiza logout
 */
function fazerLogout() {
    if (usuarioLogado()) {
        $usuarioNome = $_SESSION['usuario_nome'];
        logAtividade('logout', 'Usuário ' . $usuarioNome . ' fez logout');
    }
    
    destruirSessao();
}

// ==================== CONTROLE DE ACESSO ====================

/**
 * Verifica se usuário tem permissão
 * @param string $permissao Permissão requerida
 * @return bool Tem permissão
 */
function temPermissao($permissao) {
    if (!usuarioLogado()) return false;
    
    // Permissões padrão (para futuras versões)
    $permissoes = isset($_SESSION['permissoes']) ? $_SESSION['permissoes'] : ['visualizar', 'editar'];
    
    return in_array($permissao, $permissoes);
}

/**
 * Exige permissão específica
 * @param string $permissao Permissão requerida
 */
function requererPermissao($permissao) {
    if (!temPermissao($permissao)) {
        redirect('index.php', 'Você não tem permissão para acessar esta função.', 'error');
    }
}

// ==================== LOG DE ATIVIDADES ====================

/**
 * Registra atividade do usuário
 * @param string $tipo Tipo da atividade
 * @param string $descricao Descrição detalhada
 */
function logAtividade($tipo, $descricao) {
    if (!LOG_ENABLED) return;
    
    $logDir = ROOT_PATH . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/atividades_' . date('Y-m') . '.log';
    
    $usuarioId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'anonimo';
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'], 0, 100);
    
    $logEntry = date('Y-m-d H:i:s') . " | ";
    $logEntry .= $usuarioId . " | ";
    $logEntry .= $ip . " | ";
    $logEntry .= $tipo . " | ";
    $logEntry .= $descricao . " | ";
    $logEntry .= $userAgent . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// ==================== INICIALIZAÇÃO AUTOMÁTICA ====================

// Iniciar sessão em todas as páginas que incluírem este arquivo
iniciarSessaoSegura();

?>