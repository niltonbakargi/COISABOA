<?php
/**
 * COISABOA - Módulo de Compras - Formulário de Nova Compra
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Configurações da página
$page_title = 'Nova Compra - COISABOA';
$current_page = 'compras';

// Processar formulário se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'insert_compra.php';
    exit;
}

// Incluir cabeçalho
include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="form-container fade-in">
        <h1 class="form-title">🛒 Registrar Nova Compra</h1>
        
        <form method="POST" enctype="multipart/form-data" id="formCompra" data-managed>
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>">
            
            <div class="form-group">
                <label for="produto" class="form-label">Nome do Produto *</label>
                <input type="text" 
                       id="produto" 
                       name="produto" 
                       class="form-control" 
                       required 
                       data-validation="required"
                       data-min-length="2"
                       maxlength="100"
                       placeholder="Ex: Camiseta Branca P">
                <div class="form-text">Digite o nome completo do produto</div>
            </div>
            
            <div class="form-group">
                <label for="quantidade" class="form-label">Quantidade *</label>
                <input type="number" 
                       id="quantidade" 
                       name="quantidade" 
                       class="form-control" 
                       required 
                       data-validation="number"
                       min="1" 
                       max="9999"
                       placeholder="Ex: 10">
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="valor_pago" class="form-label">Valor Pago (Custo) *</label>
                    <input type="text" 
                           id="valor_pago" 
                           name="valor_pago" 
                           class="form-control" 
                           required 
                           data-validation="number"
                           placeholder="R$ 0,00"
                           oninput="formatarMoeda(this)">
                </div>
                
                <div class="form-group">
                    <label for="valor_proposto" class="form-label">Valor de Venda Sugerido *</label>
                    <input type="text" 
                           id="valor_proposto" 
                           name="valor_proposto" 
                           class="form-control" 
                           required 
                           data-validation="number"
                           placeholder="R$ 0,00"
                           oninput="formatarMoeda(this)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="foto_vendedor" class="form-label">Foto do Vendedor (Opcional)</label>
                <div class="file-upload" onclick="document.getElementById('foto_vendedor').click()">
                    <input type="file" 
                           id="foto_vendedor" 
                           name="foto_vendedor" 
                           accept="image/*" 
                           style="display: none"
                           onchange="previewImage(this)">
                    <div id="upload-area">
                        <p>📷 Clique para selecionar uma foto</p>
                        <small>Formatos: JPG, PNG, GIF (Max: 10MB)</small>
                    </div>
                </div>
                <div id="image-preview" class="file-preview hidden"></div>
            </div>
            
            <div class="form-group">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea id="observacoes" 
                          name="observacoes" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Informações adicionais sobre a compra..."
                          maxlength="500"></textarea>
                <div class="form-text">Máximo 500 caracteres</div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="history.back()">← Voltar</button>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Registrar Compra
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function formatarMoeda(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (valor / 100).toFixed(2) + '';
    valor = valor.replace(/\./, ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = 'R$ ' + valor;
}

function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const uploadArea = document.getElementById('upload-area');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            preview.classList.remove('hidden');
            uploadArea.innerHTML = '<p>✅ Imagem selecionada</p><small>Clique para alterar</small>';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
// Incluir rodapé
include __DIR__ . '/../../templates/footer.php';
?>