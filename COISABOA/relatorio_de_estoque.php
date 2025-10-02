<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Filtros
$filtro_produto = $_POST['filtro_produto'] ?? '';
$filtro_estoque_baixo = $_POST['filtro_estoque_baixo'] ?? '';
$ordenacao = $_POST['ordenacao'] ?? 'produto';

// Buscar dados do estoque
$estoque = [];
$total_estoque = 0;
$total_valor_estoque = 0;
$total_valor_revenda = 0;
$produtos_estoque_baixo = 0;
$produtos_sem_estoque = 0;

try {
    $pdo = getDB();
    
    // Construir query base
    $sql = "
        SELECT 
            e.*,
            (e.quantidade * e.valor_unitario) as valor_total_estoque,
            (e.quantidade * e.valor_revenda) as valor_total_revenda,
            (e.quantidade * e.valor_revenda) - (e.quantidade * e.valor_unitario) as lucro_potencial,
            ((e.valor_revenda - e.valor_unitario) / e.valor_unitario * 100) as margem_lucro_percentual,
            COALESCE(
                (SELECT SUM(v.quantidade) 
                 FROM vendas v 
                 WHERE v.produto = e.produto 
                 AND v.data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ), 0
            ) as vendas_30_dias
        FROM estoque e
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtro_produto)) {
        $sql .= " AND e.produto LIKE ?";
        $params[] = "%$filtro_produto%";
    }
    
    if ($filtro_estoque_baixo === 'baixo') {
        $sql .= " AND e.quantidade <= 5 AND e.quantidade > 0";
    } elseif ($filtro_estoque_baixo === 'zerado') {
        $sql .= " AND e.quantidade = 0";
    }
    
    // Ordenação
    $ordenacoes_validas = [
        'produto' => 'e.produto ASC',
        'quantidade' => 'e.quantidade DESC',
        'valor_unitario' => 'e.valor_unitario DESC',
        'valor_revenda' => 'e.valor_revenda DESC',
        'lucro_potencial' => 'lucro_potencial DESC',
        'margem_lucro' => 'margem_lucro_percentual DESC',
        'vendas_recentes' => 'vendas_30_dias DESC'
    ];
    
    $ordenacao_sql = $ordenacoes_validas[$ordenacao] ?? 'e.produto ASC';
    $sql .= " ORDER BY $ordenacao_sql";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estoque = $stmt->fetchAll();
    
    // Calcular totais e estatísticas
    foreach ($estoque as $item) {
        $total_estoque += $item['quantidade'];
        $total_valor_estoque += $item['valor_total_estoque'];
        $total_valor_revenda += $item['valor_total_revenda'];
        
        if ($item['quantidade'] <= 5 && $item['quantidade'] > 0) {
            $produtos_estoque_baixo++;
        } elseif ($item['quantidade'] == 0) {
            $produtos_sem_estoque++;
        }
    }
    
    // Buscar produtos mais vendidos (para sugestões de reposição)
    $stmt_vendas = $pdo->prepare("
        SELECT 
            produto,
            SUM(quantidade) as total_vendido,
            COUNT(*) as qtd_vendas
        FROM vendas 
        WHERE data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY produto 
        ORDER BY total_vendido DESC 
        LIMIT 10
    ");
    $stmt_vendas->execute();
    $produtos_mais_vendidos = $stmt_vendas->fetchAll();
    
    // Buscar produtos que nunca venderam
    $stmt_nao_vendidos = $pdo->prepare("
        SELECT e.produto, e.quantidade, e.data_atualizacao
        FROM estoque e
        LEFT JOIN vendas v ON e.produto = v.produto
        WHERE v.id IS NULL
        ORDER BY e.data_atualizacao DESC
        LIMIT 10
    ");
    $stmt_nao_vendidos->execute();
    $produtos_nao_vendidos = $stmt_nao_vendidos->fetchAll();
    
} catch (PDOException $e) {
    $erro = "Erro ao gerar relatório: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Estoque - COISABOA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        
        .btn { 
            background: #3498db; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 14px;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .filtros { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        input, select { 
            padding: 10px 12px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            width: 100%; 
            font-size: 14px;
        }
        
        .cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        
        .card { 
            background: white; 
            padding: 25px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            border-left: 4px solid #3498db; 
        }
        .card.total { border-left-color: #3498db; }
        .card.valor { border-left-color: #27ae60; }
        .card.lucro { border-left-color: #f39c12; }
        .card.alerta { border-left-color: #e74c3c; }
        .card h3 { color: #2c3e50; margin-bottom: 10px; font-size: 14px; text-transform: uppercase; }
        .card .valor { font-size: 28px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .card .valor.total { color: #3498db; }
        .card .valor.positivo { color: #27ae60; }
        .card .valor.alerta { color: #e74c3c; }
        .card .desc { font-size: 12px; color: #7f8c8d; }
        
        .tabela { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: white; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            font-size: 14px;
        }
        .tabela th, .tabela td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ecf0f1; 
        }
        .tabela th { 
            background: #34495e; 
            color: white; 
            font-weight: 600; 
            position: sticky;
            top: 0;
        }
        .tabela tr:hover { background: #f8f9fa; }
        .tabela .estoque-alto { background: #d5f4e6; }
        .tabela .estoque-medio { background: #fffacd; }
        .tabela .estoque-baixo { background: #ffcccb; }
        .tabela .estoque-zero { background: #ffb3b3; color: #721c24; }
        .tabela .positivo { color: #27ae60; font-weight: bold; }
        .tabela .negativo { color: #e74c3c; font-weight: bold; }
        .tabela .acao { text-align: center; }
        
        .sem-dados { 
            text-align: center; 
            padding: 40px; 
            color: #7f8c8d; 
            background: white; 
            border-radius: 8px; 
            margin: 20px 0; 
        }
        
        .secao { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .secao h2 { 
            color: #2c3e50; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #ecf0f1; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .charts { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .chart-container { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        
        .alertas { 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
        }
        .alerta-item { 
            background: white; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
            border-left: 4px solid #e74c3c; 
        }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        @media (max-width: 768px) {
            .charts, .grid-2 { grid-template-columns: 1fr; }
            .cards { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .tabela { font-size: 12px; }
            .tabela th, .tabela td { padding: 8px; }
        }
        
        .badge { 
            display: inline-block; 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: bold; 
            margin-left: 5px; 
        }
        .badge-success { background: #27ae60; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Relatório de Estoque - COISABOA</h1>
            <div style="display: flex; gap: 10px;">
                <a href="relatorios.php" class="btn">📊 Relatórios</a>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d;">🏠 Dashboard</a>
                <button onclick="window.print()" class="btn btn-success">🖨️ Imprimir</button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label>🔍 Buscar Produto</label>
                        <input type="text" name="filtro_produto" value="<?= htmlspecialchars($filtro_produto) ?>" 
                               placeholder="Digite o nome do produto...">
                    </div>
                    <div class="form-group">
                        <label>📊 Filtro Estoque</label>
                        <select name="filtro_estoque_baixo">
                            <option value="">Todos os produtos</option>
                            <option value="baixo" <?= $filtro_estoque_baixo === 'baixo' ? 'selected' : '' ?>>Estoque Baixo (≤ 5)</option>
                            <option value="zerado" <?= $filtro_estoque_baixo === 'zerado' ? 'selected' : '' ?>>Estoque Zerado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>📈 Ordenar por</label>
                        <select name="ordenacao">
                            <option value="produto" <?= $ordenacao === 'produto' ? 'selected' : '' ?>>Nome do Produto</option>
                            <option value="quantidade" <?= $ordenacao === 'quantidade' ? 'selected' : '' ?>>Quantidade</option>
                            <option value="valor_unitario" <?= $ordenacao === 'valor_unitario' ? 'selected' : '' ?>>Valor Unitário</option>
                            <option value="valor_revenda" <?= $ordenacao === 'valor_revenda' ? 'selected' : '' ?>>Valor Revenda</option>
                            <option value="lucro_potencial" <?= $ordenacao === 'lucro_potencial' ? 'selected' : '' ?>>Lucro Potencial</option>
                            <option value="margem_lucro" <?= $ordenacao === 'margem_lucro' ? 'selected' : '' ?>>Margem de Lucro</option>
                            <option value="vendas_recentes" <?= $ordenacao === 'vendas_recentes' ? 'selected' : '' ?>>Vendas Recentes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" style="height: 42px;">🔍 Aplicar Filtros</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (isset($erro)): ?>
            <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <!-- Alertas de Estoque -->
        <?php if ($produtos_estoque_baixo > 0 || $produtos_sem_estoque > 0): ?>
        <div class="alertas">
            <h3>⚠️ Alertas de Estoque</h3>
            <?php if ($produtos_estoque_baixo > 0): ?>
                <div class="alerta-item">
                    <strong>📉 Estoque Baixo:</strong> <?= $produtos_estoque_baixo ?> produto(s) com estoque ≤ 5 unidades
                </div>
            <?php endif; ?>
            <?php if ($produtos_sem_estoque > 0): ?>
                <div class="alerta-item">
                    <strong>❌ Estoque Zerado:</strong> <?= $produtos_sem_estoque ?> produto(s) sem estoque
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Cards de Resumo -->
        <div class="cards">
            <div class="card total">
                <h3>📦 Total em Estoque</h3>
                <div class="valor total"><?= $total_estoque ?></div>
                <div class="desc">Unidades disponíveis</div>
            </div>
            <div class="card valor">
                <h3>💰 Valor do Estoque</h3>
                <div class="valor positivo">R$ <?= number_format($total_valor_estoque, 2, ',', '.') ?></div>
                <div class="desc">Custo total do estoque</div>
            </div>
            <div class="card lucro">
                <h3>💸 Valor de Revenda</h3>
                <div class="valor">R$ <?= number_format($total_valor_revenda, 2, ',', '.') ?></div>
                <div class="desc">Valor potencial de venda</div>
            </div>
            <div class="card alerta">
                <h3>📊 Lucro Potencial</h3>
                <div class="valor positivo">R$ <?= number_format($total_valor_revenda - $total_valor_estoque, 2, ',', '.') ?></div>
                <div class="desc">Diferença entre revenda e custo</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts">
            <div class="chart-container">
                <h3>📈 Distribuição por Categoria de Estoque</h3>
                <canvas id="estoqueChart" height="250"></canvas>
            </div>
            <div class="chart-container">
                <h3>💰 Valor por Produto (Top 10)</h3>
                <canvas id="valorChart" height="250"></canvas>
            </div>
        </div>

        <!-- Produtos Mais Vendidos vs Não Vendidos -->
        <div class="grid-2">
            <div class="secao">
                <h2>🏆 Produtos Mais Vendidos (30 dias)</h2>
                <?php if (!empty($produtos_mais_vendidos)): ?>
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Vendidos</th>
                            <th>Vendas</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_mais_vendidos as $produto): ?>
                        <tr>
                            <td><?= htmlspecialchars($produto['produto']) ?></td>
                            <td><?= $produto['total_vendido'] ?></td>
                            <td><?= $produto['qtd_vendas'] ?></td>
                            <td class="acao">
                                <a href="comprar.php?produto=<?= urlencode($produto['produto']) ?>" 
                                   class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">
                                    Repor
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="sem-dados">
                    <h3>📭 Nenhuma venda nos últimos 30 dias</h3>
                </div>
                <?php endif; ?>
            </div>

            <div class="secao">
                <h2>📭 Produtos Não Vendidos</h2>
                <?php if (!empty($produtos_nao_vendidos)): ?>
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Estoque</th>
                            <th>Última Atualização</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_nao_vendidos as $produto): 
                            $classe_estoque = '';
                            if ($produto['quantidade'] == 0) $classe_estoque = 'estoque-zero';
                            elseif ($produto['quantidade'] <= 5) $classe_estoque = 'estoque-baixo';
                        ?>
                        <tr class="<?= $classe_estoque ?>">
                            <td><?= htmlspecialchars($produto['produto']) ?></td>
                            <td><?= $produto['quantidade'] ?></td>
                            <td><?= $produto['data_atualizacao'] ? date('d/m/Y', strtotime($produto['data_atualizacao'])) : 'Nunca' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="sem-dados">
                    <h3>✅ Todos os produtos já foram vendidos</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estoque Completo -->
        <div class="secao">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                <h2>📋 Estoque Completo (<?= count($estoque) ?> produtos)</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span class="badge badge-success">Alto (> 10)</span>
                    <span class="badge badge-warning">Médio (6-10)</span>
                    <span class="badge badge-danger">Baixo (1-5)</span>
                    <span class="badge badge-info">Zerado (0)</span>
                </div>
            </div>
            
            <?php if (!empty($estoque)): ?>
            <div style="overflow-x: auto;">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Estoque</th>
                            <th>Custo Unit.</th>
                            <th>Revenda Unit.</th>
                            <th>Valor Total Estoque</th>
                            <th>Valor Total Revenda</th>
                            <th>Lucro Potencial</th>
                            <th>Margem</th>
                            <th>Vendas (30d)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estoque as $item): 
                            $classe_estoque = '';
                            if ($item['quantidade'] == 0) {
                                $classe_estoque = 'estoque-zero';
                            } elseif ($item['quantidade'] <= 5) {
                                $classe_estoque = 'estoque-baixo';
                            } elseif ($item['quantidade'] <= 10) {
                                $classe_estoque = 'estoque-medio';
                            } else {
                                $classe_estoque = 'estoque-alto';
                            }
                            
                            $margem_lucro = $item['margem_lucro_percentual'] ?? 0;
                            $classe_margem = $margem_lucro >= 0 ? 'positivo' : 'negativo';
                        ?>
                        <tr class="<?= $classe_estoque ?>">
                            <td>
                                <strong><?= htmlspecialchars($item['produto']) ?></strong>
                                <?php if ($item['quantidade'] == 0): ?>
                                    <span class="badge badge-info">ZERADO</span>
                                <?php elseif ($item['quantidade'] <= 5): ?>
                                    <span class="badge badge-danger">BAIXO</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $item['quantidade'] ?></strong></td>
                            <td>R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['valor_revenda'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['valor_total_estoque'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['valor_total_revenda'], 2, ',', '.') ?></td>
                            <td class="<?= $item['lucro_potencial'] >= 0 ? 'positivo' : 'negativo' ?>">
                                R$ <?= number_format($item['lucro_potencial'], 2, ',', '.') ?>
                            </td>
                            <td class="<?= $classe_margem ?>">
                                <?= number_format($margem_lucro, 1, ',', '.') ?>%
                            </td>
                            <td><?= $item['vendas_30_dias'] ?></td>
                            <td class="acao">
                                <div style="display: flex; gap: 5px;">
                                    <a href="comprar.php?produto=<?= urlencode($item['produto']) ?>" 
                                       class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">
                                        Comprar
                                    </a>
                                    <a href="vender.php?produto_id=<?= $item['id'] ?>" 
                                       class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">
                                        Vender
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="sem-dados">
                <h3>📭 Nenhum produto encontrado com os filtros aplicados</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Dados para gráficos
        const categoriasEstoque = {
            'Zerado': <?= $produtos_sem_estoque ?>,
            'Baixo (1-5)': <?= $produtos_estoque_baixo ?>,
            'Médio (6-10)': <?= count(array_filter($estoque, fn($item) => $item['quantidade'] > 5 && $item['quantidade'] <= 10)) ?>,
            'Alto (>10)': <?= count(array_filter($estoque, fn($item) => $item['quantidade'] > 10)) ?>
        };

        // Gráfico de Distribuição de Estoque
        new Chart(document.getElementById('estoqueChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(categoriasEstoque),
                datasets: [{
                    data: Object.values(categoriasEstoque),
                    backgroundColor: ['#e74c3c', '#f39c12', '#3498db', '#27ae60']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Gráfico de Valor por Produto (Top 10)
        const topProdutos = <?= json_encode(array_slice($estoque, 0, 10)) ?>;
        new Chart(document.getElementById('valorChart'), {
            type: 'bar',
            data: {
                labels: topProdutos.map(p => p.produto.substring(0, 15) + (p.produto.length > 15 ? '...' : '')),
                datasets: [{
                    label: 'Valor do Estoque (R$)',
                    data: topProdutos.map(p => p.valor_total_estoque),
                    backgroundColor: '#3498db'
                }, {
                    label: 'Valor de Revenda (R$)',
                    data: topProdutos.map(p => p.valor_total_revenda),
                    backgroundColor: '#27ae60'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>