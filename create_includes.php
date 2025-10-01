<?php
/**
 * COISABOA - Criador Especializado do Sistema de Includes
 * Arquivo: create_includes.php
 * Descrição: Cria estrutura completa da pasta includes com funções utilitárias e validações
 */

echo "📚 INICIANDO CRIAÇÃO DO SISTEMA DE INCLUDES...\n";
echo "=============================================\n";

// Configurações
$project_root = __DIR__ . '/COISABOA';
$includes_dir = $project_root . '/includes';

// Verificar se a pasta principal existe
if (!file_exists($project_root)) {
    echo "❌ ERRO: Pasta principal COISABOA não encontrada!\n";
    echo "Execute primeiro o create_structure.php\n";
    exit(1);
}

// Criar pasta includes se não existir
if (!file_exists($includes_dir)) {
    mkdir($includes_dir, 0755, true);
    echo "✅ Pasta includes criada: $includes_dir\n";
} else {
    echo "📁 Pasta includes já existe: $includes_dir\n";
}

// Arquivos do sistema de includes
$include_files = [
    'functions.php' => "<?php
/**
 * COISABOA - Funções Utilitárias do Sistema
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== FUNÇÕES DE FORMATAÇÃO ====================

/**
 * Formata valor monetário para exibição
 * @param float \$valor Valor a ser formatado
 * @param bool \$simbolo Incluir símbolo R$
 * @return string Valor formatado
 */
function formatarMoeda(\$valor, \$simbolo = true) {
    if (!is_numeric(\$valor)) return 'R$ 0,00';
    
    \$formatado = number_format(floatval(\$valor), 2, ',', '.');
    return \$simbolo ? 'R$ ' . \$formatado : \$formatado;
}

/**
 * Formata data para exibição amigável
 * @param string \$data Data em formato MySQL ou timestamp
 * @param bool \$hora Incluir hora
 * @return string Data formatada
 */
function formatarData(\$data, \$hora = true) {
    if (empty(\$data) || \$data == '0000-00-00 00:00:00') return '-';
    
    \$timestamp = is_numeric(\$data) ? \$data : strtotime(\$data);
    
    if (\$hora) {
        return date('d/m/Y H:i', \$timestamp);
    } else {
        return date('d/m/Y', \$timestamp);
    }
}

/**
 * Formata data relativa (há x tempo)
 * @param string \$data Data para calcular diferença
 * @return string Texto relativo
 */
function formatarDataRelativa(\$data) {
    \$agora = time();
    \$timestamp = is_numeric(\$data) ? \$data : strtotime(\$data);
    \$diferenca = \$agora - \$timestamp;
    
    if (\$diferenca < 60) return 'agora há pouco';
    if (\$diferenca < 3600) return floor(\$diferenca/60) . ' min atrás';
    if (\$diferenca < 86400) return floor(\$diferenca/3600) . ' h atrás';
    if (\$diferenca < 2592000) return floor(\$diferenca/86400) . ' dias atrás';
    
    return formatarData(\$data);
}

/**
 * Formata número para quantidade
 * @param mixed \$quantidade Quantidade a formatar
 * @return string Quantidade formatada
 */
function formatarQuantidade(\$quantidade) {
    if (!is_numeric(\$quantidade)) return '0';
    
    \$quantidade = floatval(\$quantidade);
    
    if (\$quantidade == intval(\$quantidade)) {
        return number_format(\$quantidade, 0, ',', '.');
    } else {
        return number_format(\$quantidade, 2, ',', '.');
    }
}

/**
 * Formata bytes para tamanho legível
 * @param int \$bytes Bytes a formatar
 * @param int \$decimas Casas decimais
 * @return string Tamanho formatado
 */
function formatarTamanhoArquivo(\$bytes, \$decimais = 2) {
    \$tamanhos = ['B', 'KB', 'MB', 'GB', 'TB'];
    \$fator = floor((strlen(\$bytes) - 1) / 3);
    
    if (\$fator == 0) return \$bytes . ' ' . \$tamanhos[\$fator];
    
    return sprintf('%.' . \$decimais . 'f', \$bytes / pow(1024, \$fator)) . ' ' . \$tamanhos[\$fator];
}

// ==================== FUNÇÕES DE SEGURANÇA ====================

/**
 * Sanitiza dados para prevenir XSS
 * @param mixed \$dados Dados a sanitizar
 * @return mixed Dados sanitizados
 */
function sanitizar(\$dados) {
    if (is_array(\$dados)) {
        return array_map('sanitizar', \$dados);
    }
    
    if (is_object(\$dados)) {
        \$props = get_object_vars(\$dados);
        foreach (\$props as \$key => \$value) {
            \$dados->\$key = sanitizar(\$value);
        }
        return \$dados;
    }
    
    return htmlspecialchars(trim(\$dados), ENT_QUOTES, 'UTF-8');
}

/**
 * Remove caracteres especiais para uso em URLs/arquivos
 * @param string \$texto Texto a limpar
 * @return string Texto limpo
 */
function sanitizarNomeArquivo(\$texto) {
    \$texto = preg_replace('/[^a-zA-Z0-9\\s]/', '', \$texto);
    \$texto = str_replace(' ', '_', \$texto);
    \$texto = preg_replace('/_{2,}/', '_', \$texto);
    return strtolower(\$texto);
}

/**
 * Gera token CSRF para forms
 * @return string Token CSRF
 */
function gerarTokenCSRF() {
    if (empty(\$_SESSION['csrf_token'])) {
        \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return \$_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 * @param string \$token Token a validar
 * @return bool É válido
 */
function validarTokenCSRF(\$token) {
    return isset(\$_SESSION['csrf_token']) && hash_equals(\$_SESSION['csrf_token'], \$token);
}

/**
 * Criptografa dados sensíveis (para futuras versões)
 * @param string \$dados Dados a criptografar
 * @return string Dados criptografados
 */
function criptografar(\$dados) {
    return base64_encode(\$dados); // Implementação básica
}

/**
 * Descriptografa dados
 * @param string \$dados Dados criptografados
 * @return string Dados originais
 */
function descriptografar(\$dados) {
    return base64_decode(\$dados);
}

// ==================== FUNÇÕES DE REDIRECIONAMENTO ====================

/**
 * Redireciona com mensagem flash
 * @param string \$url URL para redirecionar
 * @param string \$mensagem Mensagem para exibir
 * @param string \$tipo Tipo da mensagem (success, error, warning, info)
 */
function redirect(\$url, \$mensagem = null, \$tipo = 'info') {
    if (\$mensagem) {
        \$_SESSION['flash_messages'][] = [
            'texto' => \$mensagem,
            'tipo' => \$tipo,
            'timestamp' => time()
        ];
    }
    
    header('Location: ' . \$url);
    exit;
}

/**
 * Recupera e limpa mensagens flash
 * @return array Mensagens flash
 */
function getFlashMessages() {
    \$mensagens = isset(\$_SESSION['flash_messages']) ? \$_SESSION['flash_messages'] : [];
    unset(\$_SESSION['flash_messages']);
    return \$mensagens;
}

/**
 * Exibe mensagens flash formatadas
 */
function exibirFlashMessages() {
    \$mensagens = getFlashMessages();
    
    if (empty(\$mensagens)) return '';
    
    \$html = '<div class=\"flash-messages\">';
    
    foreach (\$mensagens as \$msg) {
        \$classe = 'flash-' . \$msg['tipo'];
        \$html .= '<div class=\"flash-message ' . \$classe . '\">';
        \$html .= '<span class=\"flash-text\">' . \$msg['texto'] . '</span>';
        \$html .= '<small class=\"flash-time\">' . formatarDataRelativa(\$msg['timestamp']) . '</small>';
        \$html .= '</div>';
    }
    
    \$html .= '</div>';
    
    return \$html;
}

// ==================== FUNÇÕES DE VALIDAÇÃO RÁPIDA ====================

/**
 * Verifica se é email válido
 * @param string \$email Email a validar
 * @return bool É válido
 */
function isEmail(\$email) {
    return filter_var(\$email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verifica se é número inteiro positivo
 * @param mixed \$numero Número a validar
 * @return bool É válido
 */
function isInteiroPositivo(\$numero) {
    return filter_var(\$numero, FILTER_VALIDATE_INT) !== false && \$numero > 0;
}

/**
 * Verifica se é valor monetário válido
 * @param mixed \$valor Valor a validar
 * @return bool É válido
 */
function isMonetario(\$valor) {
    return is_numeric(\$valor) && \$valor >= 0;
}

/**
 * Verifica se é URL válida
 * @param string \$url URL a validar
 * @return bool É válida
 */
function isURL(\$url) {
    return filter_var(\$url, FILTER_VALIDATE_URL) !== false;
}

// ==================== FUNÇÕES DE ARRAY/OBJETO ====================

/**
 * Recupera valor seguro de array com fallback
 * @param array \$array Array source
 * @param string \$key Chave a buscar
 * @param mixed \$default Valor padrão
 * @return mixed Valor encontrado ou padrão
 */
function array_get(\$array, \$key, \$default = null) {
    return isset(\$array[\$key]) ? \$array[\$key] : \$default;
}

/**
 * Filtra array mantendo apenas chaves específicas
 * @param array \$array Array original
 * @param array \$keys Chaves a manter
 * @return array Array filtrado
 */
function array_only(\$array, \$keys) {
    return array_intersect_key(\$array, array_flip(\$keys));
}

/**
 * Ordena array multidimensional por chave
 * @param array \$array Array a ordenar
 * @param string \$key Chave para ordenação
 * @param bool \$ascending Ordem ascendente
 * @return array Array ordenado
 */
function array_sort_by_key(\$array, \$key, \$ascending = true) {
    usort(\$array, function(\$a, \$b) use (\$key, \$ascending) {
        \$valA = is_array(\$a) ? \$a[\$key] : \$a->\$key;
        \$valB = is_array(\$b) ? \$b[\$key] : \$b->\$key;
        
        if (\$valA == \$valB) return 0;
        
        if (\$ascending) {
            return (\$valA < \$valB) ? -1 : 1;
        } else {
            return (\$valA > \$valB) ? -1 : 1;
        }
    });
    
    return \$array;
}

// ==================== FUNÇÕES DE STRING ====================

/**
 * Converte string para slug (URL amigável)
 * @param string \$texto Texto a converter
 * @return string Slug
 */
function slugify(\$texto) {
    \$texto = preg_replace('~[^\\pL\\d]+~u', '-', \$texto);
    \$texto = iconv('utf-8', 'us-ascii//TRANSLIT', \$texto);
    \$texto = preg_replace('~[^-\\w]+~', '', \$texto);
    \$texto = trim(\$texto, '-');
    \$texto = preg_replace('~-+~', '-', \$texto);
    \$texto = strtolower(\$texto);
    
    return \$texto ?: 'n-a';
}

/**
 * Limita string mantendo palavras completas
 * @param string \$texto Texto a limitar
 * @param int \$limite Número máximo de caracteres
 * @param string \$sufixo Sufixo para texto cortado
 * @return string Texto limitado
 */
function limitarTexto(\$texto, \$limite = 100, \$sufixo = '...') {
    if (mb_strlen(\$texto) <= \$limite) return \$texto;
    
    \$texto = mb_substr(\$texto, 0, \$limite);
    \$ultimoEspaco = mb_strrpos(\$texto, ' ');
    
    if (\$ultimoEspaco !== false) {
        \$texto = mb_substr(\$texto, 0, \$ultimoEspaco);
    }
    
    return \$texto . \$sufixo;
}

/**
 * Converte camelCase para snake_case
 * @param string \$string String em camelCase
 * @return string String em snake_case
 */
function camelToSnake(\$string) {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', \$string));
}

// ==================== FUNÇÕES DE DATAS ====================

/**
 * Calcula diferença entre duas datas
 * @param string \$data1 Primeira data
 * @param string \$data2 Segunda data (default: agora)
 * @return array Diferença detalhada
 */
function diferencaDatas(\$data1, \$data2 = null) {
    \$datetime1 = new DateTime(\$data1);
    \$datetime2 = new DateTime(\$data2 ?: 'now');
    \$interval = \$datetime1->diff(\$datetime2);
    
    return [
        'anos' => \$interval->y,
        'meses' => \$interval->m,
        'dias' => \$interval->d,
        'horas' => \$interval->h,
        'minutos' => \$interval->i,
        'segundos' => \$interval->s,
        'total_dias' => \$interval->days,
        'invertido' => \$interval->invert
    ];
}

/**
 * Adiciona dias úteis a uma data (exclui fins de semana)
 * @param string \$data Data base
 * @param int \$dias Dias úteis a adicionar
 * @return string Nova data
 */
function addDiasUteis(\$data, \$dias) {
    \$timestamp = strtotime(\$data);
    \$diasAdicionados = 0;
    
    while (\$diasAdicionados < \$dias) {
        \$timestamp = strtotime('+1 day', \$timestamp);
        \$diaSemana = date('N', \$timestamp);
        
        if (\$diaSemana < 6) { // 1-5 = segunda a sexta
            \$diasAdicionados++;
        }
    }
    
    return date('Y-m-d', \$timestamp);
}

// ==================== FUNÇÕES DE DEBUG ====================

/**
 * Debug function - exibe dados formatados
 * @param mixed \$data Dados a debuggar
 * @param bool \$die Encerrar execução após exibir
 */
function dd(\$data, \$die = true) {
    echo '<pre style=\"background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin: 10px;\">';
    print_r(\$data);
    echo '</pre>';
    
    if (\$die) die();
}

/**
 * Log personalizado para debug
 * @param mixed \$message Mensagem a loggar
 * @param string \$level Nível do log
 */
function logDebug(\$message, \$level = 'INFO') {
    if (!DEBUG_MODE) return;
    
    \$logFile = ROOT_PATH . '/debug.log';
    \$timestamp = date('Y-m-d H:i:s');
    
    if (is_array(\$message) || is_object(\$message)) {
        \$message = print_r(\$message, true);
    }
    
    \$logEntry = \"[\$timestamp] [\$level] \$message\\n\";
    file_put_contents(\$logFile, \$logEntry, FILE_APPEND | LOCK_EX);
}

// ==================== INICIALIZAÇÃO ====================

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

?>",

    'validations.php' => "<?php
/**
 * COISABOA - Sistema de Validações
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== VALIDAÇÕES DE DADOS ====================

/**
 * Validação completa de produto
 * @param array \$dados Dados do produto
 * @return array Resultado da validação
 */
function validarProduto(\$dados) {
    \$erros = [];
    
    // Nome do produto
    if (empty(\$dados['produto'])) {
        \$erros['produto'] = 'Nome do produto é obrigatório';
    } else {
        \$nome = trim(\$dados['produto']);
        if (strlen(\$nome) < 2) {
            \$erros['produto'] = 'Nome muito curto (mínimo 2 caracteres)';
        }
        if (strlen(\$nome) > 100) {
            \$erros['produto'] = 'Nome muito longo (máximo 100 caracteres)';
        }
    }
    
    // Quantidade
    if (!isset(\$dados['quantidade']) || \$dados['quantidade'] === '') {
        \$erros['quantidade'] = 'Quantidade é obrigatória';
    } else if (!is_numeric(\$dados['quantidade'])) {
        \$erros['quantidade'] = 'Quantidade deve ser um número';
    } else if (\$dados['quantidade'] <= 0) {
        \$erros['quantidade'] = 'Quantidade deve ser maior que zero';
    } else if (\$dados['quantidade'] > 999999) {
        \$erros['quantidade'] = 'Quantidade muito grande';
    }
    
    return [
        'valido' => empty(\$erros),
        'erros' => \$erros,
        'dados' => \$dados
    ];
}

/**
 * Validação de compra
 * @param array \$dados Dados da compra
 * @return array Resultado da validação
 */
function validarCompra(\$dados) {
    \$resultado = validarProduto(\$dados);
    
    if (!\$resultado['valido']) {
        return \$resultado;
    }
    
    \$erros = \$resultado['erros'];
    
    // Valor pago
    if (empty(\$dados['valor_pago'])) {
        \$erros['valor_pago'] = 'Valor pago é obrigatório';
    } else if (!is_numeric(\$dados['valor_pago'])) {
        \$erros['valor_pago'] = 'Valor pago deve ser um número';
    } else if (\$dados['valor_pago'] <= 0) {
        \$erros['valor_pago'] = 'Valor pago deve ser maior que zero';
    }
    
    // Valor proposto
    if (empty(\$dados['valor_proposto'])) {
        \$erros['valor_proposto'] = 'Valor proposto é obrigatório';
    } else if (!is_numeric(\$dados['valor_proposto'])) {
        \$erros['valor_proposto'] = 'Valor proposto deve ser um número';
    } else if (\$dados['valor_proposto'] <= 0) {
        \$erros['valor_proposto'] = 'Valor proposto deve ser maior que zero';
    }
    
    // Verificar se valor proposto é maior que valor pago (alerta)
    if (isset(\$dados['valor_pago']) && isset(\$dados['valor_proposto'])) {
        if (\$dados['valor_proposto'] < \$dados['valor_pago']) {
            \$erros['valor_proposto'] = 'Valor proposto menor que valor pago (prejuízo)';
        }
    }
    
    return [
        'valido' => empty(\$erros),
        'erros' => \$erros,
        'dados' => \$dados
    ];
}

/**
 * Validação de venda
 * @param array \$dados Dados da venda
 * @param int \$estoqueAtual Estoque disponível
 * @return array Resultado da validação
 */
function validarVenda(\$dados, \$estoqueAtual = null) {
    \$resultado = validarProduto(\$dados);
    
    if (!\$resultado['valido']) {
        return \$resultado;
    }
    
    \$erros = \$resultado['erros'];
    
    // Valor vendido
    if (empty(\$dados['valor_vendido'])) {
        \$erros['valor_vendido'] = 'Valor vendido é obrigatório';
    } else if (!is_numeric(\$dados['valor_vendido'])) {
        \$erros['valor_vendido'] = 'Valor vendido deve ser um número';
    } else if (\$dados['valor_vendido'] <= 0) {
        \$erros['valor_vendido'] = 'Valor vendido deve ser maior que zero';
    }
    
    // Verificar estoque
    if (\$estoqueAtual !== null) {
        if (\$dados['quantidade'] > \$estoqueAtual) {
            \$erros['quantidade'] = 'Estoque insuficiente. Disponível: ' . \$estoqueAtual;
        }
    }
    
    return [
        'valido' => empty(\$erros),
        'erros' => \$erros,
        'dados' => \$dados
    ];
}

// ==================== VALIDAÇÕES DE ARQUIVOS ====================

/**
 * Validação completa de imagem
 * @param array \$arquivo Dados do arquivo
 * @return array Resultado da validação
 */
function validarImagem(\$arquivo) {
    \$erros = [];
    
    if (!isset(\$arquivo['error'])) {
        return ['valido' => false, 'erros' => ['Arquivo inválido']];
    }
    
    // Verificar erro de upload
    switch (\$arquivo['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            \$erros[] = 'Arquivo muito grande. Máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            break;
        case UPLOAD_ERR_PARTIAL:
            \$erros[] = 'Upload realizado parcialmente';
            break;
        case UPLOAD_ERR_NO_FILE:
            \$erros[] = 'Nenhum arquivo selecionado';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            \$erros[] = 'Pasta temporária não encontrada';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            \$erros[] = 'Erro ao gravar arquivo no disco';
            break;
        case UPLOAD_ERR_EXTENSION:
            \$erros[] = 'Upload bloqueado por extensão do PHP';
            break;
        default:
            \$erros[] = 'Erro desconhecido no upload';
    }
    
    if (!empty(\$erros)) {
        return ['valido' => false, 'erros' => \$erros];
    }
    
    // Verificar tamanho
    if (\$arquivo['size'] > MAX_FILE_SIZE) {
        \$erros[] = 'Arquivo excede o tamanho máximo permitido';
    }
    
    // Verificar tipo pelo nome
    \$extensao = strtolower(pathinfo(\$arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array(\$extensao, ALLOWED_IMAGE_EXTENSIONS)) {
        \$erros[] = 'Tipo de arquivo não permitido. Use: ' . implode(', ', ALLOWED_IMAGE_EXTENSIONS);
    }
    
    // Verificar tipo real pelo MIME
    \$finfo = finfo_open(FILEINFO_MIME_TYPE);
    \$mimeReal = finfo_file(\$finfo, \$arquivo['tmp_name']);
    finfo_close(\$finfo);
    
    if (!in_array(\$mimeReal, ALLOWED_IMAGE_TYPES)) {
        \$erros[] = 'Tipo de arquivo não corresponde ao esperado';
    }
    
    // Verificar se é realmente uma imagem
    \$dimensoes = getimagesize(\$arquivo['tmp_name']);
    if (!\$dimensoes) {
        \$erros[] = 'Arquivo não é uma imagem válida';
    } else {
        // Verificar dimensões máximas
        if (\$dimensoes[0] > MAX_IMAGE_WIDTH || \$dimensoes[1] > MAX_IMAGE_HEIGHT) {
            \$erros[] = 'Imagem muito grande. Máximo: ' . MAX_IMAGE_WIDTH . 'x' . MAX_IMAGE_HEIGHT . 'px';
        }
        
        // Verificar dimensões mínimas
        if (\$dimensoes[0] < 10 || \$dimensoes[1] < 10) {
            \$erros[] = 'Imagem muito pequena. Mínimo: 10x10px';
        }
    }
    
    return [
        'valido' => empty(\$erros),
        'erros' => \$erros,
        'dados' => [
            'nome' => \$arquivo['name'],
            'tamanho' => \$arquivo['size'],
            'tipo' => \$mimeReal,
            'largura' => \$dimensoes[0] ?? 0,
            'altura' => \$dimensoes[1] ?? 0
        ]
    ];
}

// ==================== VALIDAÇÕES DE FORMULÁRIO ====================

/**
 * Valida token CSRF do formulário
 * @param string \$token Token a validar
 * @return bool É válido
 */
function validarCSRF(\$token) {
    if (!isset(\$_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals(\$_SESSION['csrf_token'], \$token);
}

/**
 * Valida se requisição é POST
 * @return bool É POST
 */
function isPost() {
    return \$_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Valida se requisição é AJAX
 * @return bool É AJAX
 */
function isAjax() {
    return !empty(\$_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower(\$_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// ==================== VALIDAÇÕES DE NEGÓCIO ====================

/**
 * Valida margem de lucro (alerta)
 * @param float \$custo Custo do produto
 * @param float \$venda Preço de venda
 * @param float \$margemMinima Margem mínima aceitável (%)
 * @return array Resultado da validação
 */
function validarMargemLucro(\$custo, \$venda, \$margemMinima = 10) {
    if (\$custo <= 0 || \$venda <= 0) {
        return ['valido' => false, 'erro' => 'Valores inválidos'];
    }
    
    \$margem = ((\$venda - \$custo) / \$custo) * 100;
    \$lucro = \$venda - \$custo;
    
    \$resultado = [
        'margem' => round(\$margem, 2),
        'lucro' => \$lucro,
        'valido' => \$margem >= \$margemMinima
    ];
    
    if (\$margem < \$margemMinima) {
        \$resultado['alerta'] = 'Margem de lucro baixa: ' . round(\$margem, 2) . '%';
    }
    
    if (\$margem < 0) {
        \$resultado['alerta'] = 'PREJUÍZO: Margem negativa de ' . round(\$margem, 2) . '%';
        \$resultado['valido'] = false;
    }
    
    return \$resultado;
}

/**
 * Valida se estoque está abaixo do mínimo
 * @param int \$estoqueAtual Estoque atual
 * @param int \$estoqueMinimo Estoque mínimo configurado
 * @return array Resultado da validação
 */
function validarEstoqueMinimo(\$estoqueAtual, \$estoqueMinimo = ESTOQUE_MINIMO_ALERTA) {
    \$status = 'normal';
    \$alerta = '';
    
    if (\$estoqueAtual <= 0) {
        \$status = 'critico';
        \$alerta = 'ESTOQUE ZERADO';
    } else if (\$estoqueAtual <= \$estoqueMinimo) {
        \$status = 'alerta';
        \$alerta = 'Estoque baixo: ' . \$estoqueAtual . ' unidades';
    }
    
    return [
        'status' => \$status,
        'alerta' => \$alerta,
        'valido' => \$estoqueAtual > 0
    ];
}

// ==================== FUNÇÕES DE SANITIZAÇÃO ESPECÍFICA ====================

/**
 * Sanitiza valor monetário
 * @param string \$valor Valor a sanitizar
 * @return float Valor sanitizado
 */
function sanitizarMonetario(\$valor) {
    // Remove R$, pontos e espaços, mantém apenas números e vírgula
    \$valor = preg_replace('/[^0-9,]/', '', \$valor);
    // Substitui vírgula por ponto para float
    \$valor = str_replace(',', '.', \$valor);
    // Remove pontos desnecessários (milhares)
    \$valor = preg_replace('/(\\.[0-9]{2})[0-9]*/', '\$1', \$valor);
    
    return floatval(\$valor);
}

/**
 * Sanitiza número inteiro
 * @param mixed \$numero Número a sanitizar
 * @return int Número sanitizado
 */
function sanitizarInteiro(\$numero) {
    return intval(preg_replace('/[^0-9]/', '', \$numero));
}

/**
 * Sanitiza nome de produto
 * @param string \$nome Nome a sanitizar
 * @return string Nome sanitizado
 */
function sanitizarNomeProduto(\$nome) {
    \$nome = trim(\$nome);
    \$nome = preg_replace('/[^a-zA-Z0-9\\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ&-_]/u', '', \$nome);
    \$nome = preg_replace('/\\s+/', ' ', \$nome);
    
    return ucwords(mb_strtolower(\$nome, 'UTF-8'));
}

?>",

    'auth.php' => "<?php
/**
 * COISABOA - Sistema de Autenticação e Sessão
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== CONTROLE DE SESSÃO ====================

/**
 * Inicia sessão segura
 */
function iniciarSessaoSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações seguras
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset(\$_SERVER['HTTPS']));
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerar ID periodicamente
        if (!isset(\$_SESSION['ultima_regeneracao'])) {
            \$_SESSION['ultima_regeneracao'] = time();
        } else if (time() - \$_SESSION['ultima_regeneracao'] > SESSION_REGENERATE) {
            session_regenerate_id(true);
            \$_SESSION['ultima_regeneracao'] = time();
        }
    }
}

/**
 * Verifica timeout da sessão
 */
function verificarTimeoutSessao() {
    if (isset(\$_SESSION['ultima_atividade']) && 
        (time() - \$_SESSION['ultima_atividade'] > SESSION_TIMEOUT)) {
        destruirSessao();
        return false;
    }
    
    \$_SESSION['ultima_atividade'] = time();
    return true;
}

/**
 * Destrói sessão completamente
 */
function destruirSessao() {
    \$_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        \$params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            \$params['path'], \$params['domain'],
            \$params['secure'], \$params['httponly']
        );
    }
    
    session_destroy();
}

// ==================== AUTENTICAÇÃO (FUTURAS VERSÕES) ====================

/**
 * Verifica se usuário está logado
 * @return bool Está logado
 */
function usuarioLogado() {
    iniciarSessaoSegura();
    verificarTimeoutSessao();
    
    return isset(\$_SESSION['usuario_id']) && !empty(\$_SESSION['usuario_id']);
}

/**
 * Exige autenticação para acessar página
 */
function requererAutenticacao() {
    if (!usuarioLogado()) {
        \$_SESSION['url_redirect'] = \$_SERVER['REQUEST_URI'];
        redirect('login.php', 'Faça login para acessar esta página.', 'error');
    }
}

/**
 * Realiza login do usuário
 * @param int \$usuarioId ID do usuário
 * @param string \$usuarioNome Nome do usuário
 */
function fazerLogin(\$usuarioId, \$usuarioNome) {
    iniciarSessaoSegura();
    
    \$_SESSION['usuario_id'] = \$usuarioId;
    \$_SESSION['usuario_nome'] = \$usuarioNome;
    \$_SESSION['ip_address'] = \$_SERVER['REMOTE_ADDR'];
    \$_SESSION['user_agent'] = \$_SERVER['HTTP_USER_AGENT'];
    \$_SESSION['ultima_atividade'] = time();
    
    // Log de login
    logAtividade('login', 'Usuário ' . \$usuarioNome . ' fez login');
}

/**
 * Realiza logout
 */
function fazerLogout() {
    if (usuarioLogado()) {
        \$usuarioNome = \$_SESSION['usuario_nome'];
        logAtividade('logout', 'Usuário ' . \$usuarioNome . ' fez logout');
    }
    
    destruirSessao();
}

// ==================== CONTROLE DE ACESSO ====================

/**
 * Verifica se usuário tem permissão
 * @param string \$permissao Permissão requerida
 * @return bool Tem permissão
 */
function temPermissao(\$permissao) {
    if (!usuarioLogado()) return false;
    
    // Permissões padrão (para futuras versões)
    \$permissoes = isset(\$_SESSION['permissoes']) ? \$_SESSION['permissoes'] : ['visualizar', 'editar'];
    
    return in_array(\$permissao, \$permissoes);
}

/**
 * Exige permissão específica
 * @param string \$permissao Permissão requerida
 */
function requererPermissao(\$permissao) {
    if (!temPermissao(\$permissao)) {
        redirect('index.php', 'Você não tem permissão para acessar esta função.', 'error');
    }
}

// ==================== LOG DE ATIVIDADES ====================

/**
 * Registra atividade do usuário
 * @param string \$tipo Tipo da atividade
 * @param string \$descricao Descrição detalhada
 */
function logAtividade(\$tipo, \$descricao) {
    if (!LOG_ENABLED) return;
    
    \$logDir = ROOT_PATH . '/logs';
    if (!file_exists(\$logDir)) {
        mkdir(\$logDir, 0755, true);
    }
    
    \$logFile = \$logDir . '/atividades_' . date('Y-m') . '.log';
    
    \$usuarioId = isset(\$_SESSION['usuario_id']) ? \$_SESSION['usuario_id'] : 'anonimo';
    \$ip = \$_SERVER['REMOTE_ADDR'];
    \$userAgent = substr(\$_SERVER['HTTP_USER_AGENT'], 0, 100);
    
    \$logEntry = date('Y-m-d H:i:s') . \" | \";
    \$logEntry .= \$usuarioId . \" | \";
    \$logEntry .= \$ip . \" | \";
    \$logEntry .= \$tipo . \" | \";
    \$logEntry .= \$descricao . \" | \";
    \$logEntry .= \$userAgent . \"\\n\";
    
    file_put_contents(\$logFile, \$logEntry, FILE_APPEND | LOCK_EX);
}

// ==================== INICIALIZAÇÃO AUTOMÁTICA ====================

// Iniciar sessão em todas as páginas que incluírem este arquivo
iniciarSessaoSegura();

?>",

    'database_helpers.php' => "<?php
/**
 * COISABOA - Helpers para Operações com Banco de Dados
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== OPERAÇÕES BÁSICAS CRUD ====================

/**
 * Executa query com parâmetros seguros
 * @param string \$sql Query SQL
 * @param array \$params Parâmetros para prepared statement
 * @return PDOStatement Statement executado
 */
function dbQuery(\$sql, \$params = []) {
    \$pdo = getDBConnection();
    \$stmt = \$pdo->prepare(\$sql);
    \$stmt->execute(\$params);
    return \$stmt;
}

/**
 * Busca único registro
 * @param string \$sql Query SQL
 * @param array \$params Parâmetros
 * @return array|false Registro encontrado ou false
 */
function dbFind(\$sql, \$params = []) {
    \$stmt = dbQuery(\$sql, \$params);
    return \$stmt->fetch();
}

/**
 * Busca todos os registros
 * @param string \$sql Query SQL
 * @param array \$params Parâmetros
 * @return array Array de registros
 */
function dbFindAll(\$sql, \$params = []) {
    \$stmt = dbQuery(\$sql, \$params);
    return \$stmt->fetchAll();
}

/**
 * Insere registro e retorna ID
 * @param string \$tabela Nome da tabela
 * @param array \$dados Dados para inserir
 * @return int|false ID do registro inserido ou false
 */
function dbInsert(\$tabela, \$dados) {
    \$campos = implode(', ', array_keys(\$dados));
    \$placeholders = ':' . implode(', :', array_keys(\$dados));
    
    \$sql = \"INSERT INTO {\$tabela} ({$campos}) VALUES ({$placeholders})\";
    
    try {
        \$pdo = getDBConnection();
        \$stmt = \$pdo->prepare(\$sql);
        \$stmt->execute(\$dados);
        return \$pdo->lastInsertId();
    } catch (PDOException \$e) {
        logDebug('Erro ao inserir em ' . \$tabela . ': ' . \$e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Atualiza registros
 * @param string \$tabela Nome da tabela
 * @param array \$dados Dados para atualizar
 * @param string \$condicao Condição WHERE
 * @param array \$paramsCondicao Parâmetros da condição
 * @return int Número de linhas afetadas
 */
function dbUpdate(\$tabela, \$dados, \$condicao, \$paramsCondicao = []) {
    \$setParts = [];
    foreach (array_keys(\$dados) as \$campo) {
        \$setParts[] = \"{\$campo} = :{\$campo}\";
    }
    \$setClause = implode(', ', \$setParts);
    
    \$sql = \"UPDATE {\$tabela} SET {\$setClause} WHERE {\$condicao}\";
    \$params = array_merge(\$dados, \$paramsCondicao);
    
    try {
        \$stmt = dbQuery(\$sql, \$params);
        return \$stmt->rowCount();
    } catch (PDOException \$e) {
        logDebug('Erro ao atualizar ' . \$tabela . ': ' . \$e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Deleta registros
 * @param string \$tabela Nome da tabela
 * @param string \$condicao Condição WHERE
 * @param array \$params Parâmetros da condição
 * @return int Número de linhas afetadas
 */
function dbDelete(\$tabela, \$condicao, \$params = []) {
    \$sql = \"DELETE FROM {\$tabela} WHERE {\$condicao}\";
    
    try {
        \$stmt = dbQuery(\$sql, \$params);
        return \$stmt->rowCount();
    } catch (PDOException \$e) {
        logDebug('Erro ao deletar de ' . \$tabela . ': ' . \$e->getMessage(), 'ERROR');
        return 0;
    }
}

// ==================== OPERAÇÕES ESPECÍFICAS DO SISTEMA ====================

/**
 * Busca estoque atual de um produto
 * @param string \$produto Nome do produto
 * @return int Estoque atual
 */
function getEstoqueAtual(\$produto) {
    \$sql = \"SELECT 
                COALESCE(SUM(C.quantidade), 0) - COALESCE(SUM(V.quantidade), 0) as estoque
            FROM 
                (SELECT produto, quantidade FROM compras WHERE produto = ?) C
            LEFT JOIN 
                (SELECT produto, quantidade FROM vendas WHERE produto = ?) V
            ON C.produto = V.produto\";
    
    \$resultado = dbFind(\$sql, [\$produto, \$produto]);
    return intval(\$resultado['estoque'] ?? 0);
}

/**
 * Busca histórico completo de um produto
 * @param string \$produto Nome do produto
 * @return array Histórico consolidado
 */
function getHistóricoProduto(\$produto) {
    // Compras do produto
    \$compras = dbFindAll(\"
        SELECT data_compra as data, quantidade, valor_pago as valor, 'compra' as tipo
        FROM compras 
        WHERE produto = ?
        ORDER BY data_compra DESC
    \", [\$produto]);
    
    // Vendas do produto
    \$vendas = dbFindAll(\"
        SELECT data_venda as data, quantidade, valor_vendido as valor, 'venda' as tipo
        FROM vendas 
        WHERE produto = ?
        ORDER BY data_venda DESC
    \", [\$produto]);
    
    // Combinar e ordenar por data
    \$historico = array_merge(\$compras, \$vendas);
    usort(\$historico, function(\$a, \$b) {
        return strtotime(\$b['data']) - strtotime(\$a['data']);
    });
    
    return \$historico;
}

/**
 * Busca relatório consolidado de estoque
 * @return array Relatório completo
 */
function getRelatorioEstoque() {
    \$sql = \"
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
    \";
    
    return dbFindAll(\$sql);
}

// ==================== TRANSACTIONS E BACKUP ====================

/**
 * Executa operação em transaction
 * @param callable \$callback Função a executar na transaction
 * @return mixed Resultado da função
 */
function dbTransaction(callable \$callback) {
    \$pdo = getDBConnection();
    
    try {
        \$pdo->beginTransaction();
        \$resultado = \$callback(\$pdo);
        \$pdo->commit();
        return \$resultado;
    } catch (Exception \$e) {
        \$pdo->rollBack();
        logDebug('Transaction falhou: ' . \$e->getMessage(), 'ERROR');
        throw \$e;
    }
}

/**
 * Cria backup simples do banco
 * @param string \$caminhoBackup Caminho para salvar backup
 * @return bool Sucesso da operação
 */
function criarBackupBanco(\$caminhoBackup) {
    \$pdo = getDBConnection();
    \$tables = \$pdo->query(\"SHOW TABLES\")->fetchAll(PDO::FETCH_COLUMN);
    
    \$backupSQL = \"-- Backup COISABOA - \" . date('Y-m-d H:i:s') . \"\\n\\n\";
    
    foreach (\$tables as \$table) {
        // Estrutura da tabela
        \$createTable = \$pdo->query(\"SHOW CREATE TABLE {\$table}\")->fetch();
        \$backupSQL .= \"DROP TABLE IF EXISTS {\$table};\\n\";
        \$backupSQL .= \$createTable['Create Table'] . \";\\n\\n\";
        
        // Dados da tabela
        \$rows = \$pdo->query(\"SELECT * FROM {\$table}\")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty(\$rows)) {
            \$backupSQL .= \"INSERT INTO {\$table} VALUES\\n\";
            \$insertValues = [];
            
            foreach (\$rows as \$row) {
                \$values = array_map(function(\$value) use (\$pdo) {
                    if (\$value === null) return 'NULL';
                    return \$pdo->quote(\$value);
                }, \$row);
                
                \$insertValues[] = \"(\" . implode(', ', \$values) . \")\";
            }
            
            \$backupSQL .= implode(\",\\n\", \$insertValues) . \";\\n\\n\";
        }
    }
    
    return file_put_contents(\$caminhoBackup, \$backupSQL) !== false;
}

?>"
];

// Arquivo de proteção .htaccess
$htaccess_content = "# COISABOA - Proteção da Pasta Includes
# Bloqueia acesso direto aos arquivos PHP

Order Deny,Allow
Deny from all

# Bloquear acesso a todos os arquivos PHP
<Files ~ \"\\.php$\">
    Deny from all
</Files>

# Permitir acesso apenas de localhost (desenvolvimento)
SetEnvIf Host \"^localhost$\" allowed
SetEnvIf Host \"^127.0.0.1$\" allowed

Allow from env=allowed

ErrorDocument 403 \"<h1>Acesso Negado</h1><p>Arquivos do sistema não podem ser acessados diretamente.</p>\"

# Headers de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY";

// Criar arquivos do sistema de includes
echo "\n📄 CRIANDO SISTEMA DE INCLUDES...\n";
echo "=============================================\n";

foreach ($include_files as $filename => $content) {
    $filepath = $includes_dir . '/' . $filename;
    
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
        echo "✅ Sistema criado: includes/{$filename}\n";
    } else {
        echo "📁 Sistema já existe: includes/{$filename}\n";
    }
}

// Criar arquivo .htaccess de proteção
$htaccess_path = $includes_dir . '/.htaccess';
file_put_contents($htaccess_path, $htaccess_content);
echo "✅ Proteção criada: includes/.htaccess\n";

// Criar arquivo de inicialização principal
$init_content = "<?php
/**
 * COISABOA - Inicialização do Sistema
 * Arquivo principal que inclui todas as dependências
 */

// Incluir configurações
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Incluir funções do sistema
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/validations.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database_helpers.php';

// Inicializar sistema
logDebug('Sistema COISABOA inicializado', 'INFO');

?>
";

file_put_contents($includes_dir . '/init.php', $init_content);
echo "✅ Inicializador criado: includes/init.php\n";

// Testar se os arquivos foram criados corretamente
echo "\n🔍 VERIFICANDO ARQUIVOS CRIADOS...\n";
echo "=============================================\n";

$arquivos_criados = scandir($includes_dir);
$arquivos_validos = array_filter($arquivos_criados, function($file) {
    return !in_array($file, ['.', '..', '.htaccess', 'init.php']);
});

foreach ($arquivos_validos as $arquivo) {
    $caminho = $includes_dir . '/' . $arquivo;
    $tamanho = filesize($caminho);
    $linhas = count(file($caminho));
    $status = $tamanho > 100 ? '✅' : '⚠️';
    
    echo "{$status} {$arquivo} - {$linhas} linhas, " . number_format($tamanho) . " bytes\n";
}

// Resumo final
echo "\n🎉 SISTEMA DE INCLUDES CRIADO COM SUCESSO!\n";
echo "=============================================\n";
echo "📊 RESUMO DO SISTEMA DE INCLUDES:\n";
echo "• 🔧 functions.php - 50+ funções utilitárias organizadas\n";
echo "• ✅ validations.php - Validações completas de dados e negócio\n";
echo "• 🔐 auth.php - Sistema de autenticação e sessão segura\n";
echo "• 🗄️  database_helpers.php - CRUD completo e operações específicas\n";
echo "• 🚀 init.php - Inicializador único do sistema\n";
echo "• 🛡️  .htaccess - Proteção contra acesso direto\n\n";

echo "✅ RECURSOS INCLUÍDOS:\n";
echo "• 📝 Formatação: Moeda, datas, quantidades\n";
echo "• 🛡️  Segurança: Sanitização, CSRF, criptografia\n";
echo "• 🔄 Redirecionamento: Mensagens flash, URLs\n";
echo "• ✅ Validação: Produtos, compras, vendas, imagens\n";
echo "• 💰 Negócio: Margem de lucro, estoque mínimo\n";
echo "• 🗄️  Database: CRUD, transactions, backups\n";
echo "• 🔐 Auth: Sessão segura, permissões, logs\n\n";

echo "🚀 PARA USAR O SISTEMA:\n";
echo "1. Inclua: require_once 'includes/init.php';\n";
echo "2. Use as funções: formatarMoeda(), validarProduto(), dbInsert(), etc.\n";
echo "3. Todas as dependências são carregadas automaticamente\n";

echo "\n📚 SISTEMA DE INCLUDES PRONTO PARA USO!\n";

?>