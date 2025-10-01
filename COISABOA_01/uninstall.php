<?php
/**
 * COISABOA - Desinstalador (Use com cuidado!)
 * 
 * ATENÇÃO: Este script irá remover todas as tabelas do banco de dados
 * Execute apenas se você tem certeza absoluta do que está fazendo
 */

// Verificar se foi acessado via POST para confirmar
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Desinstalar COISABOA</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; }
            .warning { background: #fef2f2; border: 1px solid #fecaca; padding: 20px; border-radius: 8px; }
            .btn { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; }
            .btn-danger { background: #dc2626; color: white; }
            .btn-outline { background: transparent; border: 1px solid #d1d5db; }
        </style>
    </head>
    <body>
        <div class="warning">
            <h1>🚨 ATENÇÃO</h1>
            <p>Esta ação irá <strong>remover permanentemente</strong> todas as tabelas e dados do COISABOA.</p>
            <p><strong>Esta ação não pode ser desfeita!</strong></p>
            <p>Faça backup do seu banco de dados antes de continuar.</p>
            
            <form method="POST" style="margin-top: 20px;">
                <label>
                    <input type="checkbox" name="confirm" required>
                    Eu entendo que todos os dados serão perdidos permanentemente
                </label>
                <br><br>
                <button type="submit" class="btn btn-danger">💀 Desinstalar COISABOA</button>
                <a href="/" class="btn btn-outline">Cancelar</a>
            </form>
        </div>
    </body>
    </html>');
}

// Verificar confirmação
if (!isset($_POST['confirm'])) {
    die('Confirmação necessária.');
}

// Carregar configuração
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Lista de tabelas para remover
    $tables = ['compras', 'vendas', 'usuarios', 'configuracoes', 'logs'];
    
    echo "<h1>Desinstalando COISABOA...</h1>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "<li>Tabela '$table' removida</li>";
    }
    
    // Remover arquivos de configuração
    $config_files = ['config/database.php', 'config/installed.json'];
    foreach ($config_files as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            unlink(__DIR__ . '/' . $file);
            echo "<li>Arquivo '$file' removido</li>";
        }
    }
    
    echo "</ul>";
    echo "<h2 style='color: #dc2626;'>✅ COISABOA foi desinstalado com sucesso!</h2>";
    echo "<p>Você pode reinstalar acessando <a href='/install.php'>/install.php</a></p>";
    
} catch (Exception $e) {
    die("<h1>Erro na desinstalação</h1><p>" . $e->getMessage() . "</p>");
}
?>