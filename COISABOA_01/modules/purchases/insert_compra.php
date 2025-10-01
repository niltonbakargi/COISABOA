<?php
/**
 * COISABOA - Processamento de Nova Compra
 */

require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('comprei.php', 'Método não permitido.', 'error');
}

if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    redirect('comprei.php', 'Token inválido.', 'error');
}

$dados = sanitizar($_POST);
$validacao = validarCompra($dados);

if (!$validacao['valido']) {
    $_SESSION['form_errors'] = $validacao['erros'];
    redirect('comprei.php', 'Corrija os erros abaixo.', 'error');
}

// Processar upload se existir
$nome_imagem = null;
if (isset($_FILES['foto_vendedor']) && $_FILES['foto_vendedor']['error'] === UPLOAD_ERR_OK) {
    $upload_resultado = uploadImagem($_FILES['foto_vendedor'], 'vendors');
    if ($upload_resultado['sucesso']) {
        $nome_imagem = $upload_resultado['arquivo'];
    }
}

$dados_insercao = [
    'produto' => $dados['produto'],
    'quantidade' => intval($dados['quantidade']),
    'valor_pago' => sanitizarMonetario($dados['valor_pago']),
    'valor_proposto' => sanitizarMonetario($dados['valor_proposto']),
    'foto_vendedor' => $nome_imagem,
    'data_compra' => date('Y-m-d H:i:s')
];

try {
    $compra_id = dbInsert('compras', $dados_insercao);
    
    if ($compra_id) {
        logAtividade('compra', 'Nova compra: ' . $dados['produto']);
        redirect('estoque.php', 'Compra registrada! ✅', 'success');
    } else {
        throw new Exception('Erro ao inserir');
    }
} catch (Exception $e) {
    if ($nome_imagem) removerImagem($nome_imagem, 'vendors');
    redirect('comprei.php', 'Erro ao registrar compra.', 'error');
}
?>