gerar_relatorio_projeto<?php
/**
 * 🔍 GERADOR DE RELATÓRIO COMPLETO DO PROJETO ANDROID COISABOA
 * Autor: ChatGPT + Nilton Bakargi
 * Data: <?= date('d/m/Y') ?>
 * 
 * Este script varre toda a estrutura de um projeto Android (ou PHP, Java, etc.)
 * e gera um relatório HTML detalhado com informações úteis:
 * - Estrutura hierárquica de diretórios
 * - Estatísticas por tipo de arquivo
 * - Relações entre Activities, layouts e classes
 * - Tamanho total e contagem de linhas
 * - Data de modificação
 */

$baseDir = __DIR__;
$relatorioPath = $baseDir . DIRECTORY_SEPARATOR . 'relatorio_projeto.html';

// Armazenamento de dados
$estrutura = [];
$estatisticas = [
    'total_arquivos' => 0,
    'total_pastas' => 0,
    'linhas_por_tipo' => [],
    'tamanho_total' => 0
];

// 🔁 Função recursiva para listar arquivos e subpastas
function analisarDiretorio($dir, &$estrutura, &$estatisticas, $nivel = 0) {
    $indent = str_repeat('  ', $nivel);
    $itens = scandir($dir);
    foreach ($itens as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $estatisticas['total_pastas']++;
            $estrutura[] = "{$indent}📁 <strong>$item/</strong>";
            analisarDiretorio($path, $estrutura, $estatisticas, $nivel + 1);
        } else {
            $estatisticas['total_arquivos']++;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $tamanho = filesize($path);
            $modificado = date("d/m/Y H:i:s", filemtime($path));
            $estatisticas['tamanho_total'] += $tamanho;

            // contar linhas
            $linhas = 0;
            if (in_array($ext, ['php', 'java', 'kt', 'xml', 'gradle'])) {
                $conteudo = file($path);
                $linhas = count($conteudo);
                $estatisticas['linhas_por_tipo'][$ext] = ($estatisticas['linhas_por_tipo'][$ext] ?? 0) + $linhas;
            }

            $estrutura[] = sprintf(
                "%s📄 %s <small>(%s | %d linhas | %0.2f KB | mod: %s)</small>",
                $indent, htmlspecialchars($item), strtoupper($ext), $linhas, $tamanho / 1024, $modificado
            );
        }
    }
}

// 🔗 Função para identificar relações (Activities ↔ Layouts ↔ Classes)
function analisarRelacoes($baseDir) {
    $rel = [];

    // Manifest
    $manifestPath = $baseDir . '/app/src/main/AndroidManifest.xml';
    if (file_exists($manifestPath)) {
        $xml = @simplexml_load_file($manifestPath);
        if ($xml && isset($xml->application->activity)) {
            foreach ($xml->application->activity as $activity) {
                $rel['activities'][] = (string)$activity['android:name'];
            }
        }
    }

    // Layouts
    $layoutDir = $baseDir . '/app/src/main/res/layout';
    if (is_dir($layoutDir)) {
        foreach (glob("$layoutDir/*.xml") as $file) {
            $rel['layouts'][] = basename($file);
        }
    }

    // Classes
    $javaDir = $baseDir . '/app/src/main/java';
    if (is_dir($javaDir)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($javaDir));
        foreach ($it as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['kt', 'java'])) {
                $rel['classes'][] = str_replace($javaDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }
    }

    return $rel;
}

// 🚀 Início da análise
analisarDiretorio($baseDir, $estrutura, $estatisticas);
$relacoes = analisarRelacoes($baseDir);

// 🧮 Cálculos adicionais
$estatisticas['tamanho_total_mb'] = round($estatisticas['tamanho_total'] / (1024 * 1024), 2);

// 🖋️ Geração do relatório HTML
$conteudo = "<!DOCTYPE html><html lang='pt-BR'><head>
<meta charset='UTF-8'>
<title>Relatório Técnico - Projeto COISABOA</title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background:#f8fafc; color:#222; margin:20px; }
h1,h2,h3 { color:#1976D2; }
pre { background:#fff; padding:15px; border-radius:10px; border:1px solid #ddd; }
small { color:#666; }
ul { line-height:1.6; }
.stat { background:#fff; padding:10px 15px; border-radius:8px; border:1px solid #ccc; margin-bottom:10px; }
.footer { margin-top:40px; font-size:0.9rem; color:#555; text-align:center; }
</style></head><body>";

$conteudo .= "<h1>📊 Relatório Técnico do Projeto COISABOA</h1>";
$conteudo .= "<p><strong>Gerado em:</strong> " . date('d/m/Y H:i:s') . "</p>";
$conteudo .= "<p><strong>Pasta base:</strong> {$baseDir}</p>";

$conteudo .= "<h2>📂 Estrutura Completa de Arquivos e Pastas</h2>";
$conteudo .= "<pre>" . implode("\n", $estrutura) . "</pre>";

$conteudo .= "<h2>📈 Estatísticas Gerais</h2>";
$conteudo .= "<div class='stat'>Total de arquivos: <strong>{$estatisticas['total_arquivos']}</strong></div>";
$conteudo .= "<div class='stat'>Total de pastas: <strong>{$estatisticas['total_pastas']}</strong></div>";
$conteudo .= "<div class='stat'>Tamanho total do projeto: <strong>{$estatisticas['tamanho_total_mb']} MB</strong></div>";

if (!empty($estatisticas['linhas_por_tipo'])) {
    $conteudo .= "<h3>📄 Linhas de código por tipo de arquivo</h3><ul>";
    foreach ($estatisticas['linhas_por_tipo'] as $ext => $num) {
        $conteudo .= "<li><strong>." . strtoupper($ext) . "</strong>: $num linhas</li>";
    }
    $conteudo .= "</ul>";
}

$conteudo .= "<h2>🔗 Relações Identificadas</h2>";

if (!empty($relacoes['activities'])) {
    $conteudo .= "<h3>Activities no Manifest</h3><ul>";
    foreach ($relacoes['activities'] as $a) $conteudo .= "<li>📱 $a</li>";
    $conteudo .= "</ul>";
}

if (!empty($relacoes['layouts'])) {
    $conteudo .= "<h3>Layouts XML</h3><ul>";
    foreach ($relacoes['layouts'] as $l) $conteudo .= "<li>🧩 $l</li>";
    $conteudo .= "</ul>";
}

if (!empty($relacoes['classes'])) {
    $conteudo .= "<h3>Classes Java/Kotlin</h3><ul>";
    foreach ($relacoes['classes'] as $c) $conteudo .= "<li>💡 " . str_replace('\\', '/', $c) . "</li>";
    $conteudo .= "</ul>";
}

$conteudo .= "<h2>🧭 Conclusão</h2>";
$conteudo .= "<p>O relatório foi gerado automaticamente a partir da análise de todo o conteúdo do projeto COISABOA. 
Ele permite visualizar a estrutura, dependências, atividades e estatísticas de desenvolvimento.</p>";

$conteudo .= "<div class='footer'>Relatório gerado automaticamente por ChatGPT e Nilton Bakargi © " . date('Y') . "</div>";
$conteudo .= "</body></html>";

// 💾 Gravar arquivo HTML
file_put_contents($relatorioPath, $conteudo);

// ✅ Saída no navegador
echo "✅ Relatório gerado com sucesso!<br>";
echo "Arquivo salvo em: <strong>$relatorioPath</strong><br>";
echo "Abra no navegador para visualizar.";
?>
