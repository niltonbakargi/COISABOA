package com.coisaboa.app.ui.gerenciar

import android.app.Activity
import android.content.Intent
import android.graphics.BitmapFactory
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.provider.MediaStore
import android.text.Editable
import android.text.TextWatcher
import android.view.LayoutInflater
import android.view.View
import android.widget.*
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.ui.vendas.ProdutoDialogAdapter
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream

class GerenciarActivity : AppCompatActivity() {

    // 🧱 Componentes de interface
    private lateinit var btnModoIncluir: Button
    private lateinit var btnModoExcluir: Button
    private lateinit var boxIncluir: LinearLayout
    private lateinit var boxExcluir: LinearLayout
    private lateinit var btnAcaoPrincipal: Button

    // Campos modo Incluir
    private lateinit var imgProduto: ImageView
    private lateinit var btnSelecionarImagemProduto: Button
    private lateinit var etNomeProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorCusto: EditText
    private lateinit var etValorRevenda: EditText
    private lateinit var etObsIncluir: EditText

    // Campos modo Excluir
    private lateinit var btnSelecionarProduto: Button
    private lateinit var imgProdutoExcluir: ImageView
    private lateinit var tvEstoqueAtual: TextView
    private lateinit var etQtdExcluir: EditText
    private lateinit var etObsExcluir: EditText

    // Estado
    private var caminhoImagemProduto: String? = null
    private var produtoSelecionado: ProductEntity? = null
    private var modoAtual = "incluir"

    // ViewModel
    private val viewModel: GerenciarViewModel by viewModels {
        val dao = DatabaseProvider.get(this).productDao()
        GerenciarViewModelFactory(ProductRepository(dao))
    }

    // 📸 Seleção de imagem
    private val selecionarImagemLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode == Activity.RESULT_OK && result.data?.data != null) {
                val uri = result.data!!.data!!
                val caminho = salvarImagemLocal(uri)
                caminhoImagemProduto = caminho
                imgProduto.setImageBitmap(BitmapFactory.decodeFile(caminho))
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_gerenciar)
        supportActionBar?.title = "Gerenciar Estoque"

        inicializarComponentes()
        configurarEventos()
        observarProdutos()
    }

    // 🔹 Inicialização dos componentes
    private fun inicializarComponentes() {
        btnModoIncluir = findViewById(R.id.btnModoIncluir)
        btnModoExcluir = findViewById(R.id.btnModoExcluir)
        boxIncluir = findViewById(R.id.boxIncluir)
        boxExcluir = findViewById(R.id.boxExcluir)
        btnAcaoPrincipal = findViewById(R.id.btnAcaoPrincipal)

        // Incluir
        imgProduto = findViewById(R.id.imgProduto)
        btnSelecionarImagemProduto = findViewById(R.id.btnSelecionarImagemProduto)
        etNomeProduto = findViewById(R.id.etNomeProduto)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorCusto = findViewById(R.id.etValorCusto)
        etValorRevenda = findViewById(R.id.etValorRevenda)
        etObsIncluir = findViewById(R.id.etObsIncluir)

        // Excluir
        btnSelecionarProduto = findViewById(R.id.btnSelecionarProduto)
        imgProdutoExcluir = findViewById(R.id.imgProdutoExcluir)
        tvEstoqueAtual = findViewById(R.id.tvEstoqueAtual)
        etQtdExcluir = findViewById(R.id.etQtdExcluir)
        etObsExcluir = findViewById(R.id.etObsExcluir)
    }

    // 🔹 Configuração de eventos
    private fun configurarEventos() {
        btnModoIncluir.setOnClickListener { ativarModo("incluir") }
        btnModoExcluir.setOnClickListener { ativarModo("excluir") }

        btnSelecionarImagemProduto.setOnClickListener {
            val intent = Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI)
            selecionarImagemLauncher.launch(intent)
        }

        btnAcaoPrincipal.setOnClickListener {
            if (modoAtual == "incluir") adicionarProduto() else removerProduto()
        }

        btnSelecionarProduto.setOnClickListener { abrirDialogoProdutosFiltravel() }
    }

    // 🔍 Diálogo filtrável de produtos
    private fun abrirDialogoProdutosFiltravel() {
        lifecycleScope.launch(Dispatchers.IO) {
            val dao = DatabaseProvider.get(this@GerenciarActivity).productDao()
            val repo = ProductRepository(dao)
            val produtos = repo.getAll().filter { it.quantidade > 0 }

            withContext(Dispatchers.Main) {
                if (produtos.isEmpty()) {
                    Toast.makeText(
                        this@GerenciarActivity,
                        "Nenhum produto disponível no estoque!",
                        Toast.LENGTH_SHORT
                    ).show()
                    return@withContext
                }

                val view = LayoutInflater.from(this@GerenciarActivity)
                    .inflate(R.layout.dialog_selecionar_produto, null)
                val etBuscar = view.findViewById<EditText>(R.id.etBuscarProduto)
                val rvProdutos = view.findViewById<RecyclerView>(R.id.rvListaProdutos)
                val tvEmpty = view.findViewById<TextView>(R.id.tvSemResultados)

                val dialog = AlertDialog.Builder(this@GerenciarActivity)
                    .setTitle("Selecionar produto para exclusão")
                    .setView(view)
                    .setNegativeButton("Cancelar", null)
                    .create()

                val adapter = ProdutoDialogAdapter { produto ->
                    preencherProdutoSelecionado(produto)
                    dialog.dismiss()
                }

                rvProdutos.layoutManager = LinearLayoutManager(this@GerenciarActivity)
                rvProdutos.adapter = adapter
                adapter.submitList(produtos)
                tvEmpty.visibility = if (produtos.isEmpty()) View.VISIBLE else View.GONE

                etBuscar.addTextChangedListener(object : TextWatcher {
                    override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
                    override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                        val query = s.toString().trim().lowercase()
                        val filtrados = produtos.filter { it.nome.lowercase().contains(query) }
                        adapter.submitList(filtrados)
                        tvEmpty.visibility = if (filtrados.isEmpty()) View.VISIBLE else View.GONE
                    }
                    override fun afterTextChanged(s: Editable?) {}
                })

                dialog.show()
            }
        }
    }

    // 🔄 Observa mudanças no banco (futuro: atualização em tempo real)
    private fun observarProdutos() {
        lifecycleScope.launch {
            viewModel.produtos.collectLatest { }
        }
    }

    // 🧭 Alternar entre modos
    private fun ativarModo(modo: String) {
        modoAtual = modo
        if (modo == "incluir") {
            boxIncluir.visibility = View.VISIBLE
            boxExcluir.visibility = View.GONE
            btnAcaoPrincipal.text = "💾 Adicionar ao Estoque"
            btnAcaoPrincipal.setBackgroundColor(Color.parseColor("#16A34A"))
        } else {
            boxIncluir.visibility = View.GONE
            boxExcluir.visibility = View.VISIBLE
            btnAcaoPrincipal.text = "🗑️ Remover do Estoque"
            btnAcaoPrincipal.setBackgroundColor(Color.parseColor("#DC2626"))
        }
    }

    // ➕ Adicionar produto
    private fun adicionarProduto() {
        val nome = etNomeProduto.text.toString().trim()
        val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
        val custoUnitario = etValorCusto.text.toString().toDoubleOrNull() ?: 0.0
        val revenda = etValorRevenda.text.toString().toDoubleOrNull() ?: 0.0
        val obs = etObsIncluir.text.toString().trim()

        if (nome.isEmpty() || qtd <= 0) {
            Toast.makeText(this, "Informe nome e quantidade válidos!", Toast.LENGTH_SHORT).show()
            return
        }

        val produto = ProductEntity(
            nome = nome,
            quantidade = qtd,
            valorEstimado = custoUnitario,
            valorRevenda = revenda,
            observacoes = obs,
            caminhoImagem = caminhoImagemProduto
        )

        viewModel.salvar(
            produto,
            onOk = {
                runOnUiThread {
                    Toast.makeText(this, "✅ Produto adicionado com sucesso!", Toast.LENGTH_SHORT).show()
                    limparCampos()
                }
            },
            onErro = {
                runOnUiThread {
                    Toast.makeText(this, "Erro ao salvar: ${it.message}", Toast.LENGTH_LONG).show()
                }
            }
        )
    }

    // ➖ Remover produto
    private fun removerProduto() {
        val produto = produtoSelecionado
        val qtdRemover = etQtdExcluir.text.toString().toIntOrNull() ?: 0

        if (produto == null) {
            Toast.makeText(this, "Nenhum produto selecionado.", Toast.LENGTH_SHORT).show()
            return
        }
        if (qtdRemover <= 0) {
            Toast.makeText(this, "Informe uma quantidade válida.", Toast.LENGTH_SHORT).show()
            return
        }

        lifecycleScope.launch {
            try {
                viewModel.ajustarEstoque(produto.id, -qtdRemover)
                runOnUiThread {
                    Toast.makeText(this@GerenciarActivity, "Produto removido do estoque.", Toast.LENGTH_SHORT).show()
                    limparCampos()
                }
            } catch (e: Exception) {
                runOnUiThread {
                    Toast.makeText(this@GerenciarActivity, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
                }
            }
        }
    }

    // 🧹 Limpar campos
    private fun limparCampos() {
        etNomeProduto.text.clear()
        etQuantidade.text.clear()
        etValorCusto.text.clear()
        etValorRevenda.text.clear()
        etObsIncluir.text.clear()
        etQtdExcluir.text.clear()
        etObsExcluir.text.clear()
        tvEstoqueAtual.text = "Estoque atual: —"
        caminhoImagemProduto = null
        produtoSelecionado = null
        imgProduto.setImageResource(R.drawable.ic_placeholder)
        imgProdutoExcluir.setImageResource(R.drawable.ic_placeholder)
    }

    // 💾 Salvar imagem localmente
    private fun salvarImagemLocal(uri: Uri): String? {
        return try {
            val inputStream = contentResolver.openInputStream(uri)
            val file = File(filesDir, "produto_${System.currentTimeMillis()}.jpg")
            val outputStream = FileOutputStream(file)
            inputStream?.copyTo(outputStream)
            inputStream?.close()
            outputStream.close()
            file.absolutePath
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }

    // 🔹 Preencher produto selecionado
    private fun preencherProdutoSelecionado(produto: ProductEntity) {
        produtoSelecionado = produto
        if (!produto.caminhoImagem.isNullOrBlank()) {
            val file = File(produto.caminhoImagem!!)
            if (file.exists()) imgProdutoExcluir.setImageBitmap(BitmapFactory.decodeFile(file.absolutePath))
        }
        tvEstoqueAtual.text = "Estoque atual: ${produto.quantidade} unidade${if (produto.quantidade > 1) "s" else ""}"
        etQtdExcluir.setText("")
        Toast.makeText(this, "Selecionado: ${produto.nome}", Toast.LENGTH_SHORT).show()
    }
}
