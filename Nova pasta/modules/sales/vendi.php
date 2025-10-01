<?php
/**
 * COISABOA - Módulo de Vendas - Formulário de Nova Venda
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Configurações da página
$page_title = 'Nova Venda - COISABOA';
$current_page = 'vendas';

// Buscar produtos em estoque para autocomplete
$produtos_estoque = dbFindAll("
    SELECT produto, 
           (COALESCE(SUM(C.quantidade), 0) - COALESCE(SUM(V.quantidade), 0)) as estoque
    FROM (SELECT produto, quantidade FROM compras) C
    LEFT JOIN (SELECT produto, quantidade FROM vendas) V ON C.produto = V.produto
    GROUP BY C.produto
    HAVING estoque > 0
    ORDER BY C.produto
");

// Processar formulário se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'insert_venda.php';
    exit;
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="form-container fade-in">
        <h1 class="form-title">💰 Registrar Nova Venda</h1>
        
        <form method="POST" id="formVenda" data-managed>
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>">
            
            <div class="form-group">
                <label for="produto" class="form-label">Selecionar Produto *</label>
                <select id="produto" name="produto" class="form-control" required>
                    <option value="">-- Selecione um produto --</option>
                    <?php foreach ($produtos_estoque as $produto): ?>
                        <option value="<?= $produto['produto'] ?>" 
                                data-estoque="<?= $produto['estoque'] ?>">
                            <?= $produto['produto'] ?> (Estoque: <?= $produto['estoque'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text" id="estoque-info">Selecione um produto para ver detalhes</div>
            </div>
            
            <div class="form-group">
                <label for="quantidade" class="form-label">Quantidade Vendida *</label>
                <input type="number" 
                       id="quantidade" 
                       name="quantidade" 
                       class="form-control" 
                       required 
                       min="1" 
                       max="1"
                       placeholder="Quantidade">
                <div class="form-text" id="quantidade-info">Quantidade máxima disponível: 1</div>
            </div>
            
            <div class="form-group">
                <label for="valor_vendido" class="form-label">Valor da Venda *</label>
                <input type="text" 
                       id="valor_vendido" 
                       name="valor_vendido" 
                       class="form-control" 
                       required 
                       placeholder="R$ 0,00"
                       oninput="formatarMoeda(this)">
            </div>
            
            <div class="form-group">
                <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                <select id="forma_pagamento" name="forma_pagamento" class="form-control">
                    <?php foreach (FORMAS_PAGAMENTO as $valor => $label): ?>
                        <option value="<?= $valor ?>" <?= $valor == 'dinheiro' ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="history.back()">← Voltar</button>
                <button type="submit" class="btn btn-primary btn-lg">
                    💰 Registrar Venda
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const produtos = <?= json_encode($produtos_estoque) ?>;

document.getElementById('produto').addEventListener('change', function() {
    const produtoSelecionado = this.value;
    const option = this.options[this.selectedIndex];
    const estoque = option.getAttribute('data-estoque');
    
    document.getElementById('quantidade').max = estoque;
    document.getElementById('quantidade-info').textContent = 
        'Quantidade máxima disponível: ' + estoque;
    
    // Buscar informações do produto
    const produto = produtos.find(p => p.produto === produtoSelecionado);
    if (produto) {
        document.getElementById('estoque-info').textContent = 
            'Estoque atual: ' + produto.estoque + ' unidades';
    }
});

function formatarMoeda(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (valor / 100).toFixed(2) + '';
    valor = valor.replace(/\./, ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = 'R$ ' + valor;
}
</script>

<?php
include __DIR__ . '/../../templates/footer.php';
?>