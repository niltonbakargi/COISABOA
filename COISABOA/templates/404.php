<?php
/**
 * COISABOA - Página 404 (Não Encontrada)
 * @version 1.0.0
 */

http_response_code(404);
$page_title = 'Página Não Encontrada - COISABOA';
$body_class = 'error-page';
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="error-container text-center">
        <div class="error-icon">🔍</div>
        <h1>Página Não Encontrada</h1>
        <p>A página que você está procurando não existe ou foi movida.</p>
        
        <div class="error-actions" style="margin-top: 2rem;">
            <a href="/" class="btn btn-primary">🏠 Voltar para o Dashboard</a>
            <button onclick="history.back()" class="btn btn-outline">↩️ Voltar</button>
        </div>
        
        <div class="error-help" style="margin-top: 3rem; padding: 1.5rem; background: var(--cor-cinza-100); border-radius: var(--raio-borda);">
            <h3>Precisa de ajuda?</h3>
            <p>Se você acredita que isso é um erro, verifique o URL ou entre em contato com o suporte.</p>
        </div>
    </div>
</div>

<style>
.error-container {
    max-width: 600px;
    margin: 4rem auto;
    padding: 2rem;
}

.error-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-actions .btn {
        width: 200px;
    }
}
</style>

<?php include 'footer.php'; ?>