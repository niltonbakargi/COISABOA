<?php
// Arquivo para visualizar imagem sem extensão
$imagem_path = 'uploads/produtos/produto_1759157805_68da9e2d51c48.PNG';

// Função para detectar o tipo de imagem pelo conteúdo
function detectarTipoImagem($caminho) {
    if (!file_exists($caminho)) return false;
    
    $tamanho = getimagesize($caminho);
    if ($tamanho === false) return false;
    
    $tipos = [
        1 => 'image/gif',
        2 => 'image/jpeg', 
        3 => 'image/png',
        6 => 'image/bmp',
        17 => 'image/ico',
        18 => 'image/webp'
    ];
    
    return $tipos[$tamanho[2]] ?? false;
}

// Verificar se o arquivo existe (sem extensão)
if (file_exists($imagem_path)) {
    $tipo = detectarTipoImagem($imagem_path);
    
    if ($tipo) {
        // Mostrar a imagem
        header('Content-Type: ' . $tipo);
        readfile($imagem_path);
        exit;
    } else {
        // Se não conseguiu detectar o tipo
        header('Content-Type: image/svg+xml');
        echo '<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#f0f0f0"/>
            <text x="50%" y="40%" text-anchor="middle" font-family="Arial" font-size="14" fill="#666">
                Arquivo encontrado mas tipo não detectado
            </text>
            <text x="50%" y="60%" text-anchor="middle" font-family="Arial" font-size="12" fill="#999">
                ' . htmlspecialchars(basename($imagem_path)) . '
            </text>
        </svg>';
        exit;
    }
} else {
    // Arquivo não encontrado
    header('Content-Type: image/svg+xml');
    echo '<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#ffebee"/>
        <text x="50%" y="40%" text-anchor="middle" font-family="Arial" font-size="14" fill="#c53030">
            ❌ Arquivo não encontrado
        </text>
        <text x="50%" y="60%" text-anchor="middle" font-family="Arial" font-size="12" fill="#e53e3e">
            ' . htmlspecialchars($imagem_path) . '
        </text>
    </svg>';
    exit;
}
?>