<?php
/**
 * COISABOA - Criador Especializado do Sistema de Assets
 * Arquivo: create_assets.php
 * Descrição: Cria estrutura completa da pasta assets com CSS, JavaScript e recursos visuais
 */

echo "🎨 INICIANDO CRIAÇÃO DO SISTEMA DE ASSETS...\n";
echo "=============================================\n";

// Configurações
$project_root = __DIR__ . '/COISABOA';
$assets_dir = $project_root . '/assets';

// Verificar se a pasta principal existe
if (!file_exists($project_root)) {
    echo "❌ ERRO: Pasta principal COISABOA não encontrada!\n";
    echo "Execute primeiro o create_structure.php\n";
    exit(1);
}

// Criar estrutura de pastas de assets
$assets_folders = [
    'css/' => 'Folhas de estilo',
    'js/' => 'Scripts JavaScript',
    'images/' => 'Imagens do sistema',
    'icons/' => 'Ícones e favicons',
    'fonts/' => 'Fontes customizadas',
    'scss/' => 'Arquivos SCSS (futuro)'
];

// Criar pasta assets principal
if (!file_exists($assets_dir)) {
    mkdir($assets_dir, 0755, true);
    echo "✅ Pasta assets criada: $assets_dir\n";
} else {
    echo "📁 Pasta assets já existe: $assets_dir\n";
}

// Criar subpastas
echo "\n📂 CRIANDO SUBPASTAS DE ASSETS...\n";
foreach ($assets_folders as $folder => $description) {
    $folder_path = $assets_dir . '/' . $folder;
    
    if (!file_exists($folder_path)) {
        mkdir($folder_path, 0755, true);
        echo "✅ $description: $folder\n";
    } else {
        echo "📁 $description já existe: $folder\n";
    }
}

// Arquivos CSS principais
$css_files = [
    'style.css' => "/*!
 * COISABOA - Stylesheet Principal
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

/* ==================== VARIÁVEIS CSS ==================== */
:root {
    /* Cores principais */
    --cor-primaria: #667eea;
    --cor-primaria-escura: #5a6fd8;
    --cor-primaria-clara: #8a9ef0;
    --cor-secundaria: #764ba2;
    --cor-secundaria-escura: #6a4190;
    --cor-secundaria-clara: #8a65b0;
    
    /* Cores de status */
    --cor-sucesso: #10b981;
    --cor-alerta: #f59e0b;
    --cor-erro: #ef4444;
    --cor-info: #3b82f6;
    --cor-aviso: #f97316;
    
    /* Cores neutras */
    --cor-branco: #ffffff;
    --cor-cinza-50: #f9fafb;
    --cor-cinza-100: #f3f4f6;
    --cor-cinza-200: #e5e7eb;
    --cor-cinza-300: #d1d5db;
    --cor-cinza-400: #9ca3af;
    --cor-cinza-500: #6b7280;
    --cor-cinza-600: #4b5563;
    --cor-cinza-700: #374151;
    --cor-cinza-800: #1f2937;
    --cor-cinza-900: #111827;
    --cor-preto: #000000;
    
    /* Gradientes */
    --gradiente-principal: linear-gradient(135deg, var(--cor-primaria) 0%, var(--cor-secundaria) 100%);
    --gradiente-sucesso: linear-gradient(135deg, var(--cor-sucesso) 0%, #059669 100%);
    --gradiente-alerta: linear-gradient(135deg, var(--cor-alerta) 0%, #d97706 100%);
    
    /* Tipografia */
    --fonte-principal: 'Segoe UI', system-ui, -apple-system, sans-serif;
    --fonte-secundaria: 'Inter', sans-serif;
    --fonte-mono: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    
    /* Tamanhos de fonte */
    --texto-xs: 0.75rem;
    --texto-sm: 0.875rem;
    --texto-base: 1rem;
    --texto-lg: 1.125rem;
    --texto-xl: 1.25rem;
    --texto-2xl: 1.5rem;
    --texto-3xl: 1.875rem;
    --texto-4xl: 2.25rem;
    
    /* Espaçamentos */
    --espaco-1: 0.25rem;
    --espaco-2: 0.5rem;
    --espaco-3: 0.75rem;
    --espaco-4: 1rem;
    --espaco-5: 1.25rem;
    --espaco-6: 1.5rem;
    --espaco-8: 2rem;
    --espaco-10: 2.5rem;
    --espaco-12: 3rem;
    
    /* Bordas e sombras */
    --raio-borda: 0.75rem;
    --raio-borda-sm: 0.5rem;
    --raio-borda-lg: 1rem;
    --sombra-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --sombra: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --sombra-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    
    /* Transições */
    --transicao-rapida: all 0.15s ease-in-out;
    --transicao-normal: all 0.3s ease-in-out;
    --transicao-lenta: all 0.5s ease-in-out;
}

/* ==================== RESET E BASE ==================== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    font-size: 16px;
    scroll-behavior: smooth;
}

body {
    font-family: var(--fonte-principal);
    line-height: 1.6;
    color: var(--cor-cinza-800);
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

/* ==================== TIPOGRAFIA ==================== */
h1, h2, h3, h4, h5, h6 {
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: var(--espaco-4);
    color: var(--cor-cinza-900);
}

h1 { font-size: var(--texto-4xl); }
h2 { font-size: var(--texto-3xl); }
h3 { font-size: var(--texto-2xl); }
h4 { font-size: var(--texto-xl); }
h5 { font-size: var(--texto-lg); }
h6 { font-size: var(--texto-base); }

p {
    margin-bottom: var(--espaco-4);
    color: var(--cor-cinza-700);
}

a {
    color: var(--cor-primaria);
    text-decoration: none;
    transition: var(--transicao-rapida);
}

a:hover {
    color: var(--cor-primaria-escura);
}

/* ==================== LAYOUT PRINCIPAL ==================== */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--espaco-4);
}

.header {
    background: var(--gradiente-principal);
    color: var(--cor-branco);
    padding: var(--espaco-6) 0;
    box-shadow: var(--sombra);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: var(--espaco-3);
}

.logo-icon {
    font-size: var(--texto-3xl);
}

.logo-text {
    font-size: var(--texto-2xl);
    font-weight: 700;
}

.main-content {
    padding: var(--espaco-8) 0;
    min-height: calc(100vh - 200px);
}

.footer {
    background: var(--cor-cinza-800);
    color: var(--cor-cinza-200);
    padding: var(--espaco-6) 0;
    text-align: center;
}

/* ==================== CARDS E GRIDS ==================== */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--espaco-6);
    margin-bottom: var(--espaco-8);
}

