<?php
/**
 * COISABOA - Ferramenta de Backup Simples
 * @version 1.0.0
 */

require_once __DIR__ . '/includes/init.php';

// Verificar se está logado (para versões futuras)
// requererAutenticacao();

$backup_dir = __DIR__ . '/backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Nome do arquivo de backup
$backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

try {
    $pdo = getDBConnection();
    
    // Obter todas as tabelas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $backup_content = "-- COISABOA Backup\n";
    $backup_content .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Versão: " . SISTEMA_VERSION . "\n\n";
    
    foreach ($tables as $table) {
        $backup_content .= "-- Estrutura da tabela: $table\n";
        
        // Obter estrutura da tabela
        $create_table = $pdo->query("SHOW CREATE TABLE $table")->fetch();
        $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup_content .= $create_table['Create Table'] . ";\n\n";
        
        // Obter dados da tabela
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $backup_content .= "-- Dados da tabela: $table\n";
            $backup_content .= "INSERT INTO `$table` VALUES\n";
            
            $insert_values = [];
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, $row);
                
                $insert_values[] = "(" . implode(', ', $values) . ")";
            }
            
            $backup_content .= implode(",\n", $insert_values) . ";\n\n";
        }
    }
    
    // Salvar arquivo
    if (file_put_contents($backup_file, $backup_content)) {
        $file_size = filesize($backup_file);
        echo "<div style='padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;'>";
        echo "<h3>✅ Backup criado com sucesso!</h3>";
        echo "<p><strong>Arquivo:</strong> " . basename($backup_file) . "</p>";
        echo "<p><strong>Tamanho:</strong> " . number_format($file_size) . " bytes</p>";
        echo "<p><strong>Local:</strong> " . realpath($backup_file) . "</p>";
        echo "</div>";
    } else {
        throw new Exception("Erro ao salvar arquivo de backup");
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;'>";
    echo "<h3>❌ Erro no backup</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Link para voltar
echo "<div style='margin-top: 20px;'>";
echo "<a href='/'>← Voltar para o sistema</a>";
echo "</div>";
?>