/*!
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
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
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
            <div id="" class="flash-message flash-">
                <span class="flash-text"></span>
                <button class="flash-close" onclick="Utils.closeFlash('')">×</button>
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
                errorMessage = `Mínimo  caracteres`;
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
        const submitBtn = this.form.querySelector('button[type="submit"]');
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
                `Estoque baixo:  ( unidades)`,
                'warning'
            );
            this.markAsAlerted(product);
        }
    }

    alreadyAlerted(product) {
        return sessionStorage.getItem(`alerted-`) === 'true';
    }

    markAsAlerted(product) {
        sessionStorage.setItem(`alerted-`, 'true');
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