.card {
    background: var(--cor-branco);
    border-radius: var(--raio-borda);
    padding: var(--espaco-6);
    box-shadow: var(--sombra);
    transition: var(--transicao-normal);
    border: 1px solid var(--cor-cinza-200);
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: var(--sombra-lg);
}

.card-header {
    display: flex;
    align-items: center;
    gap: var(--espaco-3);
    margin-bottom: var(--espaco-4);
}

.card-icon {
    font-size: var(--texto-3xl);
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gradiente-principal);
    border-radius: var(--raio-borda);
    color: var(--cor-branco);
}

.card-title {
    font-size: var(--texto-xl);
    font-weight: 600;
    color: var(--cor-cinza-900);
    margin: 0;
}

.card-description {
    color: var(--cor-cinza-600);
    margin-bottom: var(--espaco-5);
}

/* ==================== BOTÕES ==================== */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--espaco-2);
    padding: var(--espaco-3) var(--espaco-5);
    border: none;
    border-radius: var(--raio-borda-sm);
    font-family: inherit;
    font-size: var(--texto-base);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transicao-rapida);
    position: relative;
    overflow: hidden;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: var(--gradiente-principal);
    color: var(--cor-branco);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: var(--sombra);
}

.btn-success {
    background: var(--gradiente-sucesso);
    color: var(--cor-branco);
}

.btn-danger {
    background: var(--cor-erro);
    color: var(--cor-branco);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--cor-primaria);
    color: var(--cor-primaria);
}

.btn-outline:hover {
    background: var(--cor-primaria);
    color: var(--cor-branco);
}

.btn-sm {
    padding: var(--espaco-2) var(--espaco-3);
    font-size: var(--texto-sm);
}

.btn-lg {
    padding: var(--espaco-4) var(--espaco-6);
    font-size: var(--texto-lg);
}

.btn-full {
    width: 100%;
}

/* ==================== FORMULÁRIOS ==================== */
.form-group {
    margin-bottom: var(--espaco-5);
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: var(--espaco-2);
    color: var(--cor-cinza-700);
}

