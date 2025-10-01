<?php
/**
 * COISABOA - Módulo de Estoque - Relatório Completo
 */

require_once __DIR__ . '/../../includes/init.php';

$page_title = 'Estoque - COISABOA';
$current_page = 'estoque';

$relatorio = getRelatorioEstoque();

// Calcular totais
$total_estoque = 0;
$produtos_baixo_estoque = 0;

foreach ($relatorio as $item) {
    $total_estoque += $item['estoque_atual'];
    if ($item['estoque_atual'] <= ESTOQUE_MINIMO_ALERTA) {
        $produtos_baixo_estoque++;
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1>📊 Relatório de Estoque</h1>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?= count($relatorio) ?></div>
            <div class="stat-label">Produtos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-number"><?= $total_estoque ?></div>
            <div class="stat-label">Total em Estoque</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-number"><?= $produtos_baixo_estoque ?></div>
            <div class="stat-label">Estoque Baixo</div>
        </div>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Estoque</th>
                    <th>Comprado</th>
                    <th>Vendido</th>
                    <th>Custo Médio</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatorio as $item): ?>
                    <?php $status = validarEstoqueMinimo($item['estoque_atual']); ?>
                    <tr class="estoque-<?= $status['status'] ?>">
                        <td><strong><?= $item['produto'] ?></strong></td>
                        <td><?= $item['estoque_atual'] ?> un</td>
                        <td><?= $item['total_comprado'] ?> un</td>
                        <td><?= $item['total_vendido'] ?> un</td>
                        <td><?= isset($item['custo_medio']) ? formatarMoeda($item['custo_medio']) : 'N/A' ?></td>
                        <td>
                            <a href="movimentacoes.php?produto=<?= urlencode($item['produto']) ?>" 
                               class="btn btn-sm btn-outline">📋 Histórico</a>
                            <a href="../sales/vendi.php?produto=<?= urlencode($item['produto']) ?>" 
                               class="btn btn-sm btn-success">💰 Vender</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>