package com.coisaboa.app.ui.relatorios

import android.app.DatePickerDialog
import android.os.Bundle
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.data.repository.SaleRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.*

/**
 * 📈 VendasActivity
 * Exibe o relatório de vendas com filtro por período:
 * - Total de vendas
 * - Quantidade total de itens vendidos
 * - Valor total vendido
 * - Ticket médio
 */
class VendasActivity : AppCompatActivity() {

    // 🧱 Componentes da interface
    private lateinit var tvTotalVendas: TextView
    private lateinit var tvItensVendidos: TextView
    private lateinit var tvValorTotal: TextView
    private lateinit var tvTicketMedio: TextView
    private lateinit var btnDataInicio: Button
    private lateinit var btnDataFim: Button
    private lateinit var btnGerarRelatorio: Button
    private lateinit var btnVoltar: Button
    private lateinit var progressBar: ProgressBar

    // 📅 Datas
    private val dateFormat = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
    private var dataInicio: Date? = null
    private var dataFim: Date? = null

    // 🗃️ Banco e repositórios
    private lateinit var productRepo: ProductRepository
    private lateinit var saleRepo: SaleRepository

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_vendas)
        supportActionBar?.title = "Relatório de Vendas"

        // ✅ Inicialização correta dos repositórios
        val db = DatabaseProvider.get(this)
        productRepo = ProductRepository(db.productDao())
        saleRepo = SaleRepository(db.saleDao(), productRepo)

        inicializarComponentes()
        configurarEventos()
        definirPeriodoPadrao()
        carregarRelatorio()
    }

    /** 🔧 Inicializa os componentes da interface */
    private fun inicializarComponentes() {
        tvTotalVendas = findViewById(R.id.tvTotalVendas)
        tvItensVendidos = findViewById(R.id.tvItensVendidos)
        tvValorTotal = findViewById(R.id.tvValorTotal)
        tvTicketMedio = findViewById(R.id.tvTicketMedio)
        btnDataInicio = findViewById(R.id.btnDataInicio)
        btnDataFim = findViewById(R.id.btnDataFim)
        btnGerarRelatorio = findViewById(R.id.btnGerarRelatorio)
        btnVoltar = findViewById(R.id.btnVoltar)
        progressBar = findViewById(R.id.progressBarVendas)
    }

    /** ⚙️ Configura os eventos dos botões */
    private fun configurarEventos() {
        btnDataInicio.setOnClickListener { selecionarData(true) }
        btnDataFim.setOnClickListener { selecionarData(false) }
        btnVoltar.setOnClickListener { finish() }

        btnGerarRelatorio.setOnClickListener {
            carregarRelatorio()
        }
    }

    /** 📆 Define o período padrão como o mês atual */
    private fun definirPeriodoPadrao() {
        val calendar = Calendar.getInstance()
        calendar.set(Calendar.DAY_OF_MONTH, 1)
        dataInicio = calendar.time

        calendar.set(Calendar.DAY_OF_MONTH, calendar.getActualMaximum(Calendar.DAY_OF_MONTH))
        dataFim = calendar.time

        atualizarRotuloDatas()
    }

    /** 🗓️ Mostra o seletor de data */
    private fun selecionarData(isInicio: Boolean) {
        val calendar = Calendar.getInstance()
        val listener = DatePickerDialog.OnDateSetListener { _, year, month, day ->
            calendar.set(year, month, day)
            if (isInicio) dataInicio = calendar.time else dataFim = calendar.time
            atualizarRotuloDatas()
        }

        DatePickerDialog(
            this, listener,
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH)
        ).show()
    }

    /** 🔁 Atualiza o texto dos botões de data */
    private fun atualizarRotuloDatas() {
        btnDataInicio.text = "📅 Início: ${dataInicio?.let { dateFormat.format(it) } ?: "--/--/----"}"
        btnDataFim.text = "📅 Fim: ${dataFim?.let { dateFormat.format(it) } ?: "--/--/----"}"
    }

    /** 📊 Carrega o relatório de vendas filtrado */
    private fun carregarRelatorio() {
        val inicio = dataInicio ?: return
        val fim = dataFim ?: return

        progressBar.visibility = ProgressBar.VISIBLE

        lifecycleScope.launch(Dispatchers.IO) {
            try {
                val vendas = saleRepo.getSalesBetween(inicio, fim)

                if (vendas.isEmpty()) {
                    withContext(Dispatchers.Main) {
                        atualizarUI(0, 0.0, 0.0, 0.0)
                        Toast.makeText(
                            this@VendasActivity,
                            "Nenhuma venda encontrada no período selecionado.",
                            Toast.LENGTH_SHORT
                        ).show()
                        progressBar.visibility = ProgressBar.GONE
                    }
                    return@launch
                }

                val totalVendas = vendas.size
                val totalItens = vendas.sumOf { it.quantidade }
                val valorTotal = vendas.sumOf { it.valorTotal }
                val ticketMedio = if (totalVendas > 0) valorTotal / totalVendas else 0.0

                withContext(Dispatchers.Main) {
                    atualizarUI(totalItens, valorTotal, ticketMedio, totalVendas.toDouble())
                    progressBar.visibility = ProgressBar.GONE
                }

            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    progressBar.visibility = ProgressBar.GONE
                    Toast.makeText(
                        this@VendasActivity,
                        "Erro ao carregar relatório: ${e.message}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }
    }

    /** 💰 Atualiza os valores exibidos na tela */
    private fun atualizarUI(
        totalItens: Int,
        valorTotal: Double,
        ticketMedio: Double,
        totalVendas: Double
    ) {
        val formato = NumberFormat.getCurrencyInstance(Locale("pt", "BR"))
        tvTotalVendas.text = totalVendas.toInt().toString()
        tvItensVendidos.text = totalItens.toString()
        tvValorTotal.text = formato.format(valorTotal)
        tvTicketMedio.text = formato.format(ticketMedio)
    }
}