.form-control {
    width: 100%;
    padding: var(--espaco-3) var(--espaco-4);
    border: 2px solid var(--cor-cinza-300);
    border-radius: var(--raio-borda-sm);
    font-family: inherit;
    font-size: var(--texto-base);
    transition: var(--transicao-rapida);
    background: var(--cor-branco);
}

.form-control:focus {
    outline: none;
    border-color: var(--cor-primaria);
    box-shadow: 0 0 0 3px rgb(102 126 234 / 0.1);
}

.form-control:disabled {
    background: var(--cor-cinza-100);
    cursor: not-allowed;
}

.form-text {
    display: block;
    margin-top: var(--espaco-2);
    font-size: var(--texto-sm);
    color: var(--cor-cinza-500);
}

.form-check {
    display: flex;
    align-items: center;
    gap: var(--espaco-2);
    margin-bottom: var(--espaco-3);
}

/* ==================== TABELAS ==================== */
.table-container {
    background: var(--cor-branco);
    border-radius: var(--raio-borda);
    overflow: hidden;
    box-shadow: var(--sombra);
    margin-bottom: var(--espaco-6);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: var(--gradiente-principal);
    color: var(--cor-branco);
    padding: var(--espaco-4);
    text-align: left;
    font-weight: 600;
}

.table td {
    padding: var(--espaco-4);
    border-bottom: 1px solid var(--cor-cinza-200);
}

.table tbody tr:hover {
    background: var(--cor-cinza-50);
}

.table tbody tr:last-child td {
    border-bottom: none;
}

/* ==================== COMPONENTES ESPECÍFICOS ==================== */
.estoque-badge {
    display: inline-flex;
    align-items: center;
    padding: var(--espaco-1) var(--espaco-3);
    border-radius: 9999px;
    font-size: var(--texto-sm);
    font-weight: 500;
}

.estoque-normal {
    background: var(--cor-sucesso);
    color: var(--cor-branco);
}

.estoque-baixo {
    background: var(--cor-alerta);
    color: var(--cor-branco);
}

.estoque-critico {
    background: var(--cor-erro);
    color: var(--cor-branco);
}

.flash-messages {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    max-width: 400px;
}

.flash-message {
    padding: var(--espaco-4);
    border-radius: var(--raio-borda-sm);
    margin-bottom: var(--espaco-3);
    box-shadow: var(--sombra-lg);
    animation: slideInRight 0.3s ease-out;
}

.flash-success {
    background: var(--cor-sucesso);
    color: var(--cor-branco);
}

.flash-error {
    background: var(--cor-erro);
    color: var(--cor-branco);
}

.flash-warning {
    background: var(--cor-alerta);
    color: var(--cor-branco);
}

.flash-info {
    background: var(--cor-info);
    color: var(--cor-branco);
}

/* ==================== ANIMAÇÕES ==================== */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.fade-in {
    animation: fadeIn 0.5s ease-out;
}

.pulse {
    animation: pulse 2s infinite;
}

