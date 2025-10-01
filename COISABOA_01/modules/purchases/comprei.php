<?php
/**
 * COISABOA - Módulo de Compras - Formulário de Nova Compra
 */

require_once __DIR__ . '/../../includes/init.php';

$page_title = 'Nova Compra - COISABOA';
$current_page = 'compras';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'insert_compra.php';
    exit;
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="form-container fade-in">
        <h1 class="form-title">🛒 Registrar Nova Compra</h1>
        
        <form method="POST" enctype="multipart/form-data" id="formCompra">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>">
            
            <div class="form-group">
                <label for="produto" class="form-label">Nome do Produto *</label>
                <input type="text" id="produto" name="produto" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="quantidade" class="form-label">Quantidade *</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" required min="1">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="valor_pago" class="form-label">Valor Pago *</label>
                    <input type="text" id="valor_pago" name="valor_pago" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="valor_proposto" class="form-label">Valor Sugerido *</label>
                    <input type="text" id="valor_proposto" name="valor_proposto" class="form-control" required>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="history.back()">← Voltar</button>
                <button type="submit" class="btn btn-primary btn-lg">💾 Registrar Compra</button>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>