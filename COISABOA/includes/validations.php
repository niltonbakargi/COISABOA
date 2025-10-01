<?php
/**
 * COISABOA - Sistema de Validações
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== VALIDAÇÕES DE DADOS ====================

/**
 * Validação completa de produto
 * @param array $dados Dados do produto
 * @return array Resultado da validação
 */
function validarProduto($dados) {
    $erros = [];
    
    // Nome do produto
    if (empty($dados['produto'])) {
        $erros['produto'] = 'Nome do produto é obrigatório';
    } else {
        $nome = trim($dados['produto']);
        if (strlen($nome) < 2) {
            $erros['produto'] = 'Nome muito curto (mínimo 2 caracteres)';
        }
        if (strlen($nome) > 100) {
            $erros['produto'] = 'Nome muito longo (máximo 100 caracteres)';
        }
    }
    
    // Quantidade
    if (!isset($dados['quantidade']) || $dados['quantidade'] === '') {
        $erros['quantidade'] = 'Quantidade é obrigatória';
    } else if (!is_numeric($dados['quantidade'])) {
        $erros['quantidade'] = 'Quantidade deve ser um número';
    } else if ($dados['quantidade'] <= 0) {
        $erros['quantidade'] = 'Quantidade deve ser maior que zero';
    } else if ($dados['quantidade'] > 999999) {
        $erros['quantidade'] = 'Quantidade muito grande';
    }
    
    return [
        'valido' => empty($erros),
        'erros' => $erros,
        'dados' => $dados
    ];
}

/**
 * Validação de compra
 * @param array $dados Dados da compra
 * @return array Resultado da validação
 */
function validarCompra($dados) {
    $resultado = validarProduto($dados);
    
    if (!$resultado['valido']) {
        return $resultado;
    }
    
    $erros = $resultado['erros'];
    
    // Valor pago
    if (empty($dados['valor_pago'])) {
        $erros['valor_pago'] = 'Valor pago é obrigatório';
    } else if (!is_numeric($dados['valor_pago'])) {
        $erros['valor_pago'] = 'Valor pago deve ser um número';
    } else if ($dados['valor_pago'] <= 0) {
        $erros['valor_pago'] = 'Valor pago deve ser maior que zero';
    }
    
    // Valor proposto
    if (empty($dados['valor_proposto'])) {
        $erros['valor_proposto'] = 'Valor proposto é obrigatório';
    } else if (!is_numeric($dados['valor_proposto'])) {
        $erros['valor_proposto'] = 'Valor proposto deve ser um número';
    } else if ($dados['valor_proposto'] <= 0) {
        $erros['valor_proposto'] = 'Valor proposto deve ser maior que zero';
    }
    
    // Verificar se valor proposto é maior que valor pago (alerta)
    if (isset($dados['valor_pago']) && isset($dados['valor_proposto'])) {
        if ($dados['valor_proposto'] < $dados['valor_pago']) {
            $erros['valor_proposto'] = 'Valor proposto menor que valor pago (prejuízo)';
        }
    }
    
    return [
        'valido' => empty($erros),
        'erros' => $erros,
        'dados' => $dados
    ];
}

/**
 * Validação de venda
 * @param array $dados Dados da venda
 * @param int $estoqueAtual Estoque disponível
 * @return array Resultado da validação
 */
function validarVenda($dados, $estoqueAtual = null) {
    $resultado = validarProduto($dados);
    
    if (!$resultado['valido']) {
        return $resultado;
    }
    
    $erros = $resultado['erros'];
    
    // Valor vendido
    if (empty($dados['valor_vendido'])) {
        $erros['valor_vendido'] = 'Valor vendido é obrigatório';
    } else if (!is_numeric($dados['valor_vendido'])) {
        $erros['valor_vendido'] = 'Valor vendido deve ser um número';
    } else if ($dados['valor_vendido'] <= 0) {
        $erros['valor_vendido'] = 'Valor vendido deve ser maior que zero';
    }
    
    // Verificar estoque
    if ($estoqueAtual !== null) {
        if ($dados['quantidade'] > $estoqueAtual) {
            $erros['quantidade'] = 'Estoque insuficiente. Disponível: ' . $estoqueAtual;
        }
    }
    
    return [
        'valido' => empty($erros),
        'erros' => $erros,
        'dados' => $dados
    ];
}

// ==================== VALIDAÇÕES DE ARQUIVOS ====================

/**
 * Validação completa de imagem
 * @param array $arquivo Dados do arquivo
 * @return array Resultado da validação
 */
