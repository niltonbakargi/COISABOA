<?php
/**
 * COISABOA - Histórico de Movimentações por Produto
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Configurações da página
$page_title = 'Movimentações - COISABOA';
$current_page = 'estoque';

// Verificar se foi passado um produto específico
$produto_filtro = $_GET['produto'] ?? '';
$historico = [];

if (!empty($produto_filtro)) {
    $historico = getHistoricoProduto($produto_filtro);
    $estoque_atual = getEstoqueAtual($produto_filtro);
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <?php if (!empty($produto_filtro)): ?>
        <div class="page-header" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: between; align-items: center;">
                <div>
                    <h1>📋 Histórico de Movimentações</h1>
                    <p>Produto: <strong><?= $produto_filtro ?></strong> | Estoque atual: <?= $estoque_atual ?> unidades</p>
                </div>
                <a href="estoque.php" class="btn btn-outline">← Voltar ao Estoque</a>
            </div>
        </div>
        
        <?php if (empty($historico)): ?>
            <div class="card text-center">
                <div class="card-icon" style="margin: 0 auto 1rem;">📭</div>
                <h3>Nenhuma movimentação encontrada</h3>
                <p>Não há registros para este produto</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Valor Total</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $saldo_acumulado = 0;
                        foreach ($historico as $movimento):
                            if ($movimento['tipo'] === 'compra') {
                                $saldo_acumulado += $movimento['quantidade'];
                            } else {
                                $saldo_acumulado -= $movimento['quantidade'];
                            }
                        ?>
                            <tr>
                                <td><?= formatarData($movimento['data']) ?></td>
                                <td>
                                    <span class="estoque-badge <?= $movimento['tipo'] === 'compra' ? 'estoque-normal' : 'estoque-alerta' ?>">
                                        <?= $movimento['tipo'] === 'compra' ? '🛒 COMPRA' : '💰 VENDA' ?>
                                    </span>
                                </td>
                                <td><?= $movimento['quantidade'] ?> un</td>
                                <td><?= formatarMoeda($movimento['valor']) ?></td>
                                <td><?= formatarMoeda($movimento['quantidade'] * $movimento['valor']) ?></td>
                                <td>
                                    <strong class="<?= $saldo_acumulado > 0 ? 'text-normal' : 'text-critico' ?>">
                                        <?= $saldo_acumulado ?> un
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="card text-center">
            <div class="card-icon" style="margin: 0 auto 1rem;">🔍</div>
            <h3>Selecione um Produto</h3>
            <p>Volte ao estoque e clique em "Histórico" para ver as movimentações de um produto específico</p>
            <a href="estoque.php" class="btn btn-primary">Voltar ao Estoque</a>
        </div>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>