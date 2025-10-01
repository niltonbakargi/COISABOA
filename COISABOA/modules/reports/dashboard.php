<?php
/**
 * COISABOA - Dashboard Principal
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Configurações da página
$page_title = 'Dashboard - COISABOA';
$current_page = 'dashboard';

// Estatísticas do dashboard
$estatisticas = [
    'total_produtos' => dbFind("SELECT COUNT(DISTINCT produto) as total FROM compras")['total'],
    'total_compras' => dbFind("SELECT COUNT(*) as total FROM compras")['total'],
    'total_vendas' => dbFind("SELECT COUNT(*) as total FROM vendas")['total'],
    'valor_total_vendas' => dbFind("SELECT COALESCE(SUM(valor_total), 0) as total FROM vendas")['total'],
    'lucro_total' => dbFind("
        SELECT COALESCE(SUM(V.valor_total - (V.quantidade * C.valor_pago)), 0) as lucro
        FROM vendas V
        JOIN compras C ON V.produto = C.produto
    ")['lucro']
];

// Últimas movimentações
$ultimas_movimentacoes = dbFindAll("
    (SELECT 'compra' as tipo, produto, quantidade, valor_pago as valor, data_compra as data 
     FROM compras 
     ORDER BY data_compra DESC 
     LIMIT 5)
    UNION ALL
    (SELECT 'venda' as tipo, produto, quantidade, valor_vendido as valor, data_venda as data 
     FROM vendas 
     ORDER BY data_venda DESC 
     LIMIT 5)
    ORDER BY data DESC 
    LIMIT 10
");

// Produtos com estoque baixo
$estoque_baixo = dbFindAll("
    SELECT produto, 
           (COALESCE(SUM(C.quantidade), 0) - COALESCE(SUM(V.quantidade), 0)) as estoque
    FROM (SELECT produto, quantidade FROM compras) C
    LEFT JOIN (SELECT produto, quantidade FROM vendas) V ON C.produto = V.produto
    GROUP BY C.produto
    HAVING estoque <= ? AND estoque > 0
    ORDER BY estoque ASC
    LIMIT 5
", [ESTOQUE_MINIMO_ALERTA]);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 3rem;">
        <h1>📊 Dashboard COISABOA</h1>
        <p>Visão geral do seu negócio</p>
    </div>
    
    <!-- Cards de Estatísticas -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?= $estatisticas['total_produtos'] ?></div>
            <div class="stat-label">Produtos Cadastrados</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-number"><?= $estatisticas['total_compras'] ?></div>
            <div class="stat-label">Compras Realizadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number"><?= $estatisticas['total_vendas'] ?></div>
            <div class="stat-label">Vendas Realizadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💵</div>
            <div class="stat-number"><?= formatarMoeda($estatisticas['valor_total_vendas']) ?></div>
            <div class="stat-label">Faturamento Total</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-number"><?= formatarMoeda($estatisticas['lucro_total']) ?></div>
            <div class="stat-label">Lucro Estimado</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-number"><?= count($estoque_baixo) ?></div>
            <div class="stat-label">Produtos com Estoque Baixo</div>
        </div>
    </div>
    
    <div class="cards-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem;">
        <!-- Últimas Movimentações -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🔄</div>
                <h3 class="card-title">Últimas Movimentações</h3>
            </div>
            
            <div class="recent-activity">
                <?php if (empty($ultimas_movimentacoes)): ?>
                    <p style="text-align: center; color: var(--cor-cinza-500);">Nenhuma movimentação recente</p>
                <?php else: ?>
                    <?php foreach ($ultimas_movimentacoes as $movimento): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?= $movimento['tipo'] === 'compra' ? 'activity-icon-compra' : 'activity-icon-venda' ?>">
                                <?= $movimento['tipo'] === 'compra' ? '🛒' : '💰' ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?= $movimento['produto'] ?> 
                                    <small>(<?= $movimento['quantidade'] ?> un - <?= formatarMoeda($movimento['valor']) ?>)</small>
                                </div>
                                <div class="activity-time">
                                    <?= formatarDataRelativa($movimento['data']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Alertas de Estoque -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">⚠️</div>
                <h3 class="card-title">Alertas de Estoque</h3>
            </div>
            
            <div class="recent-activity">
                <?php if (empty($estoque_baixo)): ?>
                    <p style="text-align: center; color: var(--cor-sucesso);">✅ Todos os produtos com estoque adequado</p>
                <?php else: ?>
                    <?php foreach ($estoque_baixo as $produto): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: var(--cor-alerta);">📦</div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?= $produto['produto'] ?>
                                    <small class="text-alerta">(<?= $produto['estoque'] ?> unidades)</small>
                                </div>
                                <div class="activity-time">
                                    <a href="../inventory/estoque.php" class="btn btn-sm btn-outline">Ver Estoque</a>
                                    <a href="../purchases/comprei.php?produto=<?= urlencode($produto['produto']) ?>" 
                                       class="btn btn-sm btn-primary">Comprar</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Ações Rápidas -->
    <div class="cards-grid" style="margin-top: 2rem;">
        <a href="../purchases/comprei.php" class="card" style="text-decoration: none; color: inherit;">
            <div class="card-header">
                <div class="card-icon">🛒</div>
                <h3 class="card-title">Nova Compra</h3>
            </div>
            <p class="card-description">Registrar entrada de novos produtos no estoque</p>
        </a>
        
        <a href="../sales/vendi.php" class="card" style="text-decoration: none; color: inherit;">
            <div class="card-header">
                <div class="card-icon">💰</div>
                <h3 class="card-title">Nova Venda</h3>
            </div>
            <p class="card-description">Registrar venda de produtos do estoque</p>
        </a>
        
        <a href="../inventory/estoque.php" class="card" style="text-decoration: none; color: inherit;">
            <div class="card-header">
                <div class="card-icon">📊</div>
                <h3 class="card-title">Ver Estoque</h3>
            </div>
            <p class="card-description">Consultar relatório completo de estoque</p>
        </a>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>