/* ==================== RESPONSIVIDADE ==================== */
@media (max-width: 768px) {
    .container {
        padding: 0 var(--espaco-3);
    }
    
    .header-content {
        flex-direction: column;
        gap: var(--espaco-4);
        text-align: center;
    }
    
    .cards-grid {
        grid-template-columns: 1fr;
        gap: var(--espaco-4);
    }
    
    .card {
        padding: var(--espaco-4);
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table {
        min-width: 600px;
    }
    
    .flash-messages {
        left: var(--espaco-3);
        right: var(--espaco-3);
        max-width: none;
    }
}

@media (max-width: 480px) {
    html {
        font-size: 14px;
    }
    
    .main-content {
        padding: var(--espaco-4) 0;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* ==================== UTILITÁRIOS ==================== */
.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

.mt-1 { margin-top: var(--espaco-1); }
.mt-2 { margin-top: var(--espaco-2); }
.mt-3 { margin-top: var(--espaco-3); }
.mt-4 { margin-top: var(--espaco-4); }
.mt-5 { margin-top: var(--espaco-5); }

.mb-1 { margin-bottom: var(--espaco-1); }
.mb-2 { margin-bottom: var(--espaco-2); }
.mb-3 { margin-bottom: var(--espaco-3); }
.mb-4 { margin-bottom: var(--espaco-4); }
.mb-5 { margin-bottom: var(--espaco-5); }

.hidden { display: none; }
.sr-only { 
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
",

    'dashboard.css' => "/*!
 * COISABOA - Stylesheet do Dashboard
 * Componentes específicos para a página inicial
 */

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--espaco-5);
    margin-bottom: var(--espaco-8);
}

.stat-card {
    background: var(--cor-branco);
    padding: var(--espaco-5);
    border-radius: var(--raio-borda);
    box-shadow: var(--sombra);
    text-align: center;
    transition: var(--transicao-normal);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--sombra-lg);
}

.stat-icon {
    font-size: var(--texto-3xl);
    margin-bottom: var(--espaco-3);
    opacity: 0.8;
}

.stat-number {
    font-size: var(--texto-3xl);
    font-weight: 700;
    color: var(--cor-primaria);
    margin-bottom: var(--espaco-1);
}

.stat-label {
    color: var(--cor-cinza-600);
    font-size: var(--texto-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.recent-activity {
    background: var(--cor-branco);
    border-radius: var(--raio-borda);
    padding: var(--espaco-5);
    box-shadow: var(--sombra);
}

.activity-item {
    display: flex;
    align-items: center;
    gap: var(--espaco-3);
    padding: var(--espaco-3) 0;
    border-bottom: 1px solid var(--cor-cinza-200);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--texto-lg);
}

.activity-icon-compra {
    background: var(--cor-sucesso);
    color: var(--cor-branco);
}

.activity-icon-venda {
    background: var(--cor-info);
    color: var(--cor-branco);
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    margin-bottom: var(--espaco-1);
}

.activity-time {
    font-size: var(--texto-sm);
    color: var(--cor-cinza-500);
}
",

    'forms.css' => "/*!
 * COISABOA - Stylesheet de Formulários
 * Estilos específicos para formulários
 */

.form-container {
    max-width: 600px;
    margin: 0 auto;
    background: var(--cor-branco);
    padding: var(--espaco-6);
    border-radius: var(--raio-borda);
    box-shadow: var(--sombra);
}

.form-title {
    text-align: center;
    margin-bottom: var(--espaco-6);
    color: var(--cor-primaria);
}

.form-actions {
    display: flex;
    gap: var(--espaco-3);
    margin-top: var(--espaco-6);
}

.form-actions .btn {
    flex: 1;
}

.file-upload {
    border: 2px dashed var(--cor-cinza-300);
    border-radius: var(--raio-borda-sm);
    padding: var(--espaco-6);
    text-align: center;
    transition: var(--transicao-normal);
    cursor: pointer;
}

.file-upload:hover {
    border-color: var(--cor-primaria);
    background: var(--cor-cinza-50);
}

.file-upload.dragover {
    border-color: var(--cor-primaria);
    background: var(--cor-cinza-100);
}

.file-preview {
    margin-top: var(--espaco-3);
    text-align: center;
}

.file-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: var(--raio-borda-sm);
    box-shadow: var(--sombra);
}

.validation-error {
    color: var(--cor-erro);
    font-size: var(--texto-sm);
    margin-top: var(--espaco-1);
    display: flex;
    align-items: center;
    gap: var(--espaco-1);
}

.validation-error::before {
    content: '⚠️';
}

.form-success {
    background: var(--cor-sucesso);
    color: var(--cor-branco);
    padding: var(--espaco-4);
    border-radius: var(--raio-borda-sm);
    margin-bottom: var(--espaco-4);
    text-align: center;
}
",

    'responsive-tables.css' => "/*!
 * COISABOA - Tabelas Responsivas
 * Adaptação de tabelas para mobile
 */

@media (max-width: 768px) {
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table {
        min-width: 600px;
    }
    
    .table th,
    .table td {
        white-space: nowrap;
    }
}

/* Estilo para tabelas em cards no mobile */
@media (max-width: 480px) {
    .table-card-view {
        display: grid;
        gap: var(--espaco-3);
    }
    
    .table-card-item {
        background: var(--cor-branco);
        border-radius: var(--raio-borda-sm);
        padding: var(--espaco-4);
        box-shadow: var(--sombra-sm);
    }
    
    .table-card-row {
        display: flex;
        justify-content: space-between;
        padding: var(--espaco-2) 0;
        border-bottom: 1px solid var(--cor-cinza-200);
    }
    
    .table-card-row:last-child {
        border-bottom: none;
    }
    
    .table-card-label {
        font-weight: 600;
        color: var(--cor-cinza-700);
    }
    
    .table-card-value {
        color: var(--cor-cinza-600);
    }
}
"
];

