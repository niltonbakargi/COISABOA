<?php
/**
 * COISABOA - Sistema de Login
 * Arquivo: login.php
 */

require_once __DIR__ . '/includes/init.php';

// Se já estiver logado, redirecionar para o dashboard
if (isset($_SESSION['usuario_id'])) {
    redirect('./', 'Você já está logado!', 'info');
}

$page_title = 'Login - COISABOA';
$current_page = 'login';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    $errors = [];
    
    // Validações
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido';
    }
    
    if (empty($senha)) {
        $errors['senha'] = 'Senha é obrigatória';
    }
    
    if (empty($errors)) {
        try {
            // Buscar usuário no banco
            $usuario = dbFind(
                "SELECT id, nome, email, senha, nivel, ativo FROM usuarios WHERE email = ? AND ativo = TRUE",
                [$email]
            );
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_nivel'] = $usuario['nivel'];
                $_SESSION['login_time'] = time();
                
                // Atualizar último login
                dbUpdate(
                    'usuarios',
                    ['ultimo_login' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$usuario['id']]
                );
                
                // Log da atividade
                logAtividade('login', 'Usuário ' . $usuario['nome'] . ' fez login');
                
                redirect('./', 'Login realizado com sucesso! 👋', 'success');
                
            } else {
                $errors['geral'] = 'Email ou senha incorretos';
            }
            
        } catch (Exception $e) {
            $errors['geral'] = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}

// Incluir cabeçalho sem menu
include __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="login-container">
        <div class="login-card">
            <!-- Cabeçalho do Login -->
            <div class="login-header">
                <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                    <div class="logo-icon">📱</div>
                    <h1 class="logo-text">COISABOA</h1>
                </div>
                <h2 style="text-align: center; margin-bottom: 0.5rem;">Bem-vindo de volta!</h2>
                <p style="text-align: center; color: var(--cor-cinza-600); margin-bottom: 2rem;">
                    Faça login para acessar o sistema
                </p>
            </div>
            
            <!-- Mensagens de Erro -->
            <?php if (!empty($errors['geral'])): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    ❌ <?= $errors['geral'] ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário de Login -->
            <form method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">📧 Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control <?= isset($errors['email']) ? 'error' : '' ?>" 
                           value="<?= $_POST['email'] ?? '' ?>"
                           placeholder="seu@email.com"
                           required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="validation-error"><?= $errors['email'] ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="senha" class="form-label">🔒 Senha</label>
                    <input type="password" 
                           id="senha" 
                           name="senha" 
                           class="form-control <?= isset($errors['senha']) ? 'error' : '' ?>" 
                           placeholder="Sua senha"
                           required>
                    <?php if (isset($errors['senha'])): ?>
                        <div class="validation-error"><?= $errors['senha'] ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-full" style="margin-top: 1rem;">
                    🚀 Entrar no Sistema
                </button>
            </form>
            
            <!-- Informações de Demo -->
            <div class="login-demo" style="margin-top: 2rem; padding: 1.5rem; background: var(--cor-cinza-100); border-radius: var(--raio-borda);">
                <h4 style="margin-bottom: 1rem; color: var(--cor-cinza-700);">👤 Dados para Teste:</h4>
                <div style="display: grid; gap: 0.5rem;">
                    <div><strong>Email:</strong> admin@coisaboa.com</div>
                    <div><strong>Senha:</strong> 123456</div>
                </div>
            </div>
            
            <!-- Rodapé do Login -->
            <div class="login-footer" style="margin-top: 2rem; text-align: center;">
                <p style="color: var(--cor-cinza-500); font-size: 0.9rem;">
                    Sistema COISABOA v<?= SISTEMA_VERSION ?><br>
                    <small>Controle de Compras, Vendas e Estoque</small>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
    background: white;
    padding: 3rem;
    border-radius: var(--raio-borda-lg);
    box-shadow: var(--sombra-lg);
    width: 100%;
    max-width: 400px;
}

.login-header {
    margin-bottom: 2rem;
}

.login-form {
    margin-bottom: 2rem;
}

.login-demo {
    border-left: 4px solid var(--cor-info);
}

/* Ajustes para a página de login */
body {
    background: transparent !important;
}

.header, .footer {
    display: none;
}

.main-content {
    padding: 0 !important;
    min-height: 100vh !important;
}
</style>

<?php
// Incluir rodapé sem conteúdo extra
include __DIR__ . '/templates/footer.php';
?>