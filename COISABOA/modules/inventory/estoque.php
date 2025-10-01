<?php
/**
 * COISABOA - Módulo de Estoque - Relatório Completo
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Configurações da página
$page_title = 'Estoque - COISABOA';
$current_page = 'estoque';

// Buscar relatório consolidado
$relatorio = getRelatorioEstoque();

// Calcular totais gerais
$total_estoque = 0;
$total_valor_estoque = 0;
$produtos_baixo_estoque = 0;
$produtos_zerados = 0;

foreach ($relatorio as $item) {
    $total_estoque += $item['estoque_atual'];
    $total_valor_estoque += $item['estoque_atual'] * ($item['custo_medio'] ?? 0);
    
    if ($item['estoque_atual'] <= ESTOQUE_MINIMO_ALERTA) {
        $produtos_baixo_estoque++;
    }
    if ($item['estoque_atual'] <= 0) {
        $produtos_zerados++;
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1>📊 Relatório de Estoque</h1>
        <p>Visão consolidada de todos os produtos</p>
    </div>
    
    <!-- Cards de Estatísticas -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?= count($relatorio) ?></div>
            <div class="stat-label">Produtos Cadastrados</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-number"><?= $total_estoque ?></div>
            <div class="stat-label">Total em Estoque</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number"><?= formatarMoeda($total_valor_estoque) ?></div>
            <div class="stat-label">Valor Total</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-number"><?= $produtos_baixo_estoque ?></div>
            <div class="stat-label">Produtos com Estoque Baixo</div>
        </div>
    </div>
    
    <!-- Tabela de Estoque -->
    <div class="table-container">
        <div class="table-header" style="display: flex; justify-content: between; align-items: center; padding: 1rem;">
            <h3 style="margin: 0;">Produtos em Estoque</h3>
            <div style="display: flex; gap: 1rem;">
                <input type="text" id="searchInput" placeholder="🔍 Buscar produto..." class="form-control" style="width: 250px;">
                <button class="btn btn-outline" onclick="exportarEstoque()">📤 Exportar</button>
            </div>
        </div>
        
        <table class="table" id="estoqueTable">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Estoque Atual</th>
                    <th>Total Comprado</th>
                    <th>Total Vendido</th>
                    <th>Custo Médio</th>
                    <th>Valor em Estoque</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatorio as $item): ?>
                    <?php
                    $status_estoque = validarEstoqueMinimo($item['estoque_atual']);
                    $valor_estoque = $item['estoque_atual'] * ($item['custo_medio'] ?? 0);
                    ?>
                    <tr class="estoque-<?= $status_estoque['status'] ?>">
                        <td>
                            <strong><?= $item['produto'] ?></strong>
                            <?php if ($status_estoque['alerta']): ?>
                                <br><small class="text-<?= $status_estoque['status'] ?>"><?= $status_estoque['alerta'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['estoque_atual'] ?> un</td>
                        <td><?= $item['total_comprado'] ?> un</td>
                        <td><?= $item['total_vendido'] ?> un</td>
                        <td><?= isset($item['custo_medio']) ? formatarMoeda($item['custo_medio']) : 'N/A' ?></td>
                        <td><?= formatarMoeda($valor_estoque) ?></td>
                        <td>
                            <span class="estoque-badge estoque-<?= $status_estoque['status'] ?>">
                                <?= strtoupper($status_estoque['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="movimentacoes.php?produto=<?= urlencode($item['produto']) ?>" 
                                   class="btn btn-sm btn-outline"
                                   title="Histórico">
                                    📋
                                </a>
                                <a href="../sales/vendi.php?produto=<?= urlencode($item['produto']) ?>" 
                                   class="btn btn-sm btn-success"
                                   title="Vender">
                                    💰
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($relatorio)): ?>
        <div class="card text-center">
            <div class="card-icon" style="margin: 0 auto 1rem;">📭</div>
            <h3>Nenhum produto em estoque</h3>
            <p>Comece registrando sua primeira compra</p>
            <a href="../purchases/comprei.php" class="btn btn-primary">Registrar Primeira Compra</a>
        </div>
    <?php endif; ?>
</div>

<script>
// Busca em tempo real
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#estoqueTable tbody tr');
    
    rows.forEach(row => {
        const productName = row.querySelector('td:first-child strong').textContent.toLowerCase();
        row.style.display = productName.includes(searchTerm) ? '' : 'none';
    });
});

function exportarEstoque() {
    // Simular exportação (implementar com PHP depois)
    alert('Funcionalidade de exportação em desenvolvimento!');
}

// Ordenação da tabela
let sortDirection = 1;
function sortTable(columnIndex) {
    const table = document.getElementById('estoqueTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent;
        const bValue = b.cells[columnIndex].textContent;
        
                // Tentar converter para número
        const aNum = parseFloat(aValue.replace(/[^\d.,]/g, '').replace(',', '.'));
        const bNum = parseFloat(bValue.replace(/[^\d.,]/g, '').replace(',', '.'));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return (aNum - bNum) * sortDirection;
        }
        
        return aValue.localeCompare(bValue) * sortDirection;
    });
    
    // Reordenar as linhas
    rows.forEach(row => tbody.appendChild(row));
    sortDirection *= -1;
}
</script>

<style>
.estoque-normal { background: #f0f9ff; }
.estoque-alerta { background: #fffbeb; }
.estoque-critico { background: #fef2f2; }

.text-normal { color: var(--cor-sucesso); }
.text-alerta { color: var(--cor-alerta); }
.text-critico { color: var(--cor-erro); }

.table-header {
    background: var(--cor-cinza-50);
    border-bottom: 1px solid var(--cor-cinza-200);
}
</style>

<?php
include __DIR__ . '/../../templates/footer.php';
?>