// Arquivos JavaScript
$js_files = [
    'app.js' => "/*!
 * COISABOA - JavaScript Principal
 * @version 1.0.0
 * @author Sistema Coisa Boa
 */

// ==================== CONFIGURAÇÕES GLOBAIS ====================
const COISABOA = {
    version: '1.0.0',
    config: {
        apiBase: window.location.origin,
        debug: window.location.hostname === 'localhost'
    }
};

// ==================== UTILITÁRIOS ====================
class Utils {
    // Debounce para otimizar eventos
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Formatação de valores
    static formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    static formatDate(date) {
        return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
    }

    // Validações
    static isValidEmail(email) {
        const regex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
        return regex.test(email);
    }

    static isValidNumber(value) {
        return !isNaN(parseFloat(value)) && isFinite(value);
    }

    // Manipulação de DOM
    static showElement(selector) {
        const element = document.querySelector(selector);
        if (element) element.classList.remove('hidden');
    }

    static hideElement(selector) {
        const element = document.querySelector(selector);
        if (element) element.classList.add('hidden');
    }

    // Notificações
    static showNotification(message, type = 'info') {
        this.createFlashMessage(message, type);
    }

    static createFlashMessage(message, type) {
        const flashContainer = document.getElementById('flash-messages') || this.createFlashContainer();
        const flashId = 'flash-' + Date.now();
        
        const flashHTML = `
            <div id=\"${flashId}\" class=\"flash-message flash-${type}\">
                <span class=\"flash-text\">${message}</span>
                <button class=\"flash-close\" onclick=\"Utils.closeFlash('${flashId}')\">×</button>
            </div>
        `;
        
        flashContainer.insertAdjacentHTML('beforeend', flashHTML);
        
        // Auto-remove após 5 segundos
        setTimeout(() => {
            this.closeFlash(flashId);
        }, 5000);
    }

    static createFlashContainer() {
        const container = document.createElement('div');
        container.id = 'flash-messages';
        container.className = 'flash-messages';
        document.body.appendChild(container);
        return container;
    }

    static closeFlash(flashId) {
        const flash = document.getElementById(flashId);
        if (flash) {
            flash.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => flash.remove(), 300);
        }
    }
}

// ==================== GERENCIADOR DE FORMULÁRIOS ====================
class FormManager {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.fields = {};
        this.errors = {};
        
