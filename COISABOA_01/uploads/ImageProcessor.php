<?php
/**
 * COISABOA - Processador Avançado de Imagens
 * @version 1.0.0
 */

class ImageProcessor {
    
    /**
     * Cria thumbnail de uma imagem
     */
    public function createThumbnail($sourcePath, $destPath, $maxWidth = 200, $maxHeight = 200) {
        if (!file_exists($sourcePath)) return false;
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) return false;
        
        list($width, $height, $type) = $imageInfo;
        
        // Calcular novas dimensões mantendo proporção
        $ratio = $width / $height;
        
        if ($maxWidth / $maxHeight > $ratio) {
            $newWidth = $maxHeight * $ratio;
            $newHeight = $maxHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        }
        
        // Criar imagem source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        // Criar thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparência
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        // Redimensionar
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salvar
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $destPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $destPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $destPath);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return true;
    }
    
    /**
     * Aplica marca d'água
     */
    public function applyWatermark($imagePath, $watermarkText = 'COISABOA') {
        // Implementação futura
        return true;
    }
}
?>