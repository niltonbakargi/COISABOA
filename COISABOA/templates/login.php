<?php
/**
 * COISABOA - Template de Login (Para versões futuras)
 * @version 1.0.0
 */

$page_title = 'Login - COISABOA';
$body_class = 'login-page';
?>

<?php include 'header.php'; ?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">📱</div>
                <h1 class="logo-text">COISABOA</h1>
            </div>
            <p class="login-subtitle">Acesse sua conta</p>
        </div>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-full">
                🔐 Entrar
            </button>
        </form>
        
        <div class="login-footer">
            <p>Sistema COISABOA v<?= SISTEMA_VERSION ?></p>
        </div>
    </div>
</div>

<style>
.login-page {
    background: var(--gradiente-principal);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-container {
    padding: 2rem;
    width: 100%;
    max-width: 400px;
}

.login-card {
    background: white;
    padding: 2rem;
    border-radius: var(--raio-borda-lg);
    box-shadow: var(--sombra-lg);
    text-align: center;
}

.login-header {
    margin-bottom: 2rem;
}

.login-subtitle {
    color: var(--cor-cinza-600);
    margin-top: 0.5rem;
}

.login-form {
    margin-bottom: 2rem;
}

.login-footer {
    border-top: 1px solid var(--cor-cinza-200);
    padding-top: 1rem;
    color: var(--cor-cinza-500);
    font-size: var(--texto-sm);
}
</style>

<?php include 'footer.php'; ?>