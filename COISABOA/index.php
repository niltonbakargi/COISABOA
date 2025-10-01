<?php
/**
 * COISABOA - Página Inicial e Dashboard
 * Arquivo: index.php
 * Local: C:\xampp\htdocs\COISABOA\COISABOA\index.php
 * @version 1.0.0
 */

// ==================== CONFIGURAÇÃO DE CAMINHOS ====================

// Definir o base path corretamente para a estrutura atual
define('BASE_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));

// ==================== VERIFICAÇÃO DE INSTALAÇÃO ====================

// Verificar se o sistema está instalado
$config_file = __DIR__ . '/config/database.php';
$installed_file = __DIR__ . '/config/installed.json';

if (!file_exists($config_file) || !file_exists($installed_file)) {
    // Redirecionar para o instalador se não estiver instalado
    if (file_exists('install.php')) {
        header('Location: install.php');
        exit;
    } else {
        echo '<!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>COISABOA - Instalação</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    margin: 0; 
                    padding: 20px;
                }
                .container { 
                    background: white; 
                    padding: 3rem; 
                    border-radius: 15px; 
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
                    text-align: center; 
                    max-width: 500px;
                }
                h1 { color: #333; margin-bottom: 1rem; }
                p { color: #666; margin-bottom: 2rem; }
                .btn { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: #667eea; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 8px; 
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📱 COISABOA</h1>
                <p>Sistema de Controle Comercial</p>
                <p>O sistema não está instalado. Clique no botão abaixo para iniciar a instalação.</p>
                <a href="install.php" class="btn">🚀 Instalar Sistema</a>
                <p style="margin-top: 2rem; font-size: 0.9em; color: #999;">
                    Caminho atual: ' . htmlspecialchars(__DIR__) . '
                </p>
            </div>
        </body>
        </html>';
        exit;
    }
}

// ==================== INICIALIZAÇÃO DO SISTEMA ====================

// Carregar configurações e funções
require_once __DIR__ . '/includes/init.php';

// Configurações da página
$page_title = 'Dashboard - COISABOA';
$current_page = 'dashboard';

// ==================== DADOS DO DASHBOARD ====================

try {
    // Estatísticas principais
    $estatisticas = [
        'total_produtos' => dbFind("SELECT COUNT(DISTINCT produto) as total FROM compras")['total'] ?? 0,
        'total_compras' => dbFind("SELECT COUNT(*) as total FROM compras")['total'] ?? 0,
        'total_vendas' => dbFind("SELECT COUNT(*) as total FROM vendas")['total'] ?? 0,
        'valor_total_vendas' => dbFind("SELECT COALESCE(SUM(valor_total), 0) as total FROM vendas")['total'] ?? 0,
        'lucro_estimado' => dbFind("
            SELECT COALESCE(SUM(v.valor_total - (v.quantidade * c.valor_pago)), 0) as lucro
            FROM vendas v
            INNER JOIN compras c ON v.produto = c.produto
        ")['lucro'] ?? 0
    ];

    // Últimas movimentações
    $ultimas_movimentacoes = dbFindAll("
        (SELECT 'compra' as tipo, produto, quantidade, valor_pago as valor, data_compra as data, NULL as forma_pagamento
         FROM compras ORDER BY data_compra DESC LIMIT 5)
        UNION ALL
        (SELECT 'venda' as tipo, produto, quantidade, valor_vendido as valor, data_venda as data, forma_pagamento
         FROM vendas ORDER BY data_venda DESC LIMIT 5)
        ORDER BY data DESC LIMIT 8
    ");

    // Produtos com estoque baixo
    $estoque_baixo = dbFindAll("
        SELECT produto, 
               (COALESCE(SUM(c.quantidade), 0) - COALESCE(SUM(v.quantidade), 0)) as estoque_atual
        FROM (SELECT produto, quantidade FROM compras) c
        LEFT JOIN (SELECT produto, quantidade FROM vendas) v ON c.produto = v.produto
        GROUP BY c.produto
        HAVING estoque_atual <= ? AND estoque_atual > 0
        ORDER BY estoque_atual ASC LIMIT 6
    ", [5]);

} catch (Exception $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
    error_log($error_message);
}

// ==================== INTERFACE ====================

// Incluir cabeçalho
include __DIR__ . '/templates/header.php';
?>

<div class="container">
    <!-- Cabeçalho -->
    <div class="page-header text-center" style="margin-bottom: 3rem;">
        <h1 class="fade-in">📊 Dashboard COISABOA</h1>
        <p class="text-muted">Sistema funcionando em: <?= $_SERVER['HTTP_HOST'] ?>/COISABOA/COISABOA/</p>
    </div>

    <!-- Cards de Estatísticas -->
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
        
        <div class="stat-card">
            <div class="stat-icon">💵</div>
            <div class="stat-number"><?= formatarMoeda($estatisticas['valor_total_vendas']) ?></div>
            <div class="stat-label">Faturamento</div>
        </div>
    </div>

    <!-- Grid Principal -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        
        <!-- Últimas Movimentações -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🔄</div>
                <h3 class="card-title">Últimas Movimentações</h3>
            </div>
            
            <div class="recent-activity">
                <?php if (empty($ultimas_movimentacoes)): ?>
                    <div class="text-center" style="padding: 2rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                        <p style="color: var(--cor-cinza-500);">Nenhuma movimentação</p>
                        <a href="modules/purchases/comprei.php" class="btn btn-primary btn-sm">Primeira Compra</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimas_movimentacoes as $movimento): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?= $movimento['tipo'] === 'compra' ? 'activity-icon-compra' : 'activity-icon-venda' ?>">
                                <?= $movimento['tipo'] === 'compra' ? '🛒' : '💰' ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <strong><?= $movimento['produto'] ?></strong>
                                    <small class="text-muted">
                                        (<?= $movimento['quantidade'] ?> un - <?= formatarMoeda($movimento['valor']) ?>)
                                    </small>
                                </div>
                                <div class="activity-time">
                                    <?= formatarData($movimento['data']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">⚡</div>
                    <h3 class="card-title">Ações Rápidas</h3>
                </div>
                
                <div style="padding: 1.5rem;">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <a href="modules/purchases/comprei.php" class="btn btn-primary btn-lg" style="text-align: center;">
                            🛒 Nova Compra
                        </a>
                        
                        <a href="modules/sales/vendi.php" class="btn btn-success btn-lg" style="text-align: center;">
                            💰 Nova Venda
                        </a>
                        
                        <a href="modules/inventory/estoque.php" class="btn btn-outline btn-lg" style="text-align: center;">
                            📊 Ver Estoque
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (!empty($estoque_baixo)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">⚠️</div>
                    <h3 class="card-title">Estoque Baixo</h3>
                </div>
                
                <div class="recent-activity">
                    <?php foreach ($estoque_baixo as $produto): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: var(--cor-alerta);">📦</div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <strong><?= $produto['produto'] ?></strong>
                                    <small class="text-alerta">
                                        (<?= $produto['estoque_atual'] ?> unidades)
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php
// Incluir rodapé
include __DIR__ . '/templates/footer.php';
?>