function validarImagem($arquivo) {
    $erros = [];
    
    if (!isset($arquivo['error'])) {
        return ['valido' => false, 'erros' => ['Arquivo inválido']];
    }
    
    // Verificar erro de upload
    switch ($arquivo['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $erros[] = 'Arquivo muito grande. Máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            break;
        case UPLOAD_ERR_PARTIAL:
            $erros[] = 'Upload realizado parcialmente';
            break;
        case UPLOAD_ERR_NO_FILE:
            $erros[] = 'Nenhum arquivo selecionado';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $erros[] = 'Pasta temporária não encontrada';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $erros[] = 'Erro ao gravar arquivo no disco';
            break;
        case UPLOAD_ERR_EXTENSION:
            $erros[] = 'Upload bloqueado por extensão do PHP';
            break;
        default:
            $erros[] = 'Erro desconhecido no upload';
    }
    
    if (!empty($erros)) {
        return ['valido' => false, 'erros' => $erros];
    }
    
    // Verificar tamanho
    if ($arquivo['size'] > MAX_FILE_SIZE) {
        $erros[] = 'Arquivo excede o tamanho máximo permitido';
    }
    
    // Verificar tipo pelo nome
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, ALLOWED_IMAGE_EXTENSIONS)) {
        $erros[] = 'Tipo de arquivo não permitido. Use: ' . implode(', ', ALLOWED_IMAGE_EXTENSIONS);
    }
    
    // Verificar tipo real pelo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeReal, ALLOWED_IMAGE_TYPES)) {
        $erros[] = 'Tipo de arquivo não corresponde ao esperado';
    }
    
    // Verificar se é realmente uma imagem
    $dimensoes = getimagesize($arquivo['tmp_name']);
    if (!$dimensoes) {
        $erros[] = 'Arquivo não é uma imagem válida';
    } else {
        // Verificar dimensões máximas
        if ($dimensoes[0] > MAX_IMAGE_WIDTH || $dimensoes[1] > MAX_IMAGE_HEIGHT) {
            $erros[] = 'Imagem muito grande. Máximo: ' . MAX_IMAGE_WIDTH . 'x' . MAX_IMAGE_HEIGHT . 'px';
        }
        
        // Verificar dimensões mínimas
        if ($dimensoes[0] < 10 || $dimensoes[1] < 10) {
            $erros[] = 'Imagem muito pequena. Mínimo: 10x10px';
        }
    }
    
    return [
        'valido' => empty($erros),
        'erros' => $erros,
        'dados' => [
            'nome' => $arquivo['name'],
            'tamanho' => $arquivo['size'],
            'tipo' => $mimeReal,
            'largura' => $dimensoes[0] ?? 0,
            'altura' => $dimensoes[1] ?? 0
        ]
    ];
}

// ==================== VALIDAÇÕES DE FORMULÁRIO ====================

/**
 * Valida token CSRF do formulário
 * @param string $token Token a validar
 * @return bool É válido
 */
function validarCSRF($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Valida se requisição é POST
 * @return bool É POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Valida se requisição é AJAX
 * @return bool É AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// ==================== VALIDAÇÕES DE NEGÓCIO ====================

/**
 * Valida margem de lucro (alerta)
 * @param float $custo Custo do produto
 * @param float $venda Preço de venda
 * @param float $margemMinima Margem mínima aceitável (%)
 * @return array Resultado da validação
 */
function validarMargemLucro($custo, $venda, $margemMinima = 10) {
    if ($custo <= 0 || $venda <= 0) {
        return ['valido' => false, 'erro' => 'Valores inválidos'];
    }
    
    $margem = (($venda - $custo) / $custo) * 100;
    $lucro = $venda - $custo;
    
    $resultado = [
        'margem' => round($margem, 2),
        'lucro' => $lucro,
        'valido' => $margem >= $margemMinima
    ];
    
    if ($margem < $margemMinima) {
        $resultado['alerta'] = 'Margem de lucro baixa: ' . round($margem, 2) . '%';
    }
    
    if ($margem < 0) {
        $resultado['alerta'] = 'PREJUÍZO: Margem negativa de ' . round($margem, 2) . '%';
        $resultado['valido'] = false;
    }
    
    return $resultado;
}

/**
 * Valida se estoque está abaixo do mínimo
 * @param int $estoqueAtual Estoque atual
 * @param int $estoqueMinimo Estoque mínimo configurado
 * @return array Resultado da validação
 */
function validarEstoqueMinimo($estoqueAtual, $estoqueMinimo = ESTOQUE_MINIMO_ALERTA) {
    $status = 'normal';
    $alerta = '';
    
    if ($estoqueAtual <= 0) {
        $status = 'critico';
        $alerta = 'ESTOQUE ZERADO';
    } else if ($estoqueAtual <= $estoqueMinimo) {
        $status = 'alerta';
        $alerta = 'Estoque baixo: ' . $estoqueAtual . ' unidades';
    }
    
    return [
        'status' => $status,
        'alerta' => $alerta,
        'valido' => $estoqueAtual > 0
    ];
}

// ==================== FUNÇÕES DE SANITIZAÇÃO ESPECÍFICA ====================

/**
 * Sanitiza valor monetário
 * @param string $valor Valor a sanitizar
 * @return float Valor sanitizado
 */
function sanitizarMonetario($valor) {
    // Remove R$, pontos e espaços, mantém apenas números e vírgula
    $valor = preg_replace('/[^0-9,]/', '', $valor);
    // Substitui vírgula por ponto para float
    $valor = str_replace(',', '.', $valor);
    // Remove pontos desnecessários (milhares)
    $valor = preg_replace('/(\.[0-9]{2})[0-9]*/', '$1', $valor);
    
    return floatval($valor);
}

/**
 * Sanitiza número inteiro
 * @param mixed $numero Número a sanitizar
 * @return int Número sanitizado
 */
function sanitizarInteiro($numero) {
    return intval(preg_replace('/[^0-9]/', '', $numero));
}

/**
 * Sanitiza nome de produto
 * @param string $nome Nome a sanitizar
 * @return string Nome sanitizado
 */
function sanitizarNomeProduto($nome) {
    $nome = trim($nome);
    $nome = preg_replace('/[^a-zA-Z0-9\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ&-_]/u', '', $nome);
    $nome = preg_replace('/\s+/', ' ', $nome);
    
    return ucwords(mb_strtolower($nome, 'UTF-8'));
}

?>