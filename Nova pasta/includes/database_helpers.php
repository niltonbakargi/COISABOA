<?php
/**
 * COISABOA - Helpers para Operações com Banco de Dados
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== OPERAÇÕES BÁSICAS CRUD ====================

/**
 * Executa query com parâmetros seguros
 * @param string $sql Query SQL
 * @param array $params Parâmetros para prepared statement
 * @return PDOStatement Statement executado
 */
function dbQuery($sql, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Busca único registro
 * @param string $sql Query SQL
 * @param array $params Parâmetros
 * @return array|false Registro encontrado ou false
 */
function dbFind($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Busca todos os registros
 * @param string $sql Query SQL
 * @param array $params Parâmetros
 * @return array Array de registros
 */
function dbFindAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insere registro e retorna ID
 * @param string $tabela Nome da tabela
 * @param array $dados Dados para inserir
 * @return int|false ID do registro inserido ou false
 */
function dbInsert($tabela, $dados) {
    $campos = implode(', ', array_keys($dados));
    $placeholders = ':' . implode(', :', array_keys($dados));
    
    $sql = "INSERT INTO {$tabela} () VALUES ()";
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        logDebug('Erro ao inserir em ' . $tabela . ': ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Atualiza registros
 * @param string $tabela Nome da tabela
 * @param array $dados Dados para atualizar
 * @param string $condicao Condição WHERE
 * @param array $paramsCondicao Parâmetros da condição
 * @return int Número de linhas afetadas
 */
function dbUpdate($tabela, $dados, $condicao, $paramsCondicao = []) {
    $setParts = [];
    foreach (array_keys($dados) as $campo) {
        $setParts[] = "{$campo} = :{$campo}";
    }
    $setClause = implode(', ', $setParts);
    
    $sql = "UPDATE {$tabela} SET {$setClause} WHERE {$condicao}";
    $params = array_merge($dados, $paramsCondicao);
    
    try {
        $stmt = dbQuery($sql, $params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        logDebug('Erro ao atualizar ' . $tabela . ': ' . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Deleta registros
 * @param string $tabela Nome da tabela
 * @param string $condicao Condição WHERE
 * @param array $params Parâmetros da condição
 * @return int Número de linhas afetadas
 */
function dbDelete($tabela, $condicao, $params = []) {
    $sql = "DELETE FROM {$tabela} WHERE {$condicao}";
    
    try {
        $stmt = dbQuery($sql, $params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        logDebug('Erro ao deletar de ' . $tabela . ': ' . $e->getMessage(), 'ERROR');
        return 0;
    }
}

// ==================== OPERAÇÕES ESPECÍFICAS DO SISTEMA ====================

/**
 * Busca estoque atual de um produto
 * @param string $produto Nome do produto
 * @return int Estoque atual
 */
function getEstoqueAtual($produto) {
    $sql = "SELECT 
                COALESCE(SUM(C.quantidade), 0) - COALESCE(SUM(V.quantidade), 0) as estoque
            FROM 
                (SELECT produto, quantidade FROM compras WHERE produto = ?) C
            LEFT JOIN 
                (SELECT produto, quantidade FROM vendas WHERE produto = ?) V
            ON C.produto = V.produto";
    
    $resultado = dbFind($sql, [$produto, $produto]);
    return intval($resultado['estoque'] ?? 0);
}

/**
 * Busca histórico completo de um produto
 * @param string $produto Nome do produto
 * @return array Histórico consolidado
 */
function getHistóricoProduto($produto) {
    // Compras do produto
    $compras = dbFindAll("
        SELECT data_compra as data, quantidade, valor_pago as valor, 'compra' as tipo
        FROM compras 
        WHERE produto = ?
        ORDER BY data_compra DESC
    ", [$produto]);
    
    // Vendas do produto
    $vendas = dbFindAll("
        SELECT data_venda as data, quantidade, valor_vendido as valor, 'venda' as tipo
        FROM vendas 
        WHERE produto = ?
        ORDER BY data_venda DESC
    ", [$produto]);
    
    // Combinar e ordenar por data
    $historico = array_merge($compras, $vendas);
    usort($historico, function($a, $b) {
        return strtotime($b['data']) - strtotime($a['data']);
    });
    
    return $historico;
}

/**
 * Busca relatório consolidado de estoque
 * @return array Relatório completo
 */
function getRelatorioEstoque() {
    $sql = "
        SELECT 
            C.produto,
            COALESCE(SUM(C.quantidade), 0) as total_comprado,
            COALESCE(SUM(C.quantidade * C.valor_pago), 0) as valor_total_comprado,
            COALESCE(SUM(V.quantidade), 0) as total_vendido,
            COALESCE(SUM(V.quantidade * V.valor_vendido), 0) as valor_total_vendido,
            COALESCE(SUM(C.quantidade), 0) - COALESCE(SUM(V.quantidade), 0) as estoque_atual,
            COALESCE(SUM(C.quantidade * C.valor_pago), 0) / NULLIF(COALESCE(SUM(C.quantidade), 0), 0) as custo_medio
        FROM 
            (SELECT produto, quantidade, valor_pago FROM compras) C
        LEFT JOIN 
            (SELECT produto, quantidade, valor_vendido FROM vendas) V
        ON C.produto = V.produto
        GROUP BY C.produto
        ORDER BY estoque_atual DESC, C.produto ASC
    ";
    
    return dbFindAll($sql);
}

// ==================== TRANSACTIONS E BACKUP ====================

/**
 * Executa operação em transaction
 * @param callable $callback Função a executar na transaction
 * @return mixed Resultado da função
 */
function dbTransaction(callable $callback) {
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();
        $resultado = $callback($pdo);
        $pdo->commit();
        return $resultado;
    } catch (Exception $e) {
        $pdo->rollBack();
        logDebug('Transaction falhou: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

/**
 * Cria backup simples do banco
 * @param string $caminhoBackup Caminho para salvar backup
 * @return bool Sucesso da operação
 */
function criarBackupBanco($caminhoBackup) {
    $pdo = getDBConnection();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $backupSQL = "-- Backup COISABOA - " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Estrutura da tabela
        $createTable = $pdo->query("SHOW CREATE TABLE {$table}")->fetch();
        $backupSQL .= "DROP TABLE IF EXISTS {$table};\n";
        $backupSQL .= $createTable['Create Table'] . ";\n\n";
        
        // Dados da tabela
        $rows = $pdo->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $backupSQL .= "INSERT INTO {$table} VALUES\n";
            $insertValues = [];
            
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, $row);
                
                $insertValues[] = "(" . implode(', ', $values) . ")";
            }
            
            $backupSQL .= implode(",\n", $insertValues) . ";\n\n";
        }
    }
    
    return file_put_contents($caminhoBackup, $backupSQL) !== false;
}

?>