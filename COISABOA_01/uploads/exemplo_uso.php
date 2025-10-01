<?php
/**
 * COISABOA - Exemplo de Uso do Sistema de Upload
 */

require_once '../config/constants.php';
require_once 'upload_functions.php';

// Exemplo 1: Upload simples
if ($_FILES['foto_vendedor']) {
    $resultado = uploadImagem($_FILES['foto_vendedor'], 'vendors');
    
    if ($resultado['sucesso']) {
        echo 'Upload realizado: ' . $resultado['caminho'];
        // Salvar $resultado['arquivo'] no banco
    } else {
        echo 'Erros: ' . implode(', ', $resultado['erros']);
    }
}

// Exemplo 2: Exibir imagem
$foto = 'nome_arquivo.jpg';
echo '<img src="' . exibirImagem($foto, 'vendors', true) . '" alt="Foto">';

// Exemplo 3: Limpeza automática
$arquivosRemovidos = limparUploadsTemporarios(24);
echo 'Arquivos temporários removidos: ' . $arquivosRemovidos;
?>