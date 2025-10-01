/*!
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
