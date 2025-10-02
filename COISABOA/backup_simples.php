<?php
/**
 * COISABOA - Backup Simplificado
 */

session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    try {
        // Pasta onde o Google Drive do celular sincroniza
        // Ajuste este caminho conforme sua configuração
        $pasta_drive = '/sdcard/Google Drive/COISABOA_Backups';
        $pasta_drive_alternativa = '/storage/emulated/0/Google Drive/COISABOA_Backups';
        
        // Tentar detectar a pasta do Drive
        $pasta_backup = null;
        if (is_dir($pasta_drive)) {
            $pasta_backup = $pasta_drive;
        } elseif (is_dir($pasta_drive_alternativa)) {
            $pasta_backup = $pasta_drive_alternativa;
        } else {
            // Criar pasta local que você pode mover manualmente para o Drive
            $pasta_backup = 'backups_drive';
            if (!is_dir($pasta_backup)) {
                mkdir($pasta_backup, 0755, true);
            }
        }
        
        // Data do backup
        $data = date('Y-m-d_H-i-s');
        $pasta_data = $pasta_backup . '/Backup_' . $data;
        
        if (!is_dir($pasta_data)) {
            mkdir($pasta_data, 0755, true);
        }
        
        // 1. Backup do Banco de Dados
        $resultado_db = backupDatabase($pasta_data);
        
        // 2. Backup das Imagens
        $resultado_imagens = backupImagens('uploads/', $pasta_data);
        
        $mensagem = "✅ Backup criado com sucesso!<br>";
        $mensagem .= "📁 Pasta: " . basename($pasta_data) . "<br>";
        $mensagem .= "🗄️ Banco: {$resultado_db['arquivo']}<br>";
        $mensagem .= "🖼️ Imagens: {$resultado_imagens['quantidade']} arquivos<br><br>";
        
        if (strpos($pasta_backup, 'backups_drive') !== false) {
            $mensagem .= "💡 <strong>Próximo passo:</strong> Mova a pasta 'backups_drive' para sua pasta do Google Drive no celular";
        } else {
            $mensagem .= "☁️ <strong>Backup sincronizando automaticamente com Google Drive</strong>";
        }
        
    } catch (Exception $e) {
        $mensagem = "❌ Erro: " . $e->getMessage();
    }
}

function backupDatabase($pasta_destino) {
    require_once 'config/database.php';
    
    $arquivo_sql = $pasta_destino . '/coisaboa_backup.sql';
    
    // Comando para exportar banco
    $command = "mysqldump -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASS . " " . DB_NAME . " > " . $arquivo_sql;
    system($command);
    
    if (!file_exists($arquivo_sql)) {
        throw new Exception('Erro ao gerar backup do banco');
    }
    
    return [
        'arquivo' => basename($arquivo_sql),
        'tamanho' => filesize($arquivo_sql)
    ];
}

function backupImagens($pasta_origem, $pasta_destino) {
    if (!is_dir($pasta_origem)) {
        throw new Exception("Pasta de imagens não encontrada: {$pasta_origem}");
    }
    
    $pasta_destino_imagens = $pasta_destino . '/uploads';
    if (!is_dir($pasta_destino_imagens)) {
        mkdir($pasta_destino_imagens, 0755, true);
    }
    
    $quantidade = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pasta_origem, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $caminho_relativo = $iterator->getSubPathName();
            $caminho_destino = $pasta_destino_imagens . '/' . $caminho_relativo;
            
            // Criar diretório se não existir
            $dir_destino = dirname($caminho_destino);
            if (!is_dir($dir_destino)) {
                mkdir($dir_destino, 0755, true);
            }
            
            copy($file->getPathname(), $caminho_destino);
            $quantidade++;
        }
    }
    
    return ['quantidade' => $quantidade];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - COISABOA</title>
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
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn { 
            background: #27ae60; 
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #219a52;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .mensagem { 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            line-height: 1.6;
        }
        
        .sucesso { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        
        .erro { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        
        .info { 
            background: #e8f4fd; 
            color: #1967d2;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .arquivos {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .arquivo-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 5px;
        }
        
        .arquivo-icon {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .voltar-link {
            display: inline-block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .voltar-link:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: none;
            text-align: center;
            color: #7f8c8d;
            margin: 10px 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>☁️ Backup no Google Drive</h1>
            <p>Seu celular já está sincronizado? Ótimo! O backup será automático.</p>
        </div>
        
        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>📱 Como funciona:</strong>
            <ul style="margin: 10px 0 10px 20px;">
                <li>Backup criado localmente no celular</li>
                <li>Google Drive sincroniza automaticamente</li>
                <li>Seguro e sem configurações complexas</li>
                <li>Acesso aos backups em qualquer dispositivo</li>
            </ul>
        </div>
        
        <div class="arquivos">
            <strong>📋 O que será salvo:</strong>
            <div class="arquivo-item">
                <div class="arquivo-icon">📊</div>
                <div>coisaboa_backup.sql (banco de dados completo)</div>
            </div>
            <div class="arquivo-item">
                <div class="arquivo-icon">🖼️</div>
                <div>uploads/ (todas as imagens dos produtos)</div>
            </div>
            <div class="arquivo-item">
                <div class="arquivo-icon">👤</div>
                <div>vendedores/ (fotos dos vendedores)</div>
            </div>
            <div class="arquivo-item">
                <div class="arquivo-icon">👥</div>
                <div>compradores/ (fotos dos compradores)</div>
            </div>
        </div>
        
        <form method="POST" id="backupForm">
            <input type="hidden" name="action" value="backup">
            <button type="submit" class="btn" id="backupBtn">
                🔄 Criar Backup Agora
            </button>
        </form>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            Criando backup... Isso pode levar alguns instantes.
        </div>
        
        <a href="dashboard.php" class="voltar-link">← Voltar para o Dashboard</a>
    </div>

    <script>
        document.getElementById('backupForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('backupBtn');
            const loading = document.getElementById('loading');
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Criando Backup...';
            loading.style.display = 'block';
        });
    </script>
</body>
</html>