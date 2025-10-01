<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pasta = 'C:/xampp/htdocs/COISABOA/uploads/produtos/9';
$url_base = 'http://localhost/COISABOA/uploads/produtos/9';

if (!is_dir($pasta)) {
    die("❌ Caminho da pasta não encontrado: $pasta");
}

$arquivos = scandir($pasta);

foreach ($arquivos as $arquivo) {
    if ($arquivo !== '.' && $arquivo !== '..') {
        $url = $url_base . '/' . $arquivo;
        echo "<div style='margin:10px; display:inline-block; text-align:center;'>
                <img src='$url' style='max-width:200px; max-height:200px; border:1px solid #ccc;'><br>
                <small>$arquivo</small>
              </div>";
    }
}
?>
