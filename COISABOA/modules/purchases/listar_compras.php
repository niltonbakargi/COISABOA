<?php
/**
 * COISABOA - Listagem de Compras
 * @version 1.0.0
 */

require_once __DIR__ . '/../../includes/init.php';

// Configurações da página
$page_title = 'Histórico de Compras - COISABOA';
$current_page = 'compras';

// Paginação
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$itens_por_pagina = ITEMS_PER_PAGE;
$offset = ($pagina - 1) * $itens_por_pagina;

// Buscar compras
$compras = dbFindAll("
    SELECT * FROM compras 
    ORDER BY data_compra DESC 
    LIMIT ? OFFSET ?
", [$itens_por_pagina, $offset]);

// Total de compras para paginação
$total_compras = dbFind("SELECT COUNT(*) as total FROM compras")['total'];
$total_paginas = ceil($total_compras / $itens_por_pagina);

// Incluir cabeçalho
include __DIR__ . '/../../templates/header.php';
?>

<div class="container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>📦 Histórico de Compras</h1>
        <a href="comprei.php" class="btn btn-primary">
            ➕ Nova Compra
        </a>
    </div>
    
    <?php if (empty($compras)): ?>
        <div class="card text-center">
            <div class="card-icon" style="margin: 0 auto 1rem;">📭</div>
            <h3>Nenhuma compra registrada</h3>
            <p>Comece registrando sua primeira compra</p>
            <a href="comprei.php" class="btn btn-primary">Registrar Primeira Compra</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Pago</th>
                        <th>Valor Sugerido</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                        <tr>
                            <td>
                                <strong><?= $compra['produto'] ?></strong>
                                <?php if ($compra['observacoes']): ?>
                                    <br><small class="text-muted"><?= limitarTexto($compra['observacoes'], 50) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= $compra['quantidade'] ?> un</td>
                            <td><?= formatarMoeda($compra['valor_pago']) ?></td>
                            <td><?= formatarMoeda($compra['valor_proposto']) ?></td>
                            <td><?= formatarData($compra['data_compra']) ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if ($compra['foto_vendedor']): ?>
                                        <button class="btn btn-sm btn-outline" 
                                                onclick="mostrarFoto('<?= $compra['foto_vendedor'] ?>')"
                                                title="Ver Foto">
                                            📷
                                        </button>
                                    <?php endif; ?>
                                    <a href="editar_compra.php?id=<?= $compra['id'] ?>" 
                                       class="btn btn-sm btn-outline"
                                       title="Editar">
                                        ✏️
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?= $compra['id'] ?>)"
                                            title="Excluir">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <nav class="pagination">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?= $i ?>" 
                       class="btn btn-sm <?= $i == $pagina ? 'btn-primary' : 'btn-outline' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal para foto -->
<div id="fotoModal" class="modal hidden">
    <div class="modal-content">
        <span class="close" onclick="fecharModal()">&times;</span>
        <img id="modalImage" src="" alt="Foto do Vendedor">
    </div>
</div>

<script>
function mostrarFoto(nomeArquivo) {
    document.getElementById('modalImage').src = '/uploads/vendors/' + nomeArquivo;
    document.getElementById('fotoModal').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('fotoModal').classList.add('hidden');
}

function confirmarExclusao(id) {
    if (confirm('Tem certeza que deseja excluir esta compra?')) {
        window.location.href = 'excluir_compra.php?id=' + id;
    }
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModal();
});
</script>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    max-width: 90%;
    max-height: 90%;
}

.modal-content img {
    max-width: 100%;
    max-height: 70vh;
}

.close {
    float: right;
    font-size: 2rem;
    cursor: pointer;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}
</style>

<?php
include __DIR__ . '/../../templates/footer.php';
?>