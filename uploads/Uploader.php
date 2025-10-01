<?php
/**
 * COISABOA - Classe Gerenciadora de Uploads
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

class Uploader {
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;
    private $errors = [];
    
    public function __construct($uploadPath = null) {
        $this->allowedTypes = ALLOWED_IMAGE_TYPES;
        $this->maxSize = MAX_FILE_SIZE;
        $this->uploadPath = $uploadPath ?: UPLOAD_PATH;
    }
    
    /**
     * Realiza upload de uma imagem com validações
     */
    public function uploadImage($file, $subfolder = 'vendors', $customName = null) {
        $this->errors = [];
        
        // Validar arquivo
        if (!$this->validateFile($file)) {
            return false;
        }
        
        // Validar tipo de imagem
        if (!$this->validateImageType($file)) {
            $this->errors[] = 'Tipo de arquivo não permitido. Use: ' . implode(', ', array_keys($this->allowedTypes));
            return false;
        }
        
        // Validar dimensões
        if (!$this->validateImageDimensions($file)) {
            $this->errors[] = 'Imagem muito grande. Máximo: ' . MAX_IMAGE_WIDTH . 'x' . MAX_IMAGE_HEIGHT . 'px';
            return false;
        }
        
        // Criar diretório se não existir
        $targetDir = $this->uploadPath . '/' . $subfolder;
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Gerar nome seguro
        $fileName = $customName ?: $this->generateSafeName($file['name']);
        $targetFile = $targetDir . '/' . $fileName;
        
        // Tentar mover arquivo
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Aplicar compressão se necessário
            $this->compressImage($targetFile);
            
            // Registrar log
            $this->logUpload($fileName, $subfolder, $file['size']);
            
            return $fileName;
        } else {
            $this->errors[] = 'Erro ao salvar arquivo. Verifique permissões.';
            return false;
        }
    }
    
    /**
     * Validações básicas do arquivo
     */
    private function validateFile($file) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->handleUploadError($file['error']);
            return false;
        }
        
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'Arquivo muito grande. Máximo: ' . ($this->maxSize / 1024 / 1024) . 'MB';
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida tipo da imagem
     */
    private function validateImageType($file) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Verificar extensão
        if (!array_key_exists($extension, $this->allowedTypes)) {
            return false;
        }
        
        // Verificar MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return in_array($mime, $this->allowedTypes);
    }
    
    /**
     * Valida dimensões da imagem
     */
    private function validateImageDimensions($file) {
        $imageInfo = getimagesize($file['tmp_name']);
        
        if (!$imageInfo) {
            $this->errors[] = 'Arquivo não é uma imagem válida';
            return false;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        return $width <= MAX_IMAGE_WIDTH && $height <= MAX_IMAGE_HEIGHT;
    }
    
    /**
     * Gera nome seguro para arquivo
     */
    private function generateSafeName($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Remover caracteres especiais
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $safeName = substr($safeName, 0, 100); // Limitar tamanho
        
        // Adicionar timestamp para evitar conflitos
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        
        return $safeName . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Comprime imagem para otimização
     */
    private function compressImage($filePath) {
        $quality = 85; // Qualidade JPEG (0-100)
        $maxWidth = 1200; // Largura máxima após compressão
        
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) return false;
        
        $mime = $imageInfo['mime'];
        $width = $imageInfo[0];
        
        // Só comprime se for maior que o máximo
        if ($width <= $maxWidth) return true;
        
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }
        
        if (!$image) return false;
        
        // Calcular nova altura mantendo proporção
        $newWidth = $maxWidth;
        $newHeight = intval($imageInfo[1] * $maxWidth / $width);
        
        // Criar nova imagem
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparência PNG/GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        // Redimensionar
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $imageInfo[1]);
        
        // Salvar com qualidade
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($newImage, $filePath, $quality);
                break;
            case 'image/png':
                imagepng($newImage, $filePath, 9); // 0-9 (compressão PNG)
                break;
            case 'image/gif':
                imagegif($newImage, $filePath);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($newImage);
        
        return true;
    }
    
    /**
     * Registra log do upload
     */
    private function logUpload($fileName, $subfolder, $fileSize) {
        if (!LOG_ENABLED) return;
        
        $logDir = $this->uploadPath . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/uploads_' . date('Y-m') . '.log';
        $logEntry = date('Y-m-d H:i:s') . " | ";
        $logEntry .= $fileName . " | ";
        $logEntry .= $subfolder . " | ";
        $logEntry .= number_format($fileSize) . " bytes | ";
        $logEntry .= $_SERVER['REMOTE_ADDR'] . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Trata erros de upload
     */
    private function handleUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                $this->errors[] = 'Arquivo excede o tamanho máximo permitido pelo servidor';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = 'Arquivo excede o tamanho máximo permitido pelo formulário';
                break;
            case UPLOAD_ERR_PARTIAL:
                $this->errors[] = 'Upload foi realizado parcialmente';
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = 'Nenhum arquivo foi enviado';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->errors[] = 'Pasta temporária não encontrada';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $this->errors[] = 'Falha ao escrever arquivo no disco';
                break;
            case UPLOAD_ERR_EXTENSION:
                $this->errors[] = 'Upload interrompido por extensão';
                break;
            default:
                $this->errors[] = 'Erro desconhecido no upload';
        }
    }
    
    /**
     * Retorna erros ocorridos
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Limpa arquivos temporários antigos
     */
    public function cleanupTempFiles($maxAgeHours = 24) {
        $tempDir = $this->uploadPath . '/temp';
        if (!file_exists($tempDir)) return 0;
        
        $files = glob($tempDir . '/*');
        $deleted = 0;
        $maxAge = time() - ($maxAgeHours * 3600);
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $maxAge) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Exibe imagem com segurança
     */
    public function displayImage($fileName, $subfolder = 'vendors') {
        $filePath = $this->uploadPath . '/' . $subfolder . '/' . $fileName;
        
        if (!file_exists($filePath)) {
            return 'images/placeholder.jpg'; // Imagem padrão
        }
        
        // Verificar se é realmente uma imagem
        $mime = mime_content_type($filePath);
        if (strpos($mime, 'image/') !== 0) {
            return 'images/placeholder.jpg';
        }
        
        return 'uploads/' . $subfolder . '/' . $fileName;
    }
}
?>