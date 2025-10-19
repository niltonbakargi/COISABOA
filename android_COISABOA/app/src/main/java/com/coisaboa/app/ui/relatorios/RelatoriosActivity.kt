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
 * 📊 RelatoriosActivity
 * Tela hub dos relatórios do sistema:
 * - Exibe resumo geral de produtos, estoque e lucro potencial
 * - Abre Relatório Financeiro
 * - Abre Relatório de Vendas
 * - Abre Relatório de Estoque
 */
class RelatoriosActivity : AppCompatActivity() {

    // 🧱 Componentes da interface
    private lateinit var tvResumo: TextView
    private lateinit var btnVoltar: Button
    private lateinit var btnRelatorioFinanceiro: Button
    private lateinit var btnRelatorioVendas: Button
    private lateinit var btnRelatorioEstoque: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_relatorios)
        supportActionBar?.title = "Relatórios do Sistema"

        inicializarComponentes()
        configurarEventos()
        carregarResumo()
    }

    /** 🔧 Vincula os elementos da interface */
    private fun inicializarComponentes() {
        tvResumo = findViewById(R.id.tvResumo)
        btnVoltar = findViewById(R.id.btnVoltar)
        btnRelatorioFinanceiro = findViewById(R.id.btnGerarRelatorio)  // já existente no layout
        btnRelatorioVendas = findViewById(R.id.btnRelatorioVendas)
        btnRelatorioEstoque = findViewById(R.id.btnRelatorioEstoque)
    }

    /** ⚙️ Configura os botões e suas ações */
    private fun configurarEventos() {

        // 🔙 Voltar para o dashboard
        btnVoltar.setOnClickListener { finish() }

        // 💰 Abre o Relatório Financeiro
        btnRelatorioFinanceiro.setOnClickListener {
            startActivity(Intent(this, FinanceiroActivity::class.java))
        }

        // 📈 Abre o Relatório de Vendas
        btnRelatorioVendas.setOnClickListener {
            startActivity(Intent(this, VendasActivity::class.java))
        }

        // 📦 Abre o Relatório de Estoque (AGORA FUNCIONAL)
        btnRelatorioEstoque.setOnClickListener {
            val intent = Intent(this, EstoqueListaActivity::class.java)
            startActivity(intent)
        }
    }

    /** 🧮 Calcula e exibe o resumo geral do sistema */
    private fun carregarResumo() {
        lifecycleScope.launch(Dispatchers.IO) {
            try {
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

            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    tvResumo.text = "⚠️ Erro ao carregar resumo: ${e.message}"
                }
            }
        }
    }
}