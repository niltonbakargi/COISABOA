package com.coisaboa.app.ui.relatorios

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.repository.ProductRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

/**
 * 📊 RelatóriosActivity
 * Exibe o resumo geral do sistema e permite acessar relatórios específicos:
 * - Total de produtos cadastrados
 * - Quantidade total de itens
 * - Valor total estimado e lucro potencial
 * - Acesso ao Relatório Financeiro detalhado
 */
class RelatoriosActivity : AppCompatActivity() {

    private lateinit var tvResumo: TextView
    private lateinit var btnVoltar: Button
    private lateinit var btnRelatorioEstoque: Button
    private lateinit var btnRelatorioVendas: Button
    private lateinit var btnRelatorioFinanceiro: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_relatorios)
        supportActionBar?.title = "Relatórios do Sistema"

        // 🔗 Vincula elementos da interface
        tvResumo = findViewById(R.id.tvResumo)
        btnVoltar = findViewById(R.id.btnVoltar)
        btnRelatorioEstoque = findViewById(R.id.btnRelatorioEstoque)
        btnRelatorioVendas = findViewById(R.id.btnRelatorioVendas)
        btnRelatorioFinanceiro = findViewById(R.id.btnGerarRelatorio)

        // 📈 Carrega o resumo geral ao abrir
        carregarResumo()

        // ⚙️ Eventos dos botões
        configurarEventos()
    }

    /** 🔧 Configura todos os botões da tela */
    private fun configurarEventos() {
        // ↩️ Voltar ao painel
        btnVoltar.setOnClickListener { finish() }

        // 💰 Abre o Relatório Financeiro detalhado
        btnRelatorioFinanceiro.setOnClickListener {
            val intent = Intent(this, FinanceiroActivity::class.java)
            startActivity(intent)
        }

        // 📦 Em breve: Relatório de Estoque detalhado
        btnRelatorioEstoque.setOnClickListener {
            tvResumo.text = "📦 Em breve: relatório detalhado de estoque."
        }

        // 🧾 Em breve: Relatório de Vendas detalhado
        btnRelatorioVendas.setOnClickListener {
            tvResumo.text = "🧾 Em breve: relatório detalhado de vendas."
        }
    }

    /** 🧮 Consulta o banco e calcula o resumo geral */
    private fun carregarResumo() {
        lifecycleScope.launch(Dispatchers.IO) {
            val db = DatabaseProvider.get(this@RelatoriosActivity)
            val repo = ProductRepository(db.productDao())
            val produtos = repo.getAll()

            val totalProdutos = produtos.size
            val totalItens = produtos.sumOf { it.quantidade }
            val valorTotal = produtos.sumOf { (it.valorEstimado ?: 0.0) * it.quantidade }
            val lucroPotencial = produtos.sumOf {
                val venda = it.valorRevenda ?: 0.0
                val custo = it.valorEstimado ?: 0.0
                (venda - custo) * it.quantidade
            }

            withContext(Dispatchers.Main) {
                tvResumo.text = """
                    📊 Resumo Geral do Sistema
                    
                    • Produtos cadastrados: $totalProdutos
                    • Itens em estoque: $totalItens
                    • Valor total em estoque: R$ %.2f
                    • Lucro potencial: R$ %.2f
                """.trimIndent().format(valorTotal, lucroPotencial)
            }
        }
    }
}
