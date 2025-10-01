<?php
/**
 * COISABOA - Processamento de Nova Compra
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('comprei.php', 'Método não permitido.', 'error');
}

// Validar CSRF
if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    redirect('comprei.php', 'Token de segurança inválido.', 'error');
}

// Sanitizar dados
$dados = sanitizar($_POST);

// Validar dados da compra
$validacao = validarCompra($dados);

if (!$validacao['valido']) {
    // Retornar erros para o formulário
    $_SESSION['form_errors'] = $validacao['erros'];
    $_SESSION['form_data'] = $dados;
    redirect('comprei.php', 'Por favor, corrija os erros abaixo.', 'error');
}

// Processar upload da imagem (se existir)
$nome_imagem = null;
if (isset($_FILES['foto_vendedor']) && $_FILES['foto_vendedor']['error'] === UPLOAD_ERR_OK) {
    $upload_resultado = uploadImagem($_FILES['foto_vendedor'], 'vendors');
    
    if (!$upload_resultado['sucesso']) {
        redirect('comprei.php', 'Erro no upload da imagem: ' . implode(', ', $upload_resultado['erros']), 'error');
    }
    
    $nome_imagem = $upload_resultado['arquivo'];
}

// Preparar dados para inserção
$dados_insercao = [
    'produto' => $dados['produto'],
    'quantidade' => intval($dados['quantidade']),
    'valor_pago' => sanitizarMonetario($dados['valor_pago']),
    'valor_proposto' => sanitizarMonetario($dados['valor_proposto']),
    'foto_vendedor' => $nome_imagem,
    'observacoes' => $dados['observacoes'] ?? null,
    'data_compra' => date('Y-m-d H:i:s')
];

// Calcular valor total
$dados_insercao['valor_total'] = $dados_insercao['valor_pago'] * $dados_insercao['quantidade'];

try {
    // Inserir no banco
    $compra_id = dbInsert('compras', $dados_insercao);
    
    if ($compra_id) {
        // Log da atividade
        logAtividade('compra', 'Nova compra registrada: ' . $dados['produto'] . ' (Qtd: ' . $dados['quantidade'] . ')');
        
        // Redirecionar com sucesso
        redirect('estoque.php', 'Compra registrada com sucesso! ✅', 'success');
    } else {
        throw new Exception('Erro ao inserir no banco de dados');
    }
    
} catch (Exception $e) {
    // Em caso de erro, excluir imagem se foi uploadada
    if ($nome_imagem) {
        removerImagem($nome_imagem, 'vendors');
    }
    
    logDebug('Erro ao registrar compra: ' . $e->getMessage(), 'ERROR');
    redirect('comprei.php', 'Erro ao registrar compra. Tente novamente.', 'error');
}
?>