package com.coisaboa.app.ui.vendas

import android.app.AlertDialog
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.SaleEntity
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.data.repository.SaleRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.util.*

/**
 * 🛒 VenderActivity
 * Tela responsável por registrar novas vendas no sistema COISABOA.
 * Permite selecionar produtos do estoque, escolher forma de pagamento,
 * calcular automaticamente o total e registrar a venda no banco.
 */
class VenderActivity : AppCompatActivity() {

    // 🔹 Componentes da interface
    private lateinit var etProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorUnitario: EditText
    private lateinit var tvValorTotal: TextView
    private lateinit var spPagamento: Spinner
    private lateinit var btnSalvar: Button
    private lateinit var btnSelecionarProduto: Button

    // 🔹 Repositórios
    private lateinit var productRepo: ProductRepository
    private lateinit var saleRepo: SaleRepository

    // 🔹 Produto selecionado no momento
    private var produtoSelecionado: ProductEntity? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_vender)
        supportActionBar?.title = "Nova Venda"

        // Inicializa os repositórios
        val db = DatabaseProvider.get(this)
        productRepo = ProductRepository(db.productDao())
        saleRepo = SaleRepository(db.saleDao(), productRepo)

        // Vincula elementos da interface
        etProduto = findViewById(R.id.etProduto)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorUnitario = findViewById(R.id.etValorUnitario)
        tvValorTotal = findViewById(R.id.tvValorTotal)
        spPagamento = findViewById(R.id.spPagamento)
        btnSalvar = findViewById(R.id.btnSalvarVenda)
        btnSelecionarProduto = findViewById(R.id.btnSelecionarProduto)

        configurarSpinnerPagamento()
        configurarEventos()
    }

    /**
     * 🔸 Preenche o Spinner de forma de pagamento
     */
    private fun configurarSpinnerPagamento() {
        val formasPagamento = arrayOf(
            "Dinheiro",
            "PIX",
            "Cartão de Crédito",
            "Cartão de Débito",
            "Transferência",
            "Outro"
        )

        val adapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_dropdown_item,
            formasPagamento
        )
        spPagamento.adapter = adapter
    }

    /**
     * 🔸 Configura os eventos da interface
     */
    private fun configurarEventos() {
        val atualizarTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            tvValorTotal.text = "R$ %.2f".format(qtd * valor)
        }

        // 🧮 Atualiza o total automaticamente conforme digita
        etQuantidade.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
            override fun afterTextChanged(s: Editable?) { atualizarTotal() }
        })

        etValorUnitario.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
            override fun afterTextChanged(s: Editable?) { atualizarTotal() }
        })

        // 🧾 Botões
        btnSelecionarProduto.setOnClickListener { abrirListaDeProdutos() }
        btnSalvar.setOnClickListener { salvarVenda() }
    }

    /**
     * 🔹 Exibe uma lista de produtos disponíveis no estoque.
     */
    private fun abrirListaDeProdutos() {
        lifecycleScope.launch(Dispatchers.IO) {
            val produtos = productRepo.getAll()
            withContext(Dispatchers.Main) {
                if (produtos.isEmpty()) {
                    Toast.makeText(this@VenderActivity, "Nenhum produto no estoque!", Toast.LENGTH_SHORT).show()
                    return@withContext
                }

                val nomes = produtos.map { "${it.nome} (Qtd: ${it.quantidade})" }.toTypedArray()
                AlertDialog.Builder(this@VenderActivity)
                    .setTitle("Selecionar produto")
                    .setItems(nomes) { _, which ->
                        produtoSelecionado = produtos[which]
                        etProduto.setText(produtoSelecionado!!.nome)
                        etValorUnitario.setText(produtoSelecionado!!.valorRevenda?.toString() ?: "")
                        Toast.makeText(this@VenderActivity, "Produto selecionado: ${produtos[which].nome}", Toast.LENGTH_SHORT).show()
                    }
                    .setNegativeButton("Cancelar", null)
                    .show()
            }
        }
    }

    /**
     * 🔹 Valida os dados e registra a venda no banco.
     * Após salvar, reduz o estoque do produto automaticamente.
     */
    private fun salvarVenda() {
        val produto = produtoSelecionado
        val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
        val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
        val formaPagamento = spPagamento.selectedItem?.toString() ?: "Não informado"

        if (produto == null || qtd <= 0 || valor <= 0.0) {
            Toast.makeText(this, "Preencha todos os campos corretamente!", Toast.LENGTH_SHORT).show()
            return
        }

        lifecycleScope.launch(Dispatchers.IO) {
            try {
                val venda = SaleEntity(
                    produtoNome = produto.nome,
                    quantidade = qtd,
                    valorUnitario = valor,
                    valorTotal = qtd * valor,
                    formaPagamento = formaPagamento,
                    dataVenda = Date()
                )

                saleRepo.insert(venda)

                // 🔻 Diminui a quantidade do produto vendido
                productRepo.decreaseStock(produto.id, qtd)

                withContext(Dispatchers.Main) {
                    Toast.makeText(this@VenderActivity, "✅ Venda registrada com sucesso!", Toast.LENGTH_LONG).show()
                    finish()
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    Toast.makeText(this@VenderActivity, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
                }
            }
        }
    }
}
