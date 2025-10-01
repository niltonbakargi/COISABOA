<?php
/**
 * COISABOA - Processamento de Nova Venda
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('vendi.php', 'Método não permitido.', 'error');
}

// Validar CSRF
if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    redirect('vendi.php', 'Token de segurança inválido.', 'error');
}

// Sanitizar dados
$dados = sanitizar($_POST);

// Verificar estoque atual
$estoque_atual = getEstoqueAtual($dados['produto']);

// Validar dados da venda
$validacao = validarVenda($dados, $estoque_atual);

if (!$validacao['valido']) {
    $_SESSION['form_errors'] = $validacao['erros'];
    $_SESSION['form_data'] = $dados;
    redirect('vendi.php', 'Por favor, corrija os erros abaixo.', 'error');
}

// Preparar dados para inserção
$dados_insercao = [
    'produto' => $dados['produto'],
    'quantidade' => intval($dados['quantidade']),
    'valor_vendido' => sanitizarMonetario($dados['valor_vendido']),
    'forma_pagamento' => $dados['forma_pagamento'] ?? 'dinheiro',
    'data_venda' => date('Y-m-d H:i:s')
];

// Calcular valor total
$dados_insercao['valor_total'] = $dados_insercao['valor_vendido'] * $dados_insercao['quantidade'];

try {
    // Inserir no banco usando transaction
    $venda_id = dbTransaction(function($pdo) use ($dados_insercao) {
        return dbInsert('vendas', $dados_insercao);
    });
    
    if ($venda_id) {
        // Log da atividade
        logAtividade('venda', 'Nova venda registrada: ' . $dados['produto'] . ' (Qtd: ' . $dados['quantidade'] . ')');
        
        // Calcular novo estoque
        $novo_estoque = $estoque_atual - $dados_insercao['quantidade'];
        
        // Mensagem de sucesso com informações
        $mensagem = 'Venda registrada com sucesso! ✅';
        $mensagem .= ' Novo estoque: ' . $novo_estoque . ' unidades';
        
        redirect('estoque.php', $mensagem, 'success');
    } else {
        throw new Exception('Erro ao inserir venda no banco');
    }
    
} catch (Exception $e) {
    logDebug('Erro ao registrar venda: ' . $e->getMessage(), 'ERROR');
    redirect('vendi.php', 'Erro ao registrar venda. Tente novamente.', 'error');
}
?>