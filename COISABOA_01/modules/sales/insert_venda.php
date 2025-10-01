<?php
/**
 * COISABOA - Processamento de Nova Venda
 */

require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('vendi.php', 'Método não permitido.', 'error');
}

if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    redirect('vendi.php', 'Token inválido.', 'error');
}

$dados = sanitizar($_POST);
$estoque_atual = getEstoqueAtual($dados['produto']);
$validacao = validarVenda($dados, $estoque_atual);

if (!$validacao['valido']) {
    $_SESSION['form_errors'] = $validacao['erros'];
    redirect('vendi.php', 'Corrija os erros abaixo.', 'error');
}

$dados_insercao = [
    'produto' => $dados['produto'],
    'quantidade' => intval($dados['quantidade']),
    'valor_vendido' => sanitizarMonetario($dados['valor_vendido']),
    'data_venda' => date('Y-m-d H:i:s')
];

try {
    $venda_id = dbInsert('vendas', $dados_insercao);
    
    if ($venda_id) {
        logAtividade('venda', 'Nova venda: ' . $dados['produto']);
        $novo_estoque = $estoque_atual - $dados_insercao['quantidade'];
        redirect('estoque.php', 'Venda registrada! Novo estoque: ' . $novo_estoque . ' unidades ✅', 'success');
    } else {
        throw new Exception('Erro ao inserir');
    }
} catch (Exception $e) {
    redirect('vendi.php', 'Erro ao registrar venda.', 'error');
}
?>