        if (this.form) {
            this.init();
        }
    }

    init() {
        // Configurar validações em tempo real
        this.form.querySelectorAll('[data-validation]').forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearFieldError(field));
        });

        // Prevenir submit duplo
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    validateField(field) {
        const value = field.value.trim();
        const validationType = field.dataset.validation;
        let isValid = true;
        let errorMessage = '';

        switch (validationType) {
            case 'required':
                isValid = value !== '';
                errorMessage = 'Este campo é obrigatório';
                break;
            case 'email':
                isValid = Utils.isValidEmail(value);
                errorMessage = 'Email inválido';
                break;
            case 'number':
                isValid = Utils.isValidNumber(value);
                errorMessage = 'Deve ser um número válido';
                break;
            case 'min-length':
                const minLength = parseInt(field.dataset.minLength);
                isValid = value.length >= minLength;
                errorMessage = `Mínimo ${minLength} caracteres`;
                break;
        }

        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }

        return isValid;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.textContent = message;
        
        field.classList.add('error');
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.validation-error');
        if (existingError) {
            existingError.remove();
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        // Validar todos os campos
        const fields = this.form.querySelectorAll('[data-validation]');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        if (!isValid) {
            Utils.showNotification('Por favor, corrija os erros no formulário', 'error');
            return;
        }

        // Prevenir submit duplo
        const submitBtn = this.form.querySelector('button[type=\"submit\"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processando...';

        try {
            await this.submitForm();
        } catch (error) {
            console.error('Erro no submit:', error);
            Utils.showNotification('Erro ao processar formulário', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    async submitForm() {
        const formData = new FormData(this.form);
        
        // Simular envio (substituir por fetch real)
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        Utils.showNotification('Operação realizada com sucesso!', 'success');
        this.form.reset();
    }
}

// ==================== GERENCIADOR DE INTERFACE ====================
class UIManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupMobileMenu();
        this.setupAutoSave();
    }

    setupEventListeners() {
        // Tooltips
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip);
            element.addEventListener('mouseleave', this.hideTooltip);
        });

        // Confirmações de ação
        document.querySelectorAll('[data-confirm]').forEach(button => {
            button.addEventListener('click', (e) => this.handleConfirmAction(e));
        });
    }

    setupMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('.main-nav');

        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', () => {
                mainNav.classList.toggle('active');
                menuToggle.classList.toggle('active');
            });
        }
    }

    setupAutoSave() {
        // Auto-save para formulários longos
        document.querySelectorAll('[data-autosave]').forEach(form => {
            const debouncedSave = Utils.debounce(() => {
                this.autoSaveForm(form);
            }, 1000);

            form.addEventListener('input', debouncedSave);
        });
    }

    showTooltip(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = e.target.dataset.tooltip;
        
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    }

    hideTooltip(e) {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    handleConfirmAction(e) {
        const message = e.target.dataset.confirm || 'Tem certeza que deseja realizar esta ação?';
        
        if (!confirm(message)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    autoSaveForm(form) {
        console.log('Auto-saving form...');
        // Implementar lógica de auto-save
    }
}

// ==================== GERENCIADOR DE ESTOQUE ====================
class StockManager {
    constructor() {
        this.currentStock = {};
        this.init();
    }

    init() {
        this.loadStockData();
        this.setupStockAlerts();
    }

    async loadStockData() {
        try {
            // Simular carregamento de dados
            await new Promise(resolve => setTimeout(resolve, 500));
            this.updateStockDisplay();
        } catch (error) {
            console.error('Erro ao carregar estoque:', error);
        }
    }

    updateStockDisplay() {
        document.querySelectorAll('[data-stock-level]').forEach(element => {
            const product = element.dataset.product;
            const stock = this.currentStock[product] || 0;
            
            element.textContent = stock;
            element.className = this.getStockLevelClass(stock);
        });
    }

    getStockLevelClass(stock) {
        if (stock === 0) return 'estoque-critico';
        if (stock <= 5) return 'estoque-baixo';
        return 'estoque-normal';
    }

    setupStockAlerts() {
        // Verificar estoques baixos periodicamente
        setInterval(() => {
            this.checkLowStock();
        }, 30000); // A cada 30 segundos
    }

    checkLowStock() {
        Object.entries(this.currentStock).forEach(([product, stock]) => {
            if (stock <= 5) {
                this.showStockAlert(product, stock);
            }
        });
    }

    showStockAlert(product, stock) {
        if (!this.alreadyAlerted(product)) {
            Utils.showNotification(
                `Estoque baixo: ${product} (${stock} unidades)`,
                'warning'
            );
            this.markAsAlerted(product);
        }
    }

    alreadyAlerted(product) {
        return sessionStorage.getItem(`alerted-${product}`) === 'true';
    }

    markAsAlerted(product) {
        sessionStorage.setItem(`alerted-${product}`, 'true');
    }
}

// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gerenciadores
    window.uiManager = new UIManager();
    window.stockManager = new StockManager();

    // Inicializar formulários
    document.querySelectorAll('form[data-managed]').forEach(form => {
        new FormManager(form.id);
    });

    // Configurações específicas da página
    const pageScript = document.querySelector('script[data-page]');
    if (pageScript) {
        const pageName = pageScript.dataset.page;
        loadPageScript(pageName);
    }

    console.log('✅ COISABOA inicializado com sucesso!');
});

function loadPageScript(pageName) {
    // Carregar scripts específicos da página
    const scripts = {
        'dashboard': () => { /* Scripts do dashboard */ },
        'estoque': () => { /* Scripts do estoque */ },
        'compras': () => { /* Scripts de compras */ },
        'vendas': () => { /* Scripts de vendas */ }
    };

    if (scripts[pageName]) {
        scripts[pageName]();
    }
}
",

    'charts.js' => "/*!
 * COISABOA - Gráficos e Visualizações
 * Integração com Chart.js para relatórios
 */

