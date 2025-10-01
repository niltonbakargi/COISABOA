<?php
/**
 * COISABOA - Módulo de Vendas - Formulário de Nova Venda
 */

require_once __DIR__ . '/../../includes/init.php';

$page_title = 'Nova Venda - COISABOA';
$current_page = 'vendas';

// Buscar produtos em estoque
$produtos_estoque = dbFindAll("
    SELECT produto, 
           (COALESCE(SUM(C.quantidade), 0) - COALESCE(SUM(V.quantidade), 0)) as estoque
    FROM (SELECT produto, quantidade FROM compras) C
    LEFT JOIN (SELECT produto, quantidade FROM vendas) V ON C.produto = V.produto
    GROUP BY C.produto
    HAVING estoque > 0
    ORDER BY C.produto
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'insert_venda.php';
    exit;
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="form-container fade-in">
        <h1 class="form-title">💰 Registrar Nova Venda</h1>
        
        <form method="POST" id="formVenda">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>">
            
            <div class="form-group">
                <label for="produto" class="form-label">Selecionar Produto *</label>
                <select id="produto" name="produto" class="form-control" required>
                    <option value="">-- Selecione --</option>
                    <?php foreach ($produtos_estoque as $produto): ?>
                        <option value="<?= $produto['produto'] ?>" data-estoque="<?= $produto['estoque'] ?>">
                            <?= $produto['produto'] ?> (Estoque: <?= $produto['estoque'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantidade" class="form-label">Quantidade *</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" required min="1">
            </div>
            
            <div class="form-group">
                <label for="valor_vendido" class="form-label">Valor da Venda *</label>
                <input type="text" id="valor_vendido" name="valor_vendido" class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="history.back()">← Voltar</button>
                <button type="submit" class="btn btn-primary btn-lg">💰 Registrar Venda</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('produto').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const estoque = option.getAttribute('data-estoque');
    document.getElementById('quantidade').max = estoque;
});
</script>

<?php
include __DIR__ . '/../../templates/footer.php';
?>