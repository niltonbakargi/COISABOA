package com.coisaboa.app.ui.relatorios

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
 * Exibe resumo geral do sistema com base nos dados locais (Room Database):
 * - Total de produtos em estoque
 * - Quantidade total de itens
 * - Valor total estimado e lucro potencial
 */
class RelatoriosActivity : AppCompatActivity() {

    private lateinit var tvResumo: TextView
    private lateinit var btnVoltar: Button
    private lateinit var btnRelatorioEstoque: Button
    private lateinit var btnRelatorioVendas: Button
    private lateinit var btnGerarRelatorio: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_relatorios)
        supportActionBar?.title = "Relatórios do Sistema"

        // Vincular elementos da interface
        tvResumo = findViewById(R.id.tvResumo)
        btnVoltar = findViewById(R.id.btnVoltar)
        btnRelatorioEstoque = findViewById(R.id.btnRelatorioEstoque)
        btnRelatorioVendas = findViewById(R.id.btnRelatorioVendas)
        btnGerarRelatorio = findViewById(R.id.btnGerarRelatorio)

        // Carregar resumo assim que abrir
        carregarResumo()

        // Botão voltar
        btnVoltar.setOnClickListener { finish() }

        // Botões futuros (placeholder)
        btnRelatorioEstoque.setOnClickListener {
            tvResumo.text = "📦 Em breve: relatório detalhado de estoque."
        }
        btnRelatorioVendas.setOnClickListener {
            tvResumo.text = "💰 Em breve: relatório detalhado de vendas."
        }
        btnGerarRelatorio.setOnClickListener {
            carregarResumo()
        }
    }

    /**
     * 🧮 Consulta o banco e calcula o resumo geral.
     */
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
                    • Produtos cadastrados: $totalProdutos
                    • Itens em estoque: $totalItens
                    • Valor total em estoque: R$ %.2f
                    • Lucro potencial: R$ %.2f
                """.trimIndent().format(valorTotal, lucroPotencial)
            }
        }
    }
}
