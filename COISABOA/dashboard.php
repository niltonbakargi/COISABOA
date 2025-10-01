<?php
/**
 * COISABOA - Dashboard
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
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-logout {
            background: #e74c3c;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .card-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            display: block;
        }
        
        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .card-description {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        
        /* Cores específicas para cada card */
        .card-comprar { border-top: 4px solid #27ae60; }
        .card-vender { border-top: 4px solid #e67e22; }
        .card-relatorios { border-top: 4px solid #3498db; }
        .card-gerenciar { border-top: 4px solid #9b59b6; }
        
        .welcome-message {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .welcome-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            color: #7f8c8d;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 COISABOA - Sistema de Gestão</h1>
            <div class="user-info">
                <span>👋 Olá, <strong><?= $_SESSION['usuario']['nome'] ?></strong></span>
                <a href="logout.php" class="btn btn-logout">🚪 Sair</a>
            </div>
        </div>
        
        <div class="welcome-message">
            <h2>Bem-vindo ao Sistema!</h2>
            <p>Gerencie suas compras, vendas e relatórios de forma simples e eficiente.</p>
        </div>
        
        <div class="dashboard-grid">
            <a href="comprar.php" class="dashboard-card card-comprar">
                <div class="card-icon">🛒</div>
                <div class="card-title">Comprar</div>
                <div class="card-description">Registrar novas compras e estoque</div>
            </a>
            
            <a href="vender.php" class="dashboard-card card-vender">
                <div class="card-icon">💰</div>
                <div class="card-title">Vender</div>
                <div class="card-description">Registrar vendas e faturamento</div>
            </a>
            
            <a href="relatorios.php" class="dashboard-card card-relatorios">
                <div class="card-icon">📊</div>
                <div class="card-title">Relatórios</div>
                <div class="card-description">Visualizar relatórios e analytics</div>
            </a>
            
            <a href="gerenciar.php" class="dashboard-card card-gerenciar">
                <div class="card-icon">⚙️</div>
                <div class="card-title">Gerenciar</div>
                <div class="card-description">Configurações e gestão do sistema</div>
            </a>
        </div>
    </div>
</body>
</html>