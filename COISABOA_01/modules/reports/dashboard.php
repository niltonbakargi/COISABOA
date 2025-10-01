<?php
/**
 * COISABOA - Dashboard Principal
 */

require_once __DIR__ . '/../../includes/init.php';

$page_title = 'Dashboard - COISABOA';
$current_page = 'dashboard';

// Estatísticas
$estatisticas = [
    'total_produtos' => dbFind("SELECT COUNT(DISTINCT produto) as total FROM compras")['total'],
    'total_compras' => dbFind("SELECT COUNT(*) as total FROM compras")['total'],
    'total_vendas' => dbFind("SELECT COUNT(*) as total FROM vendas")['total']
];

// Últimas movimentações
$ultimas_movimentacoes = dbFindAll("
    (SELECT 'compra' as tipo, produto, quantidade, data_compra as data FROM compras ORDER BY data_compra DESC LIMIT 3)
    UNION ALL
    (SELECT 'venda' as tipo, produto, quantidade, data_venda as data FROM vendas ORDER BY data_venda DESC LIMIT 3)
    ORDER BY data DESC LIMIT 6
");

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 3rem;">
        <h1>📊 Dashboard COISABOA</h1>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?= $estatisticas['total_produtos'] ?></div>
            <div class="stat-label">Produtos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-number"><?= $estatisticas['total_compras'] ?></div>
            <div class="stat-label">Compras</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number"><?= $estatisticas['total_vendas'] ?></div>
            <div class="stat-label">Vendas</div>
        </div>
    </div>
    
    <div class="cards-grid" style="margin-top: 3rem;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🔄</div>
                <h3 class="card-title">Últimas Movimentações</h3>
            </div>
            
            <div class="recent-activity">
                <?php foreach ($ultimas_movimentacoes as $movimento): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $movimento['tipo'] === 'compra' ? 'activity-icon-compra' : 'activity-icon-venda' ?>">
                            <?= $movimento['tipo'] === 'compra' ? '🛒' : '💰' ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?= $movimento['produto'] ?></div>
                            <div class="activity-time"><?= formatarData($movimento['data']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon">⚡</div>
                <h3 class="card-title">Ações Rápidas</h3>
            </div>
            
            <div style="padding: 1rem;">
                <a href="../purchases/comprei.php" class="btn btn-primary btn-full" style="margin-bottom: 1rem;">
                    🛒 Nova Compra
                </a>
                <a href="../sales/vendi.php" class="btn btn-success btn-full" style="margin-bottom: 1rem;">
                    💰 Nova Venda
                </a>
                <a href="../inventory/estoque.php" class="btn btn-outline btn-full">
                    📊 Ver Estoque
                </a>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>