package com.coisaboa.app.ui.relatorios

import android.app.DatePickerDialog
import android.os.Bundle
import android.view.View
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
 * 💰 FinanceiroActivity
 * Exibe o relatório financeiro com filtros por período e indicadores:
 * Total de Vendas, Custo, Lucro Bruto e Ticket Médio.
 */
class FinanceiroActivity : AppCompatActivity() {

    // 🧱 Componentes da interface
    private lateinit var tvTotalVendas: TextView
    private lateinit var tvCustoTotal: TextView
    private lateinit var tvLucroBruto: TextView
    private lateinit var tvTicketMedio: TextView
    private lateinit var btnDataInicio: Button
    private lateinit var btnDataFim: Button
    private lateinit var btnGerarRelatorio: Button
    private lateinit var btnVoltar: Button
    private lateinit var progressBar: ProgressBar

    // 📅 Controle de datas
    private val dateFormat = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
    private var dataInicio: Date? = null
    private var dataFim: Date? = null

    // 🗃️ Repositórios
    private val db by lazy { DatabaseProvider.get(this) }
    private val productRepo by lazy { ProductRepository(db.productDao()) }
    private val saleRepo by lazy { SaleRepository(db.saleDao(), productRepo) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_financeiro)
        supportActionBar?.title = "Relatório Financeiro"

        inicializarComponentes()
        configurarEventos()

        definirPeriodoPadrao()
        atualizarRelatorio()
    }

    // ============================================================
    // 🔹 Inicialização e configuração de eventos
    // ============================================================

    private fun inicializarComponentes() {
        tvTotalVendas = findViewById(R.id.tvTotalVendas)
        tvCustoTotal = findViewById(R.id.tvCustoTotal)
        tvLucroBruto = findViewById(R.id.tvLucroBruto)
        tvTicketMedio = findViewById(R.id.tvTicketMedio)
        btnDataInicio = findViewById(R.id.btnDataInicio)
        btnDataFim = findViewById(R.id.btnDataFim)
        btnGerarRelatorio = findViewById(R.id.btnGerarRelatorio)
        btnVoltar = findViewById(R.id.btnVoltar)
        progressBar = findViewById(R.id.progressBarFinanceiro)
    }

    private fun configurarEventos() {
        btnGerarRelatorio.setOnClickListener {
            Toast.makeText(this, "📤 Exportação em PDF em desenvolvimento.", Toast.LENGTH_SHORT).show()
        }

        btnVoltar.setOnClickListener { finish() }

        btnDataInicio.setOnClickListener { selecionarData(true) }
        btnDataFim.setOnClickListener { selecionarData(false) }
    }

    // ============================================================
    // 📆 Controle de período
    // ============================================================

    /** Define o período padrão como o mês atual. */
    private fun definirPeriodoPadrao() {
        val calendar = Calendar.getInstance()
        calendar.set(Calendar.DAY_OF_MONTH, 1)
        dataInicio = calendar.time

        calendar.set(Calendar.DAY_OF_MONTH, calendar.getActualMaximum(Calendar.DAY_OF_MONTH))
        dataFim = calendar.time

        atualizarRotuloDatas()
    }

    /** Mostra o seletor de data. */
    private fun selecionarData(isInicio: Boolean) {
        val calendar = Calendar.getInstance()
        val listener = DatePickerDialog.OnDateSetListener { _, year, month, day ->
            calendar.set(year, month, day)
            if (isInicio) dataInicio = calendar.time else dataFim = calendar.time
            atualizarRotuloDatas()
            atualizarRelatorio()
        }

        DatePickerDialog(
            this, listener,
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH)
        ).show()
    }

    /** Atualiza os textos dos botões com as datas selecionadas. */
    private fun atualizarRotuloDatas() {
        btnDataInicio.text = "📅 Início: ${dataInicio?.let { dateFormat.format(it) } ?: "--/--/----"}"
        btnDataFim.text = "📅 Fim: ${dataFim?.let { dateFormat.format(it) } ?: "--/--/----"}"
    }

    // ============================================================
    // 📊 Processamento dos dados e atualização da interface
    // ============================================================

    private fun atualizarRelatorio() {
        val inicio = dataInicio ?: return
        val fim = dataFim ?: return

        progressBar.visibility = View.VISIBLE

        lifecycleScope.launch(Dispatchers.IO) {
            try {
                val vendas = saleRepo.getSalesBetween(inicio, fim)

                if (vendas.isEmpty()) {
                    withContext(Dispatchers.Main) {
                        progressBar.visibility = View.GONE
                        atualizarUI(0.0, 0.0, 0.0, 0.0)
                        Toast.makeText(
                            this@FinanceiroActivity,
                            "Nenhuma venda encontrada no período selecionado.",
                            Toast.LENGTH_SHORT
                        ).show()
                    }
                    return@launch
                }

                val totalVendas = vendas.sumOf { it.valorTotal ?: 0.0 }

                val custoTotal = vendas.sumOf { venda ->
                    val produto = productRepo.getByName(venda.produtoNome)
                    ((produto?.valorEstimado ?: 0.0) * (venda.quantidade ?: 0))
                }

                val lucroBruto = totalVendas - custoTotal
                val ticketMedio = if (vendas.isNotEmpty()) totalVendas / vendas.size else 0.0

                withContext(Dispatchers.Main) {
                    progressBar.visibility = View.GONE
                    atualizarUI(totalVendas, custoTotal, lucroBruto, ticketMedio)
                }

            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    progressBar.visibility = View.GONE
                    Toast.makeText(
                        this@FinanceiroActivity,
                        "Erro ao gerar relatório: ${e.message}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }
    }

    /** Atualiza os textos dos indicadores financeiros. */
    private fun atualizarUI(
        totalVendas: Double,
        custoTotal: Double,
        lucroBruto: Double,
        ticketMedio: Double
    ) {
        val formato = NumberFormat.getCurrencyInstance(Locale("pt", "BR"))
        tvTotalVendas.text = formato.format(totalVendas)
        tvCustoTotal.text = formato.format(custoTotal)
        tvLucroBruto.text = formato.format(lucroBruto)
        tvTicketMedio.text = formato.format(ticketMedio)
    }
}
