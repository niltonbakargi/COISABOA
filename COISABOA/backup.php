<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$mensagem = '';

// Função para criar backup do banco de dados
function criarBackupBancoDados() {
    try {
        $pdo = getDB();
        
        // Nome do arquivo de backup
        $data = date('Y-m-d_H-i-s');
        $backup_file = "backups/backup_db_{$data}.sql";
        
        // Criar diretório de backups se não existir
        if (!file_exists('backups')) {
            mkdir('backups', 0755, true);
        }
        
        // Iniciar conteúdo do backup
        $backup_content = "-- COISABOA - Backup do Banco de Dados\n";
        $backup_content .= "-- Gerado em: " . date('d/m/Y H:i:s') . "\n";
        $backup_content .= "-- Sistema: COISABOA App\n\n";
        
        // Backup das tabelas
        $tables = ['usuarios', 'compras', 'vendas', 'estoque', 'ajustes_estoque', 'exclusoes_estoque', 'correcoes_preco'];
        
        foreach ($tables as $table) {
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $table_exists = $stmt->fetch();
            
            if (!$table_exists) {
                continue;
            }
            
            // Estrutura da tabela
            $backup_content .= "--\n-- Estrutura da tabela `{$table}`\n--\n\n";
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $create_table = $stmt->fetch();
            $backup_content .= $create_table['Create Table'] . ";\n\n";
            
            // Dados da tabela
            $backup_content .= "--\n-- Dump dos dados da tabela `{$table}`\n--\n\n";
            
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    
                    $backup_content .= "INSERT INTO `{$table}` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        // Salvar arquivo
        if (file_put_contents($backup_file, $backup_content)) {
            return [
                'success' => true,
                'file' => $backup_file,
                'size' => filesize($backup_file),
                'tables' => count($tables)
            ];
        } else {
            throw new Exception("Erro ao salvar arquivo de backup");
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Função para criar backup das imagens
function criarBackupImagens() {
    try {
        $data = date('Y-m-d_H-i-s');
        $backup_dir = "backups/images_backup_{$data}";
        
        // Criar diretório de backup
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $folders = ['uploads/produtos', 'uploads/vendedores', 'uploads/compradores'];
        $total_files = 0;
        $total_size = 0;
        
        foreach ($folders as $folder) {
            if (!file_exists($folder)) continue;
            
            $backup_subdir = $backup_dir . '/' . $folder;
            if (!file_exists($backup_subdir)) {
                mkdir($backup_subdir, 0755, true);
            }
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $relative_path = substr($file->getPathname(), strlen($folder) + 1);
                    $backup_path = $backup_subdir . '/' . $relative_path;
                    
                    // Criar diretório se necessário
                    $backup_file_dir = dirname($backup_path);
                    if (!file_exists($backup_file_dir)) {
                        mkdir($backup_file_dir, 0755, true);
                    }
                    
                    if (copy($file->getPathname(), $backup_path)) {
                        $total_files++;
                        $total_size += $file->getSize();
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'directory' => $backup_dir,
            'files' => $total_files,
            'size' => $total_size
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Processar ações de backup
if ($_POST['action'] ?? '' === 'backup_db') {
    $resultado = criarBackupBancoDados();
    
    if ($resultado['success']) {
        $tamanho = round($resultado['size'] / 1024, 2);
        $mensagem = "✅ <strong>Backup do Banco de Dados criado com sucesso!</strong><br>";
        $mensagem .= "📁 <strong>Arquivo:</strong> " . basename($resultado['file']) . "<br>";
        $mensagem .= "📊 <strong>Tabelas:</strong> {$resultado['tables']} tabelas<br>";
        $mensagem .= "💾 <strong>Tamanho:</strong> {$tamanho} KB<br>";
        $mensagem .= "🕒 <strong>Data:</strong> " . date('d/m/Y H:i:s');
    } else {
        $mensagem = "❌ <strong>Erro ao criar backup:</strong> " . $resultado['error'];
    }
}

if ($_POST['action'] ?? '' === 'backup_images') {
    $resultado = criarBackupImagens();
    
    if ($resultado['success']) {
        $tamanho = round($resultado['size'] / (1024 * 1024), 2);
        $mensagem = "✅ <strong>Backup de Imagens criado com sucesso!</strong><br>";
        $mensagem .= "📁 <strong>Diretório:</strong> " . basename($resultado['directory']) . "<br>";
        $mensagem .= "🖼️ <strong>Arquivos:</strong> {$resultado['files']} imagens<br>";
        $mensagem .= "💾 <strong>Tamanho:</strong> {$tamanho} MB<br>";
        $mensagem .= "🕒 <strong>Data:</strong> " . date('d/m/Y H:i:s');
    } else {
        $mensagem = "❌ <strong>Erro ao criar backup de imagens:</strong> " . $resultado['error'];
    }
}

if ($_POST['action'] ?? '' === 'backup_completo') {
    $resultado_db = criarBackupBancoDados();
    $resultado_img = criarBackupImagens();
    
    if ($resultado_db['success'] && $resultado_img['success']) {
        $tamanho_db = round($resultado_db['size'] / 1024, 2);
        $tamanho_img = round($resultado_img['size'] / (1024 * 1024), 2);
        
        $mensagem = "✅ <strong>Backup Completo criado com sucesso!</strong><br><br>";
        $mensagem .= "🗄️ <strong>Banco de Dados:</strong><br>";
        $mensagem .= "&nbsp;&nbsp;📁 Arquivo: " . basename($resultado_db['file']) . "<br>";
        $mensagem .= "&nbsp;&nbsp;📊 Tabelas: {$resultado_db['tables']}<br>";
        $mensagem .= "&nbsp;&nbsp;💾 Tamanho: {$tamanho_db} KB<br><br>";
        $mensagem .= "🖼️ <strong>Imagens:</strong><br>";
        $mensagem .= "&nbsp;&nbsp;📁 Diretório: " . basename($resultado_img['directory']) . "<br>";
        $mensagem .= "&nbsp;&nbsp;🖼️ Arquivos: {$resultado_img['files']} imagens<br>";
        $mensagem .= "&nbsp;&nbsp;💾 Tamanho: {$tamanho_img} MB<br><br>";
        $mensagem .= "🕒 <strong>Data:</strong> " . date('d/m/Y H:i:s');
    } else {
        $mensagem = "❌ <strong>Erro no backup completo:</strong><br>";
        if (!$resultado_db['success']) {
            $mensagem .= "• Banco: " . $resultado_db['error'] . "<br>";
        }
        if (!$resultado_img['success']) {
            $mensagem .= "• Imagens: " . $resultado_img['error'];
        }
    }
}

// Listar backups existentes
$backups_db = [];
$backups_img = [];

if (file_exists('backups')) {
    $files = scandir('backups');
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filepath = 'backups/' . $file;
        if (is_dir($filepath) && strpos($file, 'images_backup_') === 0) {
            $backups_img[] = [
                'name' => $file,
                'path' => $filepath,
                'date' => date('d/m/Y H:i', filemtime($filepath)),
                'size' => folderSize($filepath)
            ];
        } elseif (is_file($filepath) && strpos($file, 'backup_db_') === 0) {
            $backups_db[] = [
                'name' => $file,
                'path' => $filepath,
                'date' => date('d/m/Y H:i', filemtime($filepath)),
                'size' => filesize($filepath)
            ];
        }
    }
}

// Função para calcular tamanho de pasta
function folderSize($dir) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

// Função para formatar tamanho
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Backup - COISABOA</title>
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
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            padding: 20px 15px 80px 15px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: var(--primary);
        }
        
        .btn-voltar {
            background: var(--gray);
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
        
        .btn-voltar:active {
            transform: translateY(-2px);
        }
        
        /* Mensagens */
        .mensagem {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 15px;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .sucesso {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .erro {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Backup Actions */
        .backup-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .backup-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border-top: 4px solid var(--card-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .backup-card:active {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .backup-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--card-color);
        }
        
        .backup-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .backup-description {
            color: var(--gray);
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .btn-backup {
            background: var(--card-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-backup:active {
            transform: scale(0.95);
        }
        
        /* Card Colors */
        .card-db { --card-color: var(--info); }
        .card-images { --card-color: var(--secondary); }
        .card-complete { --card-color: var(--primary); }
        
        /* Backup List */
        .backup-list {
            margin-top: 10px;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 10px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .backup-item:active {
            background: #f8fafc;
        }
        
        .backup-info h4 {
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .backup-info p {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .backup-size {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .sem-backups {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        
        .sem-backups i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Info Box */
        .info-box {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 4px solid var(--info);
            font-size: 0.9rem;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            border-top: 1px solid var(--border);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--gray);
            transition: all 0.3s ease;
            flex: 1;
            padding: 8px 0;
        }
        
        .nav-item.ativo {
            color: var(--primary);
        }
        
        .nav-icon {
            font-size: 1.3rem;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }
            
            .backup-actions {
                grid-template-columns: 1fr;
            }
            
            .backup-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .backup-size {
                align-self: flex-end;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px 10px 80px 10px;
            }
            
            .container {
                max-width: 100%;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .card {
                padding: 20px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header fade-in">
            <h1>
                <i class="fas fa-cloud-upload-alt"></i>
                Sistema de Backup
            </h1>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?> fade-in">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <!-- Informações Importantes -->
        <div class="info-box fade-in">
            <strong><i class="fas fa-info-circle"></i> Informações sobre Backup</strong><br>
            • Os backups são salvos localmente na pasta <code>backups/</code><br>
            • Recomendamos fazer backup regularmente para evitar perda de dados<br>
            • Mantenha cópias em locais seguros (Google Drive, HD externo, etc.)<br>
            • Backups completos incluem banco de dados + todas as imagens
        </div>

        <!-- Ações de Backup -->
        <div class="backup-actions fade-in">
            <!-- Backup do Banco de Dados -->
            <div class="backup-card card-db">
                <div class="backup-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="backup-title">Backup do Banco</div>
                <div class="backup-description">
                    Cria um arquivo SQL com todos os dados do sistema (compras, vendas, estoque, etc.)
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="backup_db">
                    <button type="submit" class="btn-backup">
                        <i class="fas fa-download"></i>
                        Fazer Backup
                    </button>
                </form>
            </div>

            <!-- Backup das Imagens -->
            <div class="backup-card card-images">
                <div class="backup-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="backup-title">Backup de Imagens</div>
                <div class="backup-description">
                    Copia todas as imagens de produtos, vendedores e compradores
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="backup_images">
                    <button type="submit" class="btn-backup">
                        <i class="fas fa-download"></i>
                        Fazer Backup
                    </button>
                </form>
            </div>

            <!-- Backup Completo -->
            <div class="backup-card card-complete">
                <div class="backup-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="backup-title">Backup Completo</div>
                <div class="backup-description">
                    Backup completo do sistema (banco de dados + todas as imagens)
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="backup_completo">
                    <button type="submit" class="btn-backup">
                        <i class="fas fa-download"></i>
                        Backup Completo
                    </button>
                </form>
            </div>
        </div>

        <!-- Backups do Banco de Dados -->
        <div class="card fade-in">
            <h2><i class="fas fa-database"></i> Backups do Banco de Dados</h2>
            <div class="backup-list">
                <?php if (!empty($backups_db)): ?>
                    <?php foreach ($backups_db as $backup): ?>
                        <div class="backup-item">
                            <div class="backup-info">
                                <h4><?= htmlspecialchars($backup['name']) ?></h4>
                                <p>Criado em: <?= $backup['date'] ?></p>
                            </div>
                            <div class="backup-size">
                                <?= formatSize($backup['size']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sem-backups">
                        <i class="fas fa-database"></i>
                        <h3>Nenhum backup encontrado</h3>
                        <p>Faça o primeiro backup do banco de dados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Backups de Imagens -->
        <div class="card fade-in">
            <h2><i class="fas fa-images"></i> Backups de Imagens</h2>
            <div class="backup-list">
                <?php if (!empty($backups_img)): ?>
                    <?php foreach ($backups_img as $backup): ?>
                        <div class="backup-item">
                            <div class="backup-info">
                                <h4><?= htmlspecialchars($backup['name']) ?></h4>
                                <p>Criado em: <?= $backup['date'] ?></p>
                            </div>
                            <div class="backup-size">
                                <?= formatSize($backup['size']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sem-backups">
                        <i class="fas fa-images"></i>
                        <h3>Nenhum backup encontrado</h3>
                        <p>Faça o primeiro backup de imagens</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="compras.php" class="nav-item">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">Compras</span>
        </a>
        <a href="vendas.php" class="nav-item">
            <span class="nav-icon">🏷️</span>
            <span class="nav-label">Vendas</span>
        </a>
        <a href="backup.php" class="nav-item ativo">
            <span class="nav-icon">💾</span>
            <span class="nav-label">Backup</span>
        </a>
    </nav>

    <script>
        // Melhorias para mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback tátil para elementos interativos
            const interactiveElements = document.querySelectorAll('.backup-card, .btn-backup, .backup-item');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            });
            
            // Prevenir envio duplo dos formulários
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const button = this.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando Backup...';
                    
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-download"></i> Fazer Backup';
                    }, 5000);
                });
            });
        });
    </script>
</body>
</html>