class ChartManager {
    constructor() {
        this.charts = new Map();
        this.init();
    }

    init() {
        this.loadChartJS().then(() => {
            this.createCharts();
        });
    }

    async loadChartJS() {
        // Carregar Chart.js dinamicamente
        if (typeof Chart === 'undefined') {
            await this.loadScript('https://cdn.jsdelivr.net/npm/chart.js');
        }
    }

    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    createCharts() {
        // Gráfico de vendas mensais
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            this.createSalesChart(salesCtx);
        }

        // Gráfico de estoque
        const stockCtx = document.getElementById('stockChart');
        if (stockCtx) {
            this.createStockChart(stockCtx);
        }
    }

    createSalesChart(ctx) {
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Vendas Mensais',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        this.charts.set('sales', chart);
    }

    createStockChart(ctx) {
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Em Estoque', 'Vendido', 'Baixo Estoque'],
                datasets: [{
                    data: [300, 50, 100],
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6', 
                        '#f59e0b'
                    ]
                }]
            },
            options: {
                responsive: true,
                cutout: '70%'
            }
        });

        this.charts.set('stock', chart);
    }
}

// Inicializar gráficos quando disponível
if (document.querySelector('.chart-container')) {
    document.addEventListener('DOMContentLoaded', () => {
        new ChartManager();
    });
}
"
];

// Arquivos de imagem e ícones
$image_files = [
    'favicon.ico' => base64_decode('R0lGODlhEAAQAPIAAAAAAJmZmf///wAAAAAAAAAAACH5BAEAAAIALAAAAAAQABAAAAMjCLrc/jDKSatlQtScOOvNd4ZgGAaEoqjWBQAAOw=='), // Favicon básico
    'logo.png' => '', // Placeholder para logo
    'placeholder.jpg' => '' // Placeholder para imagens
];

// Arquivos de configuração
$config_files = [
    '.htaccess' => "# COISABOA - Configurações para Assets
# Otimização e cache de recursos estáticos

ExpiresActive On

# CSS e JS - Cache por 1 ano
<FilesMatch \"\\.(css|js)$\">
    ExpiresDefault \"access plus 1 year\"
    Header set Cache-Control \"public, immutable\"
</FilesMatch>

# Imagens - Cache por 1 mês
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|ico)$\">
    ExpiresDefault \"access plus 1 month\"
    Header set Cache-Control \"public\"
</FilesMatch>

# Fontes - Cache por 1 ano
<FilesMatch \"\\.(woff|woff2|ttf|eot)$\">
    ExpiresDefault \"access plus 1 year\"
    Header set Cache-Control \"public, immutable\"
</FilesMatch>

# Compressão GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>

# Headers de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set Referrer-Policy \"strict-origin-when-cross-origin\"

# Acesso permitido a todos os assets
Order Allow,Deny
Allow from all",

    'manifest.json' => '{
    "name": "COISABOA - Sistema Comercial",
    "short_name": "COISABOA",
    "description": "Sistema de controle de compras, vendas e estoque",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#667eea",
    "theme_color": "#764ba2",
    "orientation": "portrait-primary",
    "icons": [
        {
            "src": "icons/icon-72x72.png",
            "sizes": "72x72",
            "type": "image/png"
        },
        {
            "src": "icons/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "icons/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png"
        }
    ]
}'
];

// Criar arquivos CSS
echo "\n🎨 CRIANDO ARQUIVOS CSS...\n";
echo "=============================================\n";

foreach ($css_files as $filename => $content) {
    $filepath = $assets_dir . '/css/' . $filename;
    
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
        echo "✅ CSS criado: css/{$filename}\n";
    } else {
        echo "📁 CSS já existe: css/{$filename}\n";
    }
}

// Criar arquivos JavaScript
echo "\n📜 CRIANDO ARQUIVOS JAVASCRIPT...\n";
foreach ($js_files as $filename => $content) {
    $filepath = $assets_dir . '/js/' . $filename;
    
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
        echo "✅ JS criado: js/{$filename}\n";
    } else {
        echo "📁 JS já existe: js/{$filename}\n";
    }
}

// Criar arquivos de configuração
echo "\n⚙️  CRIANDO ARQUIVOS DE CONFIGURAÇÃO...\n";
foreach ($config_files as $filename => $content) {
    $filepath = $assets_dir . '/' . $filename;
    file_put_contents($filepath, $content);
    echo "✅ Config criada: {$filename}\n";
}

// Criar arquivos placeholder
echo "\n🖼️  CRIANDO ARQUIVOS DE IMAGEM PLACEHOLDER...\n";
foreach ($image_files as $filename => $content) {
    $filepath = $assets_dir . '/images/' . $filename;
    
    if (!file_exists($filepath)) {
        if ($filename === 'favicon.ico' && $content) {
            file_put_contents($filepath, $content);
        } else {
            file_put_contents($filepath, '// Placeholder file for ' . $filename);
        }
        echo "✅ Placeholder criado: images/{$filename}\n";
    } else {
        echo "📁 Placeholder já existe: images/{$filename}\n";
    }
}

// Criar arquivos de proteção nas subpastas
echo "\n🛡️  CRIANDO ARQUIVOS DE PROTEÇÃO...\n";
$protected_folders = ['css', 'js', 'images', 'icons', 'fonts', 'scss'];
foreach ($protected_folders as $folder) {
    $index_path = $assets_dir . '/' . $folder . '/index.html';
    $index_content = "<!DOCTYPE html><html><head><title>Acesso Negado</title></head><body><h1>🚫 Acesso Negado</h1><p>Pasta de assets protegida.</p></body></html>";
    
    file_put_contents($index_path, $index_content);
    echo "✅ Proteção em: {$folder}/index.html\n";
}

// Criar arquivo de inicialização de assets
$assets_init = "<?php
/**
 * COISABOA - Helper de Assets
 * Funções para incluir CSS e JS dinamicamente
 */

function css(\$arquivo) {
    return '/assets/css/' . \$arquivo . '?v=' . filemtime(ASSETS_PATH . '/css/' . \$arquivo);
}

function js(\$arquivo) {
    return '/assets/js/' . \$arquivo . '?v=' . filemtime(ASSETS_PATH . '/js/' . \$arquivo);
}

function image(\$arquivo) {
    return '/assets/images/' . \$arquivo;
}

function icon(\$arquivo) {
    return '/assets/icons/' . \$arquivo;
}
?>";

file_put_contents($assets_dir . '/assets_helper.php', $assets_init);
echo "✅ Helper criado: assets_helper.php\n";

// Resumo final
echo "\n🎉 SISTEMA DE ASSETS CRIADO COM SUCESSO!\n";
echo "=============================================\n";
echo "📊 RESUMO DO SISTEMA DE ASSETS:\n";
echo "• 🎨 style.css - Sistema completo de design (CSS Variables, Grid, Responsivo)\n";
echo "• 📊 dashboard.css - Componentes específicos do dashboard\n";
echo "• 📝 forms.css - Estilos avançados para formulários\n";
echo "• 📱 responsive-tables.css - Tabelas responsivas\n";
echo "• ⚡ app.js - JavaScript principal com classes utilitárias\n";
echo "• 📈 charts.js - Integração com gráficos (Chart.js)\n";
echo "• 🛡️  .htaccess - Otimização e cache de assets\n";
echo "• 📋 manifest.json - PWA ready\n\n";

echo "✅ RECURSOS INCLUÍDOS:\n";
echo "• 🎯 Design System completo com CSS Variables\n";
echo "• 📱 Layout totalmente responsivo (mobile-first)\n";
echo "• ⚡ JavaScript modular com classes ES6+\n";
echo "• ✅ Validação de formulários em tempo real\n";
echo "• 🔔 Sistema de notificações toast\n";
echo "• 📊 Gráficos integrados para relatórios\n";
echo "• 🚀 Otimização de performance (cache, compression)\n";
echo "• 🔧 Helper PHP para versionamento de assets\n\n";

echo "🚀 PARA USAR O SISTEMA:\n";
echo "1. Inclua o CSS: <link href=\"/assets/css/style.css\" rel=\"stylesheet\">\n";
echo "2. Inclua o JS: <script src=\"/assets/js/app.js\"></script>\n";
echo "3. Use as classes CSS: card, btn-primary, form-control, etc.\n";
echo "4. Use o JavaScript: new FormManager('meuForm'), Utils.showNotification()\n";

echo "\n🎨 SISTEMA DE ASSETS PRONTO PARA USO!\n";

?>