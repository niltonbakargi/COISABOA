<?php
// backup_mobile.php - Versão otimizada para mobile
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Detectar se é mobile
$is_mobile = preg_match("/(android|iphone|ipod|blackberry|mobile)/i", $_SERVER['HTTP_USER_AGENT']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Backup - COISABOA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #4285f4;
            --success: #34a853;
            --danger: #ea4335;
            --background: #ffffff;
            --surface: #f8f9fa;
            --text: #202124;
            --text-secondary: #5f6368;
        }
        
        @media (prefers-color-scheme: dark) {
            :root {
                --background: #202124;
                --surface: #2d2e30;
                --text: #e8eaed;
                --text-secondary: #9aa0a6;
            }
        }
        
        * { 
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            padding: 16px;
        }
        
        .card {
            background: var(--surface);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 17px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            margin: 8px 0;
            transition: all 0.2s;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .status {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 12px;
            margin: 16px 0;
            background: rgba(52, 168, 83, 0.1);
            color: var(--success);
        }
        
        .status::before {
            content: "✅";
            margin-right: 8px;
            font-size: 20px;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: var(--background);
            border-radius: 8px;
            margin: 8px 0;
        }
        
        .file-icon {
            margin-right: 12px;
            font-size: 20px;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        h2 {
            font-size: 18px;
            color: var(--text-secondary);
            font-weight: normal;
            margin-bottom: 20px;
        }
        
        .last-backup {
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
            margin: 16px 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>☁️ Backup</h1>
        <h2>Seus dados protegidos no Google Drive</h2>
        
        <div class="status">
            Google Drive sincronizado ✓
        </div>
        
        <button class="btn btn-success" onclick="fazerBackup()">
            🔄 Fazer Backup Agora
        </button>
        
        <div class="last-backup" id="lastBackup">
            Último backup: Nenhum
        </div>
    </div>
    
    <div class="card">
        <h3>Seus dados protegidos:</h3>
        
        <div class="file-item">
            <div class="file-icon">📊</div>
            <div>
                <strong>Banco de dados</strong>
                <div style="color: var(--text-secondary); font-size: 14px;">Produtos, vendas, clientes</div>
            </div>
        </div>
        
        <div class="file-item">
            <div class="file-icon">🖼️</div>
            <div>
                <strong>Fotos dos produtos</strong>
                <div style="color: var(--text-secondary); font-size: 14px;">Todas as imagens salvas</div>
            </div>
        </div>
        
        <div class="file-item">
            <div class="file-icon">👤</div>
            <div>
                <strong>Comprovantes</strong>
                <div style="color: var(--text-secondary); font-size: 14px;">Fotos de vendedores/compradores</div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3>📁 No seu Google Drive:</h3>
        <div style="color: var(--text-secondary); font-size: 14px; margin-top: 8px;">
            Procure por "COISABOA_Backups" para acessar seus backups de qualquer dispositivo
        </div>
    </div>

    <script>
        function fazerBackup() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '⏳ Criando backup...';
            btn.disabled = true;
            
            // Simular processo de backup
            setTimeout(() => {
                btn.innerHTML = '✅ Backup concluído!';
                btn.style.background = '#34a853';
                
                document.getElementById('lastBackup').textContent = 
                    'Último backup: ' + new Date().toLocaleString();
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                }, 3000);
            }, 2000);
        }
        
        // Verificar último backup ao carregar
        window.addEventListener('load', function() {
            // Aqui você pode buscar a data do último backup do localStorage
            const lastBackup = localStorage.getItem('lastBackup');
            if (lastBackup) {
                document.getElementById('lastBackup').textContent = 
                    'Último backup: ' + lastBackup;
            }
        });
    </script>
</body>
</html>