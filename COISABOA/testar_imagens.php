<?php
// Arquivo para testar imagens da pasta 9
$pasta = "uploads/produtos/9";

// Buscar TODOS os arquivos da pasta (não só imagens)
$arquivos = [];
if (file_exists($pasta) && is_dir($pasta)) {
    $todos_arquivos = scandir($pasta);
    foreach ($todos_arquivos as $arquivo) {
        if ($arquivo !== '.' && $arquivo !== '..') {
            $caminho = "$pasta/$arquivo";
            if (is_file($caminho)) {
                $arquivos[] = [
                    'nome' => $arquivo,
                    'caminho' => $caminho,
                    'extensao' => pathinfo($arquivo, PATHINFO_EXTENSION)
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Testar Imagens</title>
    <style>
        body { 
            font-family: Arial; 
            text-align: center; 
            padding: 20px;
            background: #f0f0f0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .imagem-grande {
            max-width: 90%;
            max-height: 500px;
            border: 3px solid #333;
            border-radius: 10px;
            margin: 20px 0;
        }
        .botoes {
            margin: 20px 0;
        }
        button {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .contador {
            font-size: 18px;
            margin: 10px 0;
            color: #666;
        }
        .info {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: left;
        }
        .lista-arquivos {
            text-align: left;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Testar Imagens da Pasta 9</h1>
        
        <div class="info">
            <strong>Pasta:</strong> <?= $pasta ?><br>
            <strong>Arquivos encontrados:</strong> <?= count($arquivos) ?>
        </div>

        <?php if (count($arquivos) > 0): ?>
            
            <!-- Lista de arquivos encontrados -->
            <div class="lista-arquivos">
                <h3>📁 Arquivos na pasta:</h3>
                <?php foreach ($arquivos as $index => $arquivo): ?>
                    <div style="margin: 5px 0; padding: 5px; background: #f8f9fa; border-radius: 3px;">
                        <?= $index + 1 ?>. 
                        <strong><?= $arquivo['nome'] ?></strong> 
                        (<?= $arquivo['extensao'] ?>)
                        - <a href="<?= $arquivo['caminho'] ?>" target="_blank">Abrir</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="contador" id="contador">
                Arquivo 1 de <?= count($arquivos) ?>
            </div>
            
            <img src="<?= $arquivos[0]['caminho'] ?>" class="imagem-grande" id="imagemAtual"
                 onerror="this.style.display='none'; document.getElementById('erroImagem').style.display='block';">
            
            <div id="erroImagem" style="display: none; color: red; padding: 20px;">
                <h3>❌ Erro ao carregar imagem!</h3>
                <p>O arquivo existe mas não pode ser exibido como imagem.</p>
            </div>
            
            <div class="botoes">
                <button onclick="imagemAnterior()">← Anterior</button>
                <button onclick="proximaImagem()">Próxima →</button>
            </div>
            
            <p>💡 <strong>Dica:</strong> Use as setas ← → do teclado ou role com o mouse!</p>
            
        <?php else: ?>
            <div style="padding: 40px; color: #666;">
                <h3>❌ Nenhum arquivo encontrado!</h3>
                <p>Verifique se a pasta <strong><?= $pasta ?></strong> existe e contém arquivos.</p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="vender.php" style="color: #3498db;">← Voltar para Vender</a>
        </div>
    </div>

    <script>
        const arquivos = <?= json_encode($arquivos) ?>;
        let arquivoIndex = 0;

        function mostrarArquivo() {
            if (arquivos.length === 0) return;
            
            const imagemElement = document.getElementById('imagemAtual');
            const erroElement = document.getElementById('erroImagem');
            const arquivoAtual = arquivos[arquivoIndex];
            
            // Mostrar informações
            document.getElementById('contador').textContent = 
                `Arquivo ${arquivoIndex + 1} de ${arquivos.length}: ${arquivoAtual.nome}`;
            
            // Tentar carregar a imagem
            imagemElement.src = arquivoAtual.caminho;
            imagemElement.style.display = 'block';
            erroElement.style.display = 'none';
            
            // Verificar se carregou
            imagemElement.onload = function() {
                console.log('✅ Imagem carregada:', arquivoAtual.caminho);
            };
            
            imagemElement.onerror = function() {
                console.log('❌ Erro ao carregar:', arquivoAtual.caminho);
                imagemElement.style.display = 'none';
                erroElement.style.display = 'block';
                erroElement.innerHTML = `
                    <h3>❌ Não é uma imagem válida!</h3>
                    <p><strong>Arquivo:</strong> ${arquivoAtual.nome}</p>
                    <p><strong>Tipo:</strong> ${arquivoAtual.extensao}</p>
                    <p><a href="${arquivoAtual.caminho}" target="_blank">Tentar abrir diretamente</a></p>
                `;
            };
        }

        function proximaImagem() {
            if (arquivos.length === 0) return;
            
            arquivoIndex = (arquivoIndex + 1) % arquivos.length;
            mostrarArquivo();
        }

        function imagemAnterior() {
            if (arquivos.length === 0) return;
            
            arquivoIndex = (arquivoIndex - 1 + arquivos.length) % arquivos.length;
            mostrarArquivo();
        }

        // Navegação por teclado
        document.addEventListener('keydown', function(event) {
            if (event.key === 'ArrowLeft') {
                imagemAnterior();
            } else if (event.key === 'ArrowRight') {
                proximaImagem();
            }
        });

        // Navegação por rolagem do mouse
        document.addEventListener('wheel', function(event) {
            if (event.deltaY > 0) {
                proximaImagem();
            } else {
                imagemAnterior();
            }
        });

        // Inicializar
        mostrarArquivo();
    </script>
</body>
</html>