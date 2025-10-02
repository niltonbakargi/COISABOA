<?php
/**
 * COISABOA - Dashboard Simplificado
 */

// Verificar se está logado
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - COISABOA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #6b7280;
            --border: #e5e7eb;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: var(--primary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-welcome {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-role {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .btn {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        
        .btn-logout {
            background: var(--danger);
        }
        
        .btn-logout:hover {
            background: #dc2626;
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }
        
        /* Welcome Message */
        .welcome-message {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .welcome-message h2 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .welcome-message p {
            color: var(--gray);
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        /* Main Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
        }
        
        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--card-color);
        }
        
        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .card-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .card-badge {
            background: var(--card-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Card Colors */
        .card-comprar { --card-color: var(--secondary); }
        .card-vender { --card-color: var(--primary); }
        .card-relatorios { --card-color: var(--info); }
        .card-gerenciar { --card-color: #8b5cf6; }
        .card-backup { --card-color: var(--warning); }
        
        /* Footer */
        .footer {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-top: 40px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .user-welcome {
                text-align: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-message {
                padding: 20px;
            }
            
            .welcome-message h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dashboard-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-boxes"></i>
                COISABOA - Sistema de Gestão
            </h1>
            <div class="user-info">
                <div class="user-welcome">
                    <div class="user-name">👋 Olá, <?= htmlspecialchars($_SESSION['usuario']['nome']) ?></div>
                    <div class="user-role">Administrador do Sistema</div>
                </div>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </div>
        </div>
        
        <!-- Welcome Message -->
        <div class="welcome-message">
            <h2>Bem-vindo ao COISABOA! 🎉</h2>
            <p>Seu sistema completo para gestão de compras, vendas e estoque</p>
        </div>
        
        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <a href="comprar.php" class="dashboard-card card-comprar">
                <div class="card-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="card-title">Comprar Produtos</div>
                <div class="card-description">
                    Registre novas compras, adicione fotos dos produtos e defina preços de revenda.
                </div>
                <div class="card-badge">Gestão de Entrada</div>
            </a>
            
            <a href="vender.php" class="dashboard-card card-vender">
                <div class="card-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="card-title">Vender Produtos</div>
                <div class="card-description">
                    Realize vendas, controle estoque e registre formas de pagamento.
                </div>
                <div class="card-badge">Gestão de Saída</div>
            </a>
            
            <a href="relatorios.php" class="dashboard-card card-relatorios">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">Relatórios</div>
                <div class="card-description">
                    Acesse relatórios detalhados de vendas, estoque e lucratividade.
                </div>
                <div class="card-badge">Analytics</div>
            </a>
            
            <a href="gerenciar.php" class="dashboard-card card-gerenciar">
                <div class="card-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="card-title">Gerenciar</div>
                <div class="card-description">
                    Ajuste estoque, corrija preços e faça manutenção do sistema.
                </div>
                <div class="card-badge">Administração</div>
            </a>
            
            <a href="backup_simples.php" class="dashboard-card card-backup">
                <div class="card-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="card-title">Backup</div>
                <div class="card-description">
                    Faça backup completo no Google Drive. Banco de dados e imagens protegidos.
                </div>
                <div class="card-badge">Segurança</div>
            </a>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>COISABOA &copy; 2024 - Sistema de Gestão Comercial</p>
        </div>
    </div>

    <script>
        // Adicionar animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>