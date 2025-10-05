<?php
/**
 * Script: descrever_andamento.php
 * Autor: ChatGPT & Nilton Dobes Bakargi
 * Função: Descrever o andamento do projeto Android automaticamente.
 */

$diretorio = "C:/xampp/htdocs/COISABOA/android_COISABOA";

function listarArquivos($pasta) {
    $itens = scandir($pasta);
    $estrutura = [];
    foreach ($itens as $item) {
        if ($item === '.' || $item === '..') continue;
        $caminho = $pasta . DIRECTORY_SEPARATOR . $item;
        if (is_dir($caminho)) {
            $estrutura[$item] = listarArquivos($caminho);
        } else {
            $estrutura[] = $item;
        }
    }
    return $estrutura;
}

function gerarRelatorio($estrutura) {
    $relatorio = "";
    $todosArquivos = json_encode($estrutura);
    
    // Interpretação simples do progresso
    if (strpos($todosArquivos, "AndroidManifest.xml") !== false) {
        $relatorio .= "📱 O arquivo *AndroidManifest.xml* foi encontrado — o app já possui estrutura base do Android.\n";
    }
    if (strpos($todosArquivos, "MainActivity") !== false) {
        $relatorio .= "🎯 O arquivo *MainActivity.java* ou equivalente está presente — a tela principal foi criada.\n";
    }
    if (strpos($todosArquivos, "res") !== false) {
        $relatorio .= "🎨 A pasta *res* está configurada — recursos visuais e layouts estão sendo utilizados.\n";
    }
    if (strpos($todosArquivos, "build.gradle") !== false) {
        $relatorio .= "⚙️ Arquivos *build.gradle* detectados — o projeto está configurado para compilação.\n";
    }
    if (strpos($todosArquivos, "layout") !== false) {
        $relatorio .= "🧩 Layouts XML encontrados — interfaces gráficas estão sendo desenvolvidas.\n";
    }
    if (strpos($todosArquivos, "drawable") !== false) {
        $relatorio .= "🖼️ A pasta *drawable* contém ícones ou imagens do app.\n";
    }
    if (strpos($todosArquivos, "java") !== false) {
        $relatorio .= "💻 Código-fonte Java detectado — lógica do aplicativo em desenvolvimento.\n";
    }
    if (strpos($todosArquivos, "apk") !== false) {
        $relatorio .= "✅ Arquivos *.apk* detectados — o app já foi compilado ou testado.\n";
    }

    if ($relatorio === "") {
        $relatorio = "⚠️ Nenhum arquivo típico de projeto Android foi detectado. Verifique o caminho do diretório.";
    }

    return nl2br($relatorio);
}

$estrutura = listarArquivos($diretorio);
$relatorio = gerarRelatorio($estrutura);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório do Projeto Android COISABOA</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f3f3; padding: 20px; }
        h1 { color: #2c3e50; }
        pre { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        .relatorio { background: #eafaf1; padding: 15px; border-left: 5px solid #2ecc71; }
    </style>
</head>
<body>
    <h1>📊 Relatório do Andamento do Projeto Android — COISABOA</h1>
    <div class="relatorio"><?= $relatorio ?></div>
    <h2>📂 Estrutura de Arquivos</h2>
    <pre><?php print_r($estrutura); ?></pre>
</body>
</html>
