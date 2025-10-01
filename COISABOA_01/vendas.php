<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$msg = "";

/**
 * Converte o caminho salvo no BD para uma URL exibível.
 * Aceita formatos:
 *  - http(s)://...
 *  - /COISABOA/COISABOA/...
 *  - COISABOA/..., uploads/...
 *  - caminhos Windows (C:\xampp\...uploads\arquivo.png)
 * Retorna sempre algo como: /COISABOA/COISABOA/uploads/arquivo.png
 */
function web_path(?string $path): string {
    if (!$path) return '';
    $p = str_replace('\\', '/', trim($path)); // normaliza separador
    $p = ltrim($p, '/');

    // URL absoluta já ok
    if (preg_match('~^https?://~i', $p)) return $p;

    // Se veio caminho Windows completo: tenta cortar a partir de "uploads/"
    if (preg_match('~^[A-Za-z]:/|^/~', $p)) {
        $pos = stripos($p, 'uploads/');
        if ($pos !== false) $p = substr($p, $pos);
    }

    // Se já começa com COISABOA/COISABOA, retorna com barra na frente
    if (stripos($p, 'COISABOA/COISABOA/') === 0) {
        return '/' . $p;
    }

    // Se começa com COISABOA/ (apenas uma), remove para não duplicar a base
    if (stripos($p, 'COISABOA/') === 0 && stripos($p, 'COISABOA/COISABOA/') !== 0) {
        $p = substr($p, strlen('COISABOA/'));
    }

    // Base do app (ex.: /COISABOA/COISABOA)
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $base . '/' . ltrim($p, '/');
}

// Buscar itens disponíveis do estoque
$produtos = $pdo->query("
    SELECT id, produto, numero, valor_pago, imagem
    FROM estoque_itens
    WHERE status = 'disponivel'
    ORDER BY produto, numero
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id          = (int)($_POST['item_id'] ?? 0);
    $valor_vendido    = (float)($_POST['valor_vendido'] ?? 0);
    $valor_total      = (float)($_POST['valor_total'] ?? 0);
    $desconto         = (float)($_POST['desconto'] ?? 0);
    $forma_pagamento  = $_POST['forma_pagamento'] ?? 'dinheiro';
    $data_venda_d     = $_POST['data_venda'] ?? date('Y-m-d');
    $data_venda       = $data_venda_d . ' 00:00:00';
    $imagem_comprador = null;

    // Upload da imagem do comprador (opcional) — caminho RELATIVO: uploads/compradores/...
    if (!empty($_FILES['imagem_comprador']['name'])) {
        $dir = __DIR__ . "/uploads/compradores/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['imagem_comprador']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $permitidas)) $ext = 'jpg';

        $nomeArquivo = uniqid("comprador_") . "." . $ext;
        $destino = $dir . $nomeArquivo;

        $okMime = true;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($f, $_FILES['imagem_comprador']['tmp_name']);
            finfo_close($f);
            $okMime = (bool)preg_match('~^image/(jpeg|png|gif|webp)$~i', (string)$mime);
        }

        if ($okMime && move_uploaded_file($_FILES['imagem_comprador']['tmp_name'], $destino)) {
            $imagem_comprador = "uploads/compradores/" . $nomeArquivo; // salva RELATIVO
        }
    }

    // Buscar item no estoque
    $stmt = $pdo->prepare("SELECT produto, numero, valor_pago, imagem FROM estoque_itens WHERE id = ? AND status = 'disponivel'");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $msg = "❌ Item não encontrado ou já vendido.";
    } else {
        try {
            $pdo->beginTransaction();

            // Registrar venda (imagem_produto = o que está no estoque_itens.imagem)
            $stmt = $pdo->prepare("
                INSERT INTO vendas 
                (produto, quantidade, valor_vendido, valor_total, desconto, forma_pagamento, imagem_produto, imagem_comprador, data_venda) 
                VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $item['produto'] . " " . $item['numero'], // Ex.: "Geladeira 01"
                $valor_vendido,
                $valor_total,
                $desconto,
                $forma_pagamento,
                $item['imagem'] ?? null,
                $imagem_comprador,
                $data_venda
            ]);

            // Dar baixa no estoque_itens
            $stmt = $pdo->prepare("UPDATE estoque_itens SET status = 'vendido' WHERE id = ?");
            $stmt->execute([$item_id]);

            $pdo->commit();
            $msg = "✅ Venda registrada e estoque atualizado!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "❌ Erro: " . $e->getMessage();
        }
    }
}

// Listagem de vendas
$vendas = $pdo->query("SELECT * FROM vendas ORDER BY data_venda DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registrar Venda - COISABOA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f8fafc; }
        form { background: white; padding: 20px; border-radius: 8px; width: 460px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #059669; }
        .msg { margin: 15px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #f1f5f9; }
        img { max-width: 120px; border-radius: 4px; margin-top: 8px; }
        #preview { max-width: 180px; }
        small.hint { color:#6b7280; display:block; margin:-8px 0 12px 0; }
    </style>
</head>
<body>
    <h2>💰 Registrar Venda</h2>

    <?php if ($msg): ?>
        <div class="msg <?= strpos($msg, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Produto</label>
        <select name="item_id" required onchange="mostrarImagem(this)">
            <option value="">Selecione...</option>
            <?php foreach ($produtos as $p): ?>
                <option
                    value="<?= (int)$p['id'] ?>"
                    data-imagem="<?= htmlspecialchars(web_path($p['imagem'])) ?>">
                    <?= htmlspecialchars($p['produto']." ".$p['numero']) ?>
                    (R$ <?= number_format((float)$p['valor_pago'], 2, ',', '.') ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div id="preview-box" style="text-align:center; margin-bottom:12px; display:none;">
            <img id="preview" src="" alt="Preview do Produto">
            <div><a id="preview-link" href="#" target="_blank" style="font-size:12px;">abrir imagem</a></div>
        </div>

        <label>Valor Unitário</label>
        <input type="number" step="0.01" name="valor_vendido" required>

        <label>Valor Total</label>
        <input type="number" step="0.01" name="valor_total" required>

        <label>Desconto</label>
        <input type="number" step="0.01" name="desconto" value="0.00">

        <label>Forma de Pagamento</label>
        <select name="forma_pagamento" required>
            <option value="dinheiro">Dinheiro</option>
            <option value="cartao_credito">Cartão de Crédito</option>
            <option value="cartao_debito">Cartão de Débito</option>
            <option value="pix">PIX</option>
            <option value="transferencia">Transferência</option>
            <option value="boleto">Boleto</option>
        </select>

        <label>Data da Venda</label>
        <input type="date" name="data_venda" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
        <small class="hint">Pode alterar para registrar vendas de períodos anteriores.</small>

        <label>Foto do Comprador (opcional)</label>
        <input type="file" name="imagem_comprador" accept="image/*">

        <button type="submit">Registrar Venda</button>
    </form>

    <h3>📋 Lista de Vendas</h3>
    <table>
        <tr>
            <th>ID</th><th>Produto</th><th>Qtd</th><th>Unitário</th>
            <th>Total</th><th>Desconto</th><th>Pagamento</th>
            <th>Produto</th><th>Comprador</th><th>Data</th>
        </tr>
        <?php foreach ($vendas as $v): ?>
        <tr>
            <td><?= (int)$v["id"] ?></td>
            <td><?= htmlspecialchars($v["produto"]) ?></td>
            <td><?= (int)$v["quantidade"] ?></td>
            <td>R$ <?= number_format((float)$v["valor_vendido"], 2, ',', '.') ?></td>
            <td>R$ <?= number_format((float)$v["valor_total"], 2, ',', '.') ?></td>
            <td>R$ <?= number_format((float)$v["desconto"], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars(ucfirst(str_replace("_", " ", $v["forma_pagamento"]))) ?></td>
            <td>
                <?php if (!empty($v["imagem_produto"])): ?>
                    <img src="<?= htmlspecialchars(web_path($v["imagem_produto"])) ?>" alt="Produto">
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($v["imagem_comprador"])): ?>
                    <img src="<?= htmlspecialchars(web_path($v["imagem_comprador"])) ?>" alt="Comprador">
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($v["data_venda"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p style="margin-top:20px;">
        <a href="painel.php">⬅️ Voltar ao painel</a>
    </p>

    <script>
        function mostrarImagem(sel) {
            const opt = sel.options[sel.selectedIndex];
            const imgSrc = opt.getAttribute("data-imagem"); // já vem normalizado no PHP
            const box = document.getElementById("preview-box");
            const img = document.getElementById("preview");
            const link = document.getElementById("preview-link");

            if (imgSrc) {
                img.src = imgSrc;
                link.href = imgSrc;
                box.style.display = "block";
            } else {
                img.src = "";
                link.href = "#";
                box.style.display = "none";
            }
        }
    </script>
</body>
</html>
