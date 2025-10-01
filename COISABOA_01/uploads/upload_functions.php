<?php
/**
 * COISABOA - Funções de Upload Simplificadas
 * @version 1.0.0
 */

/**
 * Função simplificada para upload de imagens
 */
function uploadImagem($arquivo, $pasta = 'vendors') {
    require_once UPLOAD_PATH . '/Uploader.php';
    
    $uploader = new Uploader();
    $resultado = $uploader->uploadImage($arquivo, $pasta);
    
    if ($resultado) {
        return [
            'sucesso' => true,
            'arquivo' => $resultado,
            'caminho' => 'uploads/' . $pasta . '/' . $resultado
        ];
    } else {
        return [
            'sucesso' => false,
            'erros' => $uploader->getErrors()
        ];
    }
}

/**
 * Exibe imagem com fallback para placeholder
 */
function exibirImagem($nomeArquivo, $pasta = 'vendors', $thumbnail = false) {
    require_once UPLOAD_PATH . '/Uploader.php';
    
    $uploader = new Uploader();
    $caminho = $uploader->displayImage($nomeArquivo, $pasta);
    
    if ($thumbnail) {
        // Criar thumbnail sob demanda
        $sourcePath = UPLOAD_PATH . '/' . $pasta . '/' . $nomeArquivo;
        $thumbPath = UPLOAD_PATH . '/temp/thumb_' . $nomeArquivo;
        
        if (!file_exists($thumbPath)) {
            require_once UPLOAD_PATH . '/ImageProcessor.php';
            $processor = new ImageProcessor();
            $processor->createThumbnail($sourcePath, $thumbPath, 150, 150);
        }
        
        if (file_exists($thumbPath)) {
            $caminho = 'uploads/temp/thumb_' . $nomeArquivo;
        }
    }
    
    return $caminho;
}

/**
 * Remove arquivo de upload
 */
function removerImagem($nomeArquivo, $pasta = 'vendors') {
    $caminho = UPLOAD_PATH . '/' . $pasta . '/' . $nomeArquivo;
    
    if (file_exists($caminho)) {
        return unlink($caminho);
    }
    
    return false;
}

/**
 * Limpa arquivos temporários antigos
 */
function limparUploadsTemporarios($horas = 24) {
    require_once UPLOAD_PATH . '/Uploader.php';
    
    $uploader = new Uploader();
    return $uploader->cleanupTempFiles($horas);
}

/**
 * Lista arquivos de uma pasta de upload
 */
function listarArquivosUpload($pasta = 'vendors') {
    $caminho = UPLOAD_PATH . '/' . $pasta;
    
    if (!file_exists($caminho)) {
        return [];
    }
    
    $arquivos = scandir($caminho);
    $arquivos = array_diff($arquivos, ['.', '..', '.htaccess', 'index.html']);
    
    $resultado = [];
    foreach ($arquivos as $arquivo) {
        $caminhoCompleto = $caminho . '/' . $arquivo;
        if (is_file($caminhoCompleto)) {
            $resultado[] = [
                'nome' => $arquivo,
                'tamanho' => filesize($caminhoCompleto),
                'modificado' => date('d/m/Y H:i', filemtime($caminhoCompleto)),
                'caminho' => 'uploads/' . $pasta . '/' . $arquivo
            ];
        }
    }
    
    return $resultado;
}
?>