<?php
/**
 * COISABOA - Sistema de Autenticação e Sessão
 * @version 1.0.1
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
        
        // Só usa secure em HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerar ID periodicamente (a cada 30 minutos)
        if (!isset($_SESSION['ultima_regeneracao'])) {
            $_SESSION['ultima_regeneracao'] = time();
        } else if (time() - $_SESSION['ultima_regeneracao'] > 1800) { // 30 minutos
            session_regenerate_id(true);
            $_SESSION['ultima_regeneracao'] = time();
        }
    }
}

/**
 * Verifica timeout da sessão (8 horas)
 */
function verificarTimeoutSessao() {
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
        fazerLogout();
        return false;
    }
    
    // Atualizar tempo de atividade
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
    
    @session_destroy();
}

// ==================== AUTENTICAÇÃO ====================

/**
 * Verifica se usuário está logado
 * @return bool Está logado
 */
function usuarioLogado() {
    iniciarSessaoSegura();
    
    // Verificar se existe sessão de usuário
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        return false;
    }
    
    // Verificar timeout
    if (!verificarTimeoutSessao()) {
        return false;
    }
    
    // Verificar se IP e User Agent são os mesmos (proteção básica)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        fazerLogout();
        return false;
    }
    
    return true;
}

/**
 * Exige autenticação para acessar página
 */
function requererLogin() {
    if (!usuarioLogado()) {
        // Salvar URL atual para redirecionamento após login
        $_SESSION['url_redirect'] = $_SERVER['REQUEST_URI'];
        redirect('login.php', 'Faça login para acessar esta página.', 'error');
    }
}

/**
 * Realiza login do usuário
 * @param int $usuarioId ID do usuário
 * @param string $usuarioNome Nome do usuário
 * @param string $usuarioEmail Email do usuário
 * @param string $usuarioNivel Nível do usuário
 */
function fazerLogin($usuarioId, $usuarioNome, $usuarioEmail, $usuarioNivel = 'usuario') {
    iniciarSessaoSegura();
    
    // Limpar sessão anterior
    $_SESSION = [];
    
    // Criar nova sessão
    $_SESSION['usuario_id'] = $usuarioId;
    $_SESSION['usuario_nome'] = $usuarioNome;
    $_SESSION['usuario_email'] = $usuarioEmail;
    $_SESSION['usuario_nivel'] = $usuarioNivel;
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['login_time'] = time();
    $_SESSION['ultima_atividade'] = time();
    $_SESSION['ultima_regeneracao'] = time();
    
    // Log de login
    logAtividade('login', "Usuário {$usuarioNome} ({$usuarioEmail}) fez login");
}

/**
 * Realiza logout
 */
function fazerLogout() {
    if (isset($_SESSION['usuario_nome'])) {
        $usuarioNome = $_SESSION['usuario_nome'];
        $usuarioEmail = $_SESSION['usuario_email'] ?? 'N/A';
        logAtividade('logout', "Usuário {$usuarioNome} ({$usuarioEmail}) fez logout");
    }
    
    destruirSessao();
}

// ==================== CONTROLE DE ACESSO ====================

/**
 * Verifica se é administrador
 * @return bool É administrador
 */
function isAdmin() {
    return usuarioLogado() && isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
}

/**
 * Verifica se usuário tem permissão
 * @param string $permissao Permissão requerida
 * @return bool Tem permissão
 */
function temPermissao($permissao) {
    if (!usuarioLogado()) return false;
    
    // Admin tem todas as permissões
    if (isAdmin()) {
        return true;
    }
    
    // Permissões baseadas no nível do usuário
    $permissoes = [
        'admin' => ['visualizar', 'editar', 'excluir', 'configurar', 'relatorios'],
        'usuario' => ['visualizar', 'editar']
    ];
    
    $nivel = $_SESSION['usuario_nivel'] ?? 'usuario';
    
    return isset($permissoes[$nivel]) && in_array($permissao, $permissoes[$nivel]);
}

/**
 * Exige permissão específica
 * @param string $permissao Permissão requerida
 */
function requererPermissao($permissao) {
    requererLogin();
    
    if (!temPermissao($permissao)) {
        redirect('index.php', 'Você não tem permissão para acessar esta função.', 'error');
    }
}

/**
 * Exige privilégios de administrador
 */
function requererAdmin() {
    requererLogin();
    
    if (!isAdmin()) {
        redirect('index.php', 'Acesso negado. Permissão de administrador necessária.', 'error');
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
    $usuarioNome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'N/A';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200);
    
    $logEntry = date('Y-m-d H:i:s') . " | ";
    $logEntry .= "UID:{$usuarioId} | ";
    $logEntry .= "User:{$usuarioNome} | ";
    $logEntry .= "IP:{$ip} | ";
    $logEntry .= "{$tipo} | ";
    $logEntry .= "{$descricao} | ";
    $logEntry .= "UA:{$userAgent}\n";
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Obtém informações do usuário logado
 * @return array|null Dados do usuário ou null se não logado
 */
function getUsuarioLogado() {
    if (!usuarioLogado()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['usuario_id'],
        'nome' => $_SESSION['usuario_nome'],
        'email' => $_SESSION['usuario_email'],
        'nivel' => $_SESSION['usuario_nivel'],
        'login_time' => $_SESSION['login_time'],
        'ip' => $_SESSION['ip_address']
    ];
}

/**
 * Atualiza dados do usuário na sessão
 * @param string $nome Novo nome
 * @param string $email Novo email
 */
function atualizarUsuarioSessao($nome = null, $email = null) {
    if (!usuarioLogado()) return;
    
    if ($nome !== null) {
        $_SESSION['usuario_nome'] = $nome;
    }
    
    if ($email !== null) {
        $_SESSION['usuario_email'] = $email;
    }
}

// ==================== REDIRECIONAMENTO PÓS-LOGIN ====================

/**
 * Obtém URL para redirecionamento pós-login
 * @return string URL para redirecionar
 */
function getRedirectUrl() {
    $default_url = 'index.php';
    $redirect_url = $_SESSION['url_redirect'] ?? $default_url;
    
    // Limpar URL de redirecionamento após uso
    unset($_SESSION['url_redirect']);
    
    // Prevenir redirecionamento para login/logout
    if (strpos($redirect_url, 'login.php') !== false || 
        strpos($redirect_url, 'logout.php') !== false) {
        return $default_url;
    }
    
    return $redirect_url;
}

// ==================== INICIALIZAÇÃO AUTOMÁTICA ====================

// Iniciar sessão em todas as páginas que incluírem este arquivo
iniciarSessaoSegura();

// Verificar timeout automaticamente
if (usuarioLogado()) {
    // Atualizar atividade
    $_SESSION['ultima_atividade'] = time();
}

?>