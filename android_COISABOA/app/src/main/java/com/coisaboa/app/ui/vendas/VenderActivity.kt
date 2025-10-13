package com.coisaboa.app.ui.vendas

import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.view.LayoutInflater
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import androidx.appcompat.app.AlertDialog
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
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
 * Registra vendas com seletor de produtos filtrável.
 */
class VenderActivity : AppCompatActivity() {

    // 🔹 Elementos da interface
    private lateinit var etProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorUnitario: EditText
    private lateinit var etObservacoes: EditText
    private lateinit var tvValorEstimado: TextView
    private lateinit var tvValorTotal: TextView
    private lateinit var spPagamento: Spinner
    private lateinit var btnSalvar: Button
    private lateinit var btnSelecionarProduto: Button

    // 🔹 Repositórios
    private lateinit var productRepo: ProductRepository
    private lateinit var saleRepo: SaleRepository

    // 🔹 Estado
    private var produtoSelecionado: ProductEntity? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_vender)
        supportActionBar?.title = "Nova Venda"

        val db = DatabaseProvider.get(this)
        productRepo = ProductRepository(db.productDao())
        saleRepo = SaleRepository(db.saleDao(), productRepo)

        // Vincula elementos de interface
        etProduto = findViewById(R.id.etProduto)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorUnitario = findViewById(R.id.etValorUnitario)
        etObservacoes = findViewById(R.id.etObservacoes)
        tvValorEstimado = findViewById(R.id.tvValorEstimado)
        tvValorTotal = findViewById(R.id.tvValorTotal)
        spPagamento = findViewById(R.id.spPagamento)
        btnSalvar = findViewById(R.id.btnSalvarVenda)
        btnSelecionarProduto = findViewById(R.id.btnSelecionarProduto)

        configurarSpinnerPagamento()
        configurarEventos()
    }

    /** 💳 Preenche o Spinner de formas de pagamento */
    private fun configurarSpinnerPagamento() {
        val formasPagamento = arrayOf(
            "Dinheiro", "PIX", "Cartão de Crédito", "Cartão de Débito", "Transferência", "Outro"
        )
        spPagamento.adapter = ArrayAdapter(
            this, android.R.layout.simple_spinner_dropdown_item, formasPagamento
        )
    }

    /** 🔧 Configura eventos de texto e cliques */
    private fun configurarEventos() {
        val atualizarTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            tvValorTotal.text = "R$ %.2f".format(qtd * valor)
        }

        val watcher = object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
            override fun afterTextChanged(s: Editable?) { atualizarTotal() }
        }

        etQuantidade.addTextChangedListener(watcher)
        etValorUnitario.addTextChangedListener(watcher)

        btnSelecionarProduto.setOnClickListener {
            abrirDialogoProdutosFiltravel()
        }

        btnSalvar.setOnClickListener { salvarVenda() }
    }

    /** 🔍 Novo diálogo com filtro dinâmico */
    private fun abrirDialogoProdutosFiltravel() {
        lifecycleScope.launch(Dispatchers.IO) {
            val produtos = productRepo.getAll().filter { it.quantidade > 0 }

            withContext(Dispatchers.Main) {
                if (produtos.isEmpty()) {
                    Toast.makeText(this@VenderActivity, "Nenhum produto disponível no estoque!", Toast.LENGTH_SHORT).show()
                    return@withContext
                }

                val view = LayoutInflater.from(this@VenderActivity)
                    .inflate(R.layout.dialog_selecionar_produto, null)

                val etBuscar = view.findViewById<EditText>(R.id.etBuscarProduto)
                val rvProdutos = view.findViewById<RecyclerView>(R.id.rvListaProdutos)
                val tvEmpty = view.findViewById<TextView>(R.id.tvSemResultados)

                val adapter = ProdutoDialogAdapter { produto ->
                    preencherCamposProduto(produto)
                }

                rvProdutos.layoutManager = LinearLayoutManager(this@VenderActivity)
                rvProdutos.adapter = adapter

                adapter.submitList(produtos)
                tvEmpty.visibility = if (produtos.isEmpty()) TextView.VISIBLE else TextView.GONE

                etBuscar.addTextChangedListener(object : TextWatcher {
                    override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
                    override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                        val query = s.toString().trim().lowercase()
                        val filtrados = produtos.filter { it.nome.lowercase().contains(query) }
                        adapter.submitList(filtrados)
                        tvEmpty.visibility = if (filtrados.isEmpty()) TextView.VISIBLE else TextView.GONE
                    }
                    override fun afterTextChanged(s: Editable?) {}
                })

                AlertDialog.Builder(this@VenderActivity)
                    .setTitle("Selecionar produto")
                    .setView(view)
                    .setNegativeButton("Cancelar", null)
                    .show()
            }
        }
    }

    /** 🧾 Preenche campos da tela após selecionar produto */
    private fun preencherCamposProduto(p: ProductEntity) {
        produtoSelecionado = p
        etProduto.setText(p.nome)
        tvValorEstimado.text = "Valor estimado: R$ %.2f".format(p.valorEstimado ?: 0.0)

        val preco = when {
            (p.valorRevenda ?: 0.0) > 0.0 -> p.valorRevenda!!
            (p.valorEstimado ?: 0.0) > 0.0 -> p.valorEstimado!!
            else -> 0.0
        }
        etValorUnitario.setText("%.2f".format(preco))
    }

    /** 💾 Valida e registra nova venda */
    private fun salvarVenda() {
        val produto = produtoSelecionado
        val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
        val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
        val formaPagamento = spPagamento.selectedItem?.toString() ?: "Não informado"
        val observacoes = etObservacoes.text.toString().ifBlank { null }

        when {
            produto == null -> {
                Toast.makeText(this, "Selecione um produto.", Toast.LENGTH_SHORT).show(); return
            }
            qtd <= 0 -> {
                Toast.makeText(this, "Informe uma quantidade válida.", Toast.LENGTH_SHORT).show(); return
            }
            valor <= 0.0 -> {
                Toast.makeText(this, "Informe um valor unitário válido.", Toast.LENGTH_SHORT).show(); return
            }
            qtd > produto.quantidade -> {
                Toast.makeText(this, "Estoque insuficiente: disponível ${produto.quantidade}.", Toast.LENGTH_LONG).show(); return
            }
        }

        lifecycleScope.launch(Dispatchers.IO) {
            try {
                val venda = SaleEntity(
                    produtoNome = produto.nome,
                    quantidade = qtd,
                    valorUnitario = valor,
                    valorTotal = qtd * valor,
                    formaPagamento = formaPagamento,
                    valorEstimado = produto.valorEstimado,
                    observacoes = observacoes,
                    dataVenda = Date()
                )

                saleRepo.insert(venda)
                productRepo.decreaseStock(produto.id, qtd)

                withContext(Dispatchers.Main) {
                    Toast.makeText(this@VenderActivity, "✅ Venda registrada com sucesso!", Toast.LENGTH_LONG).show()
                    finish()
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    Toast.makeText(this@VenderActivity, "Erro ao salvar venda: ${e.message}", Toast.LENGTH_LONG).show()
                }
            }
        }
    }
}
