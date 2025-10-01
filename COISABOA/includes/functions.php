<?php
/**
 * COISABOA - Funções Utilitárias do Sistema
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== FUNÇÕES DE FORMATAÇÃO ====================

/**
 * Formata valor monetário para exibição
 * @param float $valor Valor a ser formatado
 * @param bool $simbolo Incluir símbolo R$
 * @return string Valor formatado
 */
function formatarMoeda($valor, $simbolo = true) {
    if (!is_numeric($valor)) return 'R$ 0,00';
    
    $formatado = number_format(floatval($valor), 2, ',', '.');
    return $simbolo ? 'R$ ' . $formatado : $formatado;
}

/**
 * Formata data para exibição amigável
 * @param string $data Data em formato MySQL ou timestamp
 * @param bool $hora Incluir hora
 * @return string Data formatada
 */
function formatarData($data, $hora = true) {
    if (empty($data) || $data == '0000-00-00 00:00:00') return '-';
    
    $timestamp = is_numeric($data) ? $data : strtotime($data);
    
    if ($hora) {
        return date('d/m/Y H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Formata data relativa (há x tempo)
 * @param string $data Data para calcular diferença
 * @return string Texto relativo
 */
function formatarDataRelativa($data) {
    $agora = time();
    $timestamp = is_numeric($data) ? $data : strtotime($data);
    $diferenca = $agora - $timestamp;
    
    if ($diferenca < 60) return 'agora há pouco';
    if ($diferenca < 3600) return floor($diferenca/60) . ' min atrás';
    if ($diferenca < 86400) return floor($diferenca/3600) . ' h atrás';
    if ($diferenca < 2592000) return floor($diferenca/86400) . ' dias atrás';
    
    return formatarData($data);
}

/**
 * Formata número para quantidade
 * @param mixed $quantidade Quantidade a formatar
 * @return string Quantidade formatada
 */
function formatarQuantidade($quantidade) {
    if (!is_numeric($quantidade)) return '0';
    
    $quantidade = floatval($quantidade);
    
    if ($quantidade == intval($quantidade)) {
        return number_format($quantidade, 0, ',', '.');
    } else {
        return number_format($quantidade, 2, ',', '.');
    }
}

/**
 * Formata bytes para tamanho legível
 * @param int $bytes Bytes a formatar
 * @param int $decimas Casas decimais
 * @return string Tamanho formatado
 */
function formatarTamanhoArquivo($bytes, $decimais = 2) {
    $tamanhos = ['B', 'KB', 'MB', 'GB', 'TB'];
    $fator = floor((strlen($bytes) - 1) / 3);
    
    if ($fator == 0) return $bytes . ' ' . $tamanhos[$fator];
    
    return sprintf('%.' . $decimais . 'f', $bytes / pow(1024, $fator)) . ' ' . $tamanhos[$fator];
}

// ==================== FUNÇÕES DE SEGURANÇA ====================

/**
 * Sanitiza dados para prevenir XSS
 * @param mixed $dados Dados a sanitizar
 * @return mixed Dados sanitizados
 */
function sanitizar($dados) {
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    
    if (is_object($dados)) {
        $props = get_object_vars($dados);
        foreach ($props as $key => $value) {
            $dados->$key = sanitizar($value);
        }
        return $dados;
    }
    
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

/**
 * Remove caracteres especiais para uso em URLs/arquivos
 * @param string $texto Texto a limpar
 * @return string Texto limpo
 */
function sanitizarNomeArquivo($texto) {
    $texto = preg_replace('/[^a-zA-Z0-9\s]/', '', $texto);
    $texto = str_replace(' ', '_', $texto);
    $texto = preg_replace('/_{2,}/', '_', $texto);
    return strtolower($texto);
}

/**
 * Gera token CSRF para forms
 * @return string Token CSRF
 */
function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 * @param string $token Token a validar
 * @return bool É válido
 */
function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Criptografa dados sensíveis (para futuras versões)
 * @param string $dados Dados a criptografar
 * @return string Dados criptografados
 */
function criptografar($dados) {
    return base64_encode($dados); // Implementação básica
}

/**
 * Descriptografa dados
 * @param string $dados Dados criptografados
 * @return string Dados originais
 */
function descriptografar($dados) {
    return base64_decode($dados);
}

// ==================== FUNÇÕES DE REDIRECIONAMENTO ====================

/**
 * Redireciona com mensagem flash
 * @param string $url URL para redirecionar
 * @param string $mensagem Mensagem para exibir
 * @param string $tipo Tipo da mensagem (success, error, warning, info)
 */
function redirect($url, $mensagem = null, $tipo = 'info') {
    if ($mensagem) {
        $_SESSION['flash_messages'][] = [
            'texto' => $mensagem,
            'tipo' => $tipo,
            'timestamp' => time()
        ];
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Recupera e limpa mensagens flash
 * @return array Mensagens flash
 */
function getFlashMessages() {
    $mensagens = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    unset($_SESSION['flash_messages']);
    return $mensagens;
}

/**
 * Exibe mensagens flash formatadas
 */
function exibirFlashMessages() {
    $mensagens = getFlashMessages();
    
    if (empty($mensagens)) return '';
    
    $html = '<div class="flash-messages">';
    
    foreach ($mensagens as $msg) {
        $classe = 'flash-' . $msg['tipo'];
        $html .= '<div class="flash-message ' . $classe . '">';
        $html .= '<span class="flash-text">' . $msg['texto'] . '</span>';
        $html .= '<small class="flash-time">' . formatarDataRelativa($msg['timestamp']) . '</small>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// ==================== FUNÇÕES DE VALIDAÇÃO RÁPIDA ====================

/**
 * Verifica se é email válido
 * @param string $email Email a validar
 * @return bool É válido
 */
function isEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verifica se é número inteiro positivo
 * @param mixed $numero Número a validar
 * @return bool É válido
 */
function isInteiroPositivo($numero) {
    return filter_var($numero, FILTER_VALIDATE_INT) !== false && $numero > 0;
}

/**
 * Verifica se é valor monetário válido
 * @param mixed $valor Valor a validar
 * @return bool É válido
 */
function isMonetario($valor) {
    return is_numeric($valor) && $valor >= 0;
}

/**
 * Verifica se é URL válida
 * @param string $url URL a validar
 * @return bool É válida
 */
function isURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ==================== FUNÇÕES DE ARRAY/OBJETO ====================

/**
 * Recupera valor seguro de array com fallback
 * @param array $array Array source
 * @param string $key Chave a buscar
 * @param mixed $default Valor padrão
 * @return mixed Valor encontrado ou padrão
 */
function array_get($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Filtra array mantendo apenas chaves específicas
 * @param array $array Array original
 * @param array $keys Chaves a manter
 * @return array Array filtrado
 */
function array_only($array, $keys) {
    return array_intersect_key($array, array_flip($keys));
}

/**
 * Ordena array multidimensional por chave
 * @param array $array Array a ordenar
 * @param string $key Chave para ordenação
 * @param bool $ascending Ordem ascendente
 * @return array Array ordenado
 */
function array_sort_by_key($array, $key, $ascending = true) {
    usort($array, function($a, $b) use ($key, $ascending) {
        $valA = is_array($a) ? $a[$key] : $a->$key;
        $valB = is_array($b) ? $b[$key] : $b->$key;
        
        if ($valA == $valB) return 0;
        
        if ($ascending) {
            return ($valA < $valB) ? -1 : 1;
        } else {
            return ($valA > $valB) ? -1 : 1;
        }
    });
    
    return $array;
}

// ==================== FUNÇÕES DE STRING ====================

/**
 * Converte string para slug (URL amigável)
 * @param string $texto Texto a converter
 * @return string Slug
 */
function slugify($texto) {
    $texto = preg_replace('~[^\pL\d]+~u', '-', $texto);
    $texto = iconv('utf-8', 'us-ascii//TRANSLIT', $texto);
    $texto = preg_replace('~[^-\w]+~', '', $texto);
    $texto = trim($texto, '-');
    $texto = preg_replace('~-+~', '-', $texto);
    $texto = strtolower($texto);
    
    return $texto ?: 'n-a';
}

/**
 * Limita string mantendo palavras completas
 * @param string $texto Texto a limitar
 * @param int $limite Número máximo de caracteres
 * @param string $sufixo Sufixo para texto cortado
 * @return string Texto limitado
 */
function limitarTexto($texto, $limite = 100, $sufixo = '...') {
    if (mb_strlen($texto) <= $limite) return $texto;
    
    $texto = mb_substr($texto, 0, $limite);
    $ultimoEspaco = mb_strrpos($texto, ' ');
    
    if ($ultimoEspaco !== false) {
        $texto = mb_substr($texto, 0, $ultimoEspaco);
    }
    
    return $texto . $sufixo;
}

/**
 * Converte camelCase para snake_case
 * @param string $string String em camelCase
 * @return string String em snake_case
 */
function camelToSnake($string) {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
}

// ==================== FUNÇÕES DE DATAS ====================

/**
 * Calcula diferença entre duas datas
 * @param string $data1 Primeira data
 * @param string $data2 Segunda data (default: agora)
 * @return array Diferença detalhada
 */
function diferencaDatas($data1, $data2 = null) {
    $datetime1 = new DateTime($data1);
    $datetime2 = new DateTime($data2 ?: 'now');
    $interval = $datetime1->diff($datetime2);
    
    return [
        'anos' => $interval->y,
        'meses' => $interval->m,
        'dias' => $interval->d,
        'horas' => $interval->h,
        'minutos' => $interval->i,
        'segundos' => $interval->s,
        'total_dias' => $interval->days,
        'invertido' => $interval->invert
    ];
}

/**
 * Adiciona dias úteis a uma data (exclui fins de semana)
 * @param string $data Data base
 * @param int $dias Dias úteis a adicionar
 * @return string Nova data
 */
function addDiasUteis($data, $dias) {
    $timestamp = strtotime($data);
    $diasAdicionados = 0;
    
    while ($diasAdicionados < $dias) {
        $timestamp = strtotime('+1 day', $timestamp);
        $diaSemana = date('N', $timestamp);
        
        if ($diaSemana < 6) { // 1-5 = segunda a sexta
            $diasAdicionados++;
        }
    }
    
    return date('Y-m-d', $timestamp);
}

// ==================== FUNÇÕES DE DEBUG ====================

/**
 * Debug function - exibe dados formatados
 * @param mixed $data Dados a debuggar
 * @param bool $die Encerrar execução após exibir
 */
function dd($data, $die = true) {
    echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin: 10px;">';
    print_r($data);
    echo '</pre>';
    
    if ($die) die();
}

/**
 * Log personalizado para debug
 * @param mixed $message Mensagem a loggar
 * @param string $level Nível do log
 */
function logDebug($message, $level = 'INFO') {
    if (!DEBUG_MODE) return;
    
    $logFile = ROOT_PATH . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// ==================== INICIALIZAÇÃO ====================

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

?>