<?php
/**
 * COISABOA - Template do Rodapé
 * @version 1.0.0
 */
?>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Informações do Sistema -->
            <div class="footer-section">
                <div class="logo">
                    <div class="logo-icon">📱</div>
                    <h2 class="logo-text">COISABOA</h2>
                </div>
                <p class="footer-description">
                    Sistema de controle comercial simplificado para pequenos negócios
                </p>
                <div class="footer-meta">
                    <span>Versão <?= SISTEMA_VERSION ?></span>
                    <span>•</span>
                    <span><?= SISTEMA_ANO ?></span>
                </div>
            </div>
            
            <!-- Links Rápidos -->
            <div class="footer-section">
                <h3 class="footer-title">Navegação</h3>
                <ul class="footer-links">
                    <li><a href="/">Dashboard</a></li>
                    <li><a href="/modules/purchases/comprei.php">Nova Compra</a></li>
                    <li><a href="/modules/sales/vendi.php">Nova Venda</a></li>
                    <li><a href="/modules/inventory/estoque.php">Ver Estoque</a></li>
                </ul>
            </div>
            
            <!-- Estatísticas Rápidas -->
            <div class="footer-section">
                <h3 class="footer-title">Estatísticas</h3>
                <div class="footer-stats">
                    <?php
                    try {
                        $total_produtos = dbFind("SELECT COUNT(DISTINCT produto) as total FROM compras")['total'] ?? 0;
                        $total_movimentacoes = dbFind("SELECT COUNT(*) as total FROM (SELECT id FROM compras UNION ALL SELECT id FROM vendas) as movimentos")['total'] ?? 0;
                    } catch (Exception $e) {
                        $total_produtos = 0;
                        $total_movimentacoes = 0;
                    }
                    ?>
                    <div class="stat">
                        <span class="stat-number"><?= $total_produtos ?></span>
                        <span class="stat-label">Produtos</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?= $total_movimentacoes ?></span>
                        <span class="stat-label">Movimentações</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> COISABOA - Todos os direitos reservados</p>
            <p class="footer-credits">
                Desenvolvido com ❤️ para pequenos comerciantes
            </p>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script src="/assets/js/app.js"></script>
<script src="/assets/js/charts.js"></script>

<!-- Script específico da página -->
<?php if (isset($page_script) && $page_script): ?>
    <script src="/assets/js/<?= $page_script ?>.js"></script>
<?php endif; ?>

<!-- Inicialização -->
<script>
// Configurações globais
const COISABOA_CONFIG = {
    baseUrl: '<?= isset($_SERVER['HTTPS']) ? 'https://' : 'http://' ?><?= $_SERVER['HTTP_HOST'] ?>',
    currentPage: '<?= $current_page ?>',
    version: '<?= SISTEMA_VERSION ?>'
};

// Inicializar componentes específicos da página
document.addEventListener('DOMContentLoaded', function() {
    // Menu mobile
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Auto-remover flash messages após 5 segundos
    setTimeout(() => {
        document.querySelectorAll('.flash-message').forEach(msg => {
            msg.style.animation = 'fadeOut 0.3s ease-in-out';
            setTimeout(() => msg.remove(), 300);
        });
    }, 5000);
    
    // Mostrar loading em links e forms
    document.querySelectorAll('a, form').forEach(element => {
        element.addEventListener('click', function(e) {
            // Não mostrar loading para links que abrem em nova aba
            if (this.target === '_blank') return;
            
            // Não mostrar loading para links âncora
            if (this.getAttribute('href')?.startsWith('#')) return;
            
            showLoading();
        });
    });
});

// Funções utilitárias globais
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

// Interceptar erros JavaScript
window.addEventListener('error', function(e) {
    console.error('Erro JavaScript:', e.error);
    <?php if (is_dev_environment()): ?>
        alert('Erro JavaScript: ' + e.error.message);
    <?php endif; ?>
});

// Prevenir saída acidental da página com forms preenchidos
window.addEventListener('beforeunload', function(e) {
    const forms = document.querySelectorAll('form');
    let hasData = false;
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.value && !input.disabled) {
                hasData = true;
            }
        });
    });
    
    if (hasData) {
        e.preventDefault();
        e.returnValue = 'Você tem alterações não salvas. Tem certeza que deseja sair?';
        return e.returnValue;
    }
});

// Service Worker para PWA (futura implementação)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registrado com sucesso: ', registration.scope);
            })
            .catch(function(error) {
                console.log('Falha no registro do ServiceWorker: ', error);
            });
    });
}
</script>

</body>
</html>