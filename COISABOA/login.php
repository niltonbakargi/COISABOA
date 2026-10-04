<?php
/**
 * COISABOA - Sistema de Gestão
 * Página de Login
 */

// Verificar se o sistema está instalado
if (!file_exists('config/database.php')) {
    header('Location: install.php');
    exit;
}

// Iniciar sessão
session_start();

// Incluir configuração do banco
require_once 'config/database.php';

// Processar login
$erro = '';
$sucesso = '';

if ($_POST['action'] ?? '' === 'login') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email']
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = "Email ou senha incorretos!";
        }
    } catch (PDOException $e) {
        error_log("Erro de login: " . $e->getMessage());
        $erro = "Erro interno. Tente novamente mais tarde.";
    }
}

// Processar cadastro de novo usuário
if ($_POST['action'] ?? '' === 'cadastrar') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    try {
        $pdo = getDB();
        
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $erro = "Este email já está cadastrado!";
        } else {
            // Inserir novo usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $email, $senha_hash]);
            
            $sucesso = "Usuário cadastrado com sucesso! Faça login.";
        }
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar usuário: " . $e->getMessage());
        $erro = "Erro interno. Tente novamente mais tarde.";
    }
}

// Verificar se já está logado
if ($_SESSION['usuario'] ?? false) {
    header('Location: dashboard.php');
    exit;
}

// Determinar se está no modo cadastro
$modo_cadastro = $_GET['cadastro'] ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - COISABOA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .login-content {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-success {
            background: #38a169;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
        }
        
        .alert-success {
            background: #c6f6d5;
            border: 1px solid #9ae6b4;
            color: #276749;
        }
        
        .demo-credentials {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 25px;
            text-align: center;
        }
        
        .demo-credentials h4 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .demo-credentials p {
            color: #856404;
            font-size: 0.85rem;
            margin: 3px 0;
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
            margin: 0 10px;
        }
        
        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .logo {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .modo-toggle {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modo-toggle a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .modo-toggle a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">📱</div>
            <h1>COISABOA</h1>
            <p>Sistema de Gestão Pessoal</p>
        </div>
        
        <div class="login-content">
            <?php if ($erro): ?>
                <div class="alert alert-error">
                    ❌ <?= $erro ?>
                </div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    ✅ <?= $sucesso ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$modo_cadastro): ?>
                <!-- MODO LOGIN -->
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label class="form-label">📧 Email</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="seu@email.com" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? 'admin@coisaboa.com') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🔒 Senha</label>
                        <input type="password" name="senha" class="form-control"
                               placeholder="Sua senha" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        🚀 Entrar no Sistema
                    </button>
                    
                    <a href="?cadastro=1" class="btn btn-success">
                        📝 Cadastrar Novo Usuário
                    </a>
                </form>
                
                <div class="demo-credentials">
                    <h4>👤 Dados para Teste</h4>
                    <p><strong>Email:</strong> admin@coisaboa.com</p>
                    <p><strong>Senha:</strong> 123456</p>
                </div>
                
            <?php else: ?>
                <!-- MODO CADASTRO -->
                <div class="modo-toggle">
                    <h3>📝 Cadastrar Novo Usuário</h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="cadastrar">
                    
                    <div class="form-group">
                        <label class="form-label">👤 Nome Completo</label>
                        <input type="text" name="nome" class="form-control" 
                               placeholder="Seu nome completo" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📧 Email</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="seu@email.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🔒 Senha</label>
                        <input type="password" name="senha" class="form-control" 
                               placeholder="Crie uma senha" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        ✅ Cadastrar Usuário
                    </button>
                    
                    <a href="?" class="btn btn-secondary">
                        ↩️ Voltar para Login
                    </a>
                </form>
            <?php endif; ?>
            
            <div class="links">
                <a href="install.php?action=reinstalar" 
                   onclick="return confirm('Isso reinstalará o sistema. Continuar?')">
                   🔧 Reinstalar Sistema
                </a>
            </div>
        </div>
    </div>

    <script>
        // Efeito de foco automático no campo email
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.querySelector('input[name="email"]');
            if (emailField) {
                emailField.focus();
            }
        });

        // Animação suave ao focar nos campos
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>