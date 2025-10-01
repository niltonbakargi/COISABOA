<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

// ---------- Helper: normaliza caminho salvo no BD para URL exibível ----------
function web_path(?string $path): string {
    if (!$path) return '';
    // Se já for URL completa, usa direto
    if (preg_match('~^https?://~i', $path)) return $path;
    // Se começar com barra, já é absoluto no site
    if ($path[0] === '/') return $path;
    // Base = diretório onde este script vive, ex: /COISABOA/COISABOA
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $base . '/' . ltrim($path, '/');
}

$msg = "";

// ---------------------------------------------------------------------------
// POST: Registrar compra + subir imagens + popular estoque_itens
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campos
    $produto        = trim($_POST['produto'] ?? '');
    $quantidade     = (int)($_POST['quantidade'] ?? 0);
    $valor_pago     = (float)($_POST['valor_pago'] ?? 0);
    $valor_total    = (float)($_POST['valor_total'] ?? 0);
    $valor_proposto = (float)($_POST['valor_proposto'] ?? 0);
    $desconto       = (float)($_POST['desconto'] ?? 0);
    $observacoes    = trim($_POST['observacoes'] ?? '');
    $data_compra_d  = $_POST['data_compra'] ?? date('Y-m-d'); // yyyy-mm-dd
    // grava como DATETIME com 00:00:00 (você pode trocar por hora atual se preferir)
    $data_compra    = $data_compra_d . ' 00:00:00';

    // Upload da foto do produto (salva caminho RELATIVO: uploads/produtos/...)
    $imagem_produto = null;
    if (!empty($_FILES['imagem_produto']['name'])) {
        $dir = __DIR__ . "/uploads/produtos/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['imagem_produto']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $permitidas)) $ext = 'jpg';

        $nomeArquivo = uniqid("produto_") . "." . $ext;
        $destino = $dir . $nomeArquivo;

        // (Opcional) checar mime
        $okMime = true;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['imagem_produto']['tmp_name']);
            finfo_close($finfo);
            $okMime = (bool)preg_match('~^image/(jpeg|png|gif|webp)$~i', (string)$mime);
        }

        if ($okMime && move_uploaded_file($_FILES['imagem_produto']['tmp_name'], $destino)) {
            // salva RELATIVO
            $imagem_produto = "uploads/produtos/" . $nomeArquivo;
        }
    }

    // Upload da foto do vendedor (RELATIVO: uploads/vendedores/...)
    $imagem_vendedor = null;
    if (!empty($_FILES['imagem_vendedor']['name'])) {
        $dir = __DIR__ . "/uploads/vendedores/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['imagem_vendedor']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $permitidas)) $ext = 'jpg';

        $nomeArquivo = uniqid("vendedor_") . "." . $ext;
        $destino = $dir . $nomeArquivo;

        $okMime = true;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['imagem_vendedor']['tmp_name']);
            finfo_close($finfo);
            $okMime = (bool)preg_match('~^image/(jpeg|png|gif|webp)$~i', (string)$mime);
        }

        if ($okMime && move_uploaded_file($_FILES['imagem_vendedor']['tmp_name'], $destino)) {
            $imagem_vendedor = "uploads/vendedores/" . $nomeArquivo;
        }
    }

    if ($produto === '' || $quantidade <= 0 || $valor_pago < 0 || $valor_total < 0) {
        $msg = "❌ Preencha os campos obrigatórios corretamente.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1) Registrar compra
            $stmt = $pdo->prepare("
                INSERT INTO compras 
                (produto, quantidade, valor_pago, valor_total, valor_proposto, desconto, observacoes, data_compra, imagem_produto, imagem_vendedor) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $produto,
                $quantidade,
                $valor_pago,
                $valor_total,
                $valor_proposto,
                $desconto,
                $observacoes,
                $data_compra,
                $imagem_produto,
                $imagem_vendedor
            ]);

            // 2) Registrar itens no estoque_itens (produto + número sequencial + imagem)
            // Pega o último número UMA vez e só incrementa no loop
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(numero),0) FROM estoque_itens WHERE produto = ?");
            $stmt->execute([$produto]);
            $ultimo = (int)$stmt->fetchColumn();
            $proximo = $ultimo + 1;

            $ins = $pdo->prepare("
                INSERT INTO estoque_itens (produto, numero, valor_pago, status, imagem) 
                VALUES (?, ?, ?, 'disponivel', ?)
            ");

            for ($i = 0; $i < $quantidade; $i++) {
                $ins->execute([$produto, $proximo + $i, $valor_pago, $imagem_produto]);
            }

            $pdo->commit();
            $msg = "✅ Compra registrada e estoque atualizado!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "❌ Erro: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------------------------------------
// Lista de compras
// ---------------------------------------------------------------------------
$compras = $pdo->query("SELECT * FROM compras ORDER BY data_compra DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registrar Compra - COISABOA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f8fafc; }
        form { background: white; padding: 20px; border-radius: 8px; width: 460px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #556cd6; }
        .msg { margin: 15px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #f1f5f9; }
        img { max-width: 80px; border-radius: 4px; }
        small.hint { color:#6b7280; display:block; margin:-8px 0 12px 0; }
    </style>
</head>
<body>
    <h2>🛒 Registrar Compra</h2>

    <?php if ($msg): ?>
        <div class="msg <?= str_contains($msg, '✅') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Produto</label>
        <input type="text" name="produto" required>

        <label>Quantidade</label>
        <input type="number" name="quantidade" min="1" required>

        <label>Valor Pago (por item)</label>
        <input type="number" step="0.01" name="valor_pago" required>

        <label>Valor Total</label>
        <input type="number" step="0.01" name="valor_total" required>

        <label>Valor Proposto</label>
        <input type="number" step="0.01" name="valor_proposto">

        <label>Desconto</label>
        <input type="number" step="0.01" name="desconto" value="0.00">

        <label>Observações</label>
        <textarea name="observacoes"></textarea>

        <label>Data da Compra</label>
        <input type="date" name="data_compra" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
        <small class="hint">Você pode alterar esta data para registrar compras de períodos anteriores.</small>

        <label>Foto do Produto</label>
        <input type="file" name="imagem_produto" accept="image/*">

        <label>Foto do Vendedor</label>
        <input type="file" name="imagem_vendedor" accept="image/*">

        <button type="submit">Registrar</button>
    </form>

    <h3>📋 Lista de Compras</h3>
    <table>
        <tr>
            <th>ID</th><th>Produto</th><th>Qtd</th><th>Valor Pago</th>
            <th>Total</th><th>Proposto</th><th>Desconto</th>
            <th>Imagem (Produto)</th><th>Imagem (Vendedor)</th><th>Data</th>
        </tr>
        <?php foreach ($compras as $c): ?>
        <tr>
            <td><?= (int)$c["id"] ?></td>
            <td><?= htmlspecialchars($c["produto"]) ?></td>
            <td><?= (int)$c["quantidade"] ?></td>
            <td>R$ <?= number_format((float)$c["valor_pago"], 2, ',', '.') ?></td>
            <td>R$ <?= number_format((float)$c["valor_total"], 2, ',', '.') ?></td>
            <td>R$ <?= number_format((float)$c["valor_proposto"], 2, ',', '.') ?></td>
            <td>R$ <?= number_format((float)$c["desconto"], 2, ',', '.') ?></td>
            <td>
                <?php if (!empty($c["imagem_produto"])): ?>
                    <img src="<?= htmlspecialchars(web_path($c["imagem_produto"])) ?>" alt="Produto">
                <?php else: ?> — <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($c["imagem_vendedor"])): ?>
                    <img src="<?= htmlspecialchars(web_path($c["imagem_vendedor"])) ?>" alt="Vendedor">
                <?php else: ?> — <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($c["data_compra"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p style="margin-top:20px;">
        <a href="painel.php">⬅️ Voltar ao painel</a>
    </p>
</body>
</html>
