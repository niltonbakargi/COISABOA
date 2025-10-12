package com.coisaboa.app.ui.gerenciar

import android.app.Activity
import android.content.Intent
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Bundle
import android.provider.MediaStore
import android.widget.*
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.repository.ProductRepository
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import java.io.File
import java.io.FileOutputStream

/**
 * ⚙️ GerenciarActivity
 * Tela de administração do estoque local.
 * Permite visualizar, editar, excluir e cadastrar novos produtos,
 * armazenando dados no banco Room.
 */
class GerenciarActivity : AppCompatActivity() {

    // Componentes da interface
    private lateinit var rvProdutos: RecyclerView
    private lateinit var imgProduto: ImageView
    private lateinit var etNome: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorEstimado: EditText
    private lateinit var etValorRevenda: EditText
    private lateinit var etObservacoes: EditText
    private lateinit var btnSalvar: Button
    private lateinit var btnExcluir: Button
    private lateinit var btnLimpar: Button
    private lateinit var btnSelecionarImagem: Button

    // Variáveis auxiliares
    private var caminhoImagemSelecionada: String? = null
    private var produtoSelecionado: ProductEntity? = null

    // ✅ ViewModel com Factory injetando o repositório
    private val viewModel: GerenciarViewModel by viewModels {
        val dao = DatabaseProvider.get(this).productDao()
        GerenciarViewModelFactory(ProductRepository(dao))
    }

    // Adaptador da lista
    private val adapter = GerenciarAdapter { produto ->
        preencherCampos(produto)
    }

    // 📸 Registro para escolher imagem da galeria
    private val selecionarImagemLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode == Activity.RESULT_OK && result.data?.data != null) {
                val uri = result.data!!.data!!
                val caminhoSalvo = salvarImagemLocal(uri)
                caminhoImagemSelecionada = caminhoSalvo
                imgProduto.setImageBitmap(BitmapFactory.decodeFile(caminhoSalvo))
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_gerenciar)
        supportActionBar?.title = "Gerenciar Estoque"

        inicializarComponentes()
        configurarRecycler()
        observarProdutos()
        configurarEventos()
        viewModel.carregar()
    }

    // 🔹 Liga todos os elementos da tela
    private fun inicializarComponentes() {
        rvProdutos = findViewById(R.id.rvProdutos)
        imgProduto = findViewById(R.id.imgProduto)
        etNome = findViewById(R.id.etNome)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorEstimado = findViewById(R.id.etValorEstimado)
        etValorRevenda = findViewById(R.id.etValorRevenda)
        etObservacoes = findViewById(R.id.etObservacoes)
        btnSalvar = findViewById(R.id.btnSalvar)
        btnExcluir = findViewById(R.id.btnExcluir)
        btnLimpar = findViewById(R.id.btnLimpar)
        btnSelecionarImagem = findViewById(R.id.btnSelecionarImagem)
    }

    // 🔹 Configura a RecyclerView
    private fun configurarRecycler() {
        rvProdutos.layoutManager = LinearLayoutManager(this)
        rvProdutos.adapter = adapter
    }

    // 🔹 Observa alterações na lista de produtos (Flow)
    private fun observarProdutos() {
        lifecycleScope.launch {
            viewModel.produtos.collectLatest { lista ->
                adapter.submitList(lista)
            }
        }
    }

    // 🔹 Define eventos de clique e ações da tela
    private fun configurarEventos() {
        btnSelecionarImagem.setOnClickListener {
            val intent = Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI)
            selecionarImagemLauncher.launch(intent)
        }

        // 💾 Salvar produto
        btnSalvar.setOnClickListener { salvarProduto() }

        // 🗑️ Excluir produto
        btnExcluir.setOnClickListener { excluirProduto() }

        // 🧽 Limpar campos
        btnLimpar.setOnClickListener { limparCampos() }
    }

    // 🔹 Lógica de salvar / atualizar produto
    private fun salvarProduto() {
        val nome = etNome.text.toString().trim()
        val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
        val custo = etValorEstimado.text.toString().toDoubleOrNull() ?: 0.0
        val revenda = etValorRevenda.text.toString().toDoubleOrNull() ?: 0.0
        val obs = etObservacoes.text.toString().trim()

        if (nome.isEmpty()) {
            Toast.makeText(this, "Informe o nome do produto!", Toast.LENGTH_SHORT).show()
            return
        }

        val produto = produtoSelecionado?.copy(
            nome = nome,
            quantidade = qtd,
            valorEstimado = custo,
            valorRevenda = revenda,
            observacoes = obs,
            caminhoImagem = caminhoImagemSelecionada
        ) ?: ProductEntity(
            nome = nome,
            quantidade = qtd,
            valorEstimado = custo,
            valorRevenda = revenda,
            observacoes = obs,
            caminhoImagem = caminhoImagemSelecionada
        )

        viewModel.salvar(
            produto,
            onOk = {
                runOnUiThread {
                    Toast.makeText(this, "✅ Produto salvo com sucesso!", Toast.LENGTH_SHORT).show()
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

    // 🔹 Excluir produto selecionado
    private fun excluirProduto() {
        val produto = produtoSelecionado
        if (produto == null) {
            Toast.makeText(this, "Nenhum produto selecionado.", Toast.LENGTH_SHORT).show()
            return
        }

        viewModel.excluir(
            produto,
            onOk = {
                runOnUiThread {
                    Toast.makeText(this, "Produto excluído!", Toast.LENGTH_SHORT).show()
                    limparCampos()
                }
            },
            onErro = {
                runOnUiThread {
                    Toast.makeText(this, "Erro ao excluir: ${it.message}", Toast.LENGTH_LONG).show()
                }
            }
        )
    }

    // 🔹 Preenche campos com produto selecionado da lista
    private fun preencherCampos(produto: ProductEntity) {
        produtoSelecionado = produto
        etNome.setText(produto.nome)
        etQuantidade.setText(produto.quantidade.toString())
        etValorEstimado.setText(produto.valorEstimado?.toString() ?: "")
        etValorRevenda.setText(produto.valorRevenda?.toString() ?: "")
        etObservacoes.setText(produto.observacoes ?: "")
        caminhoImagemSelecionada = produto.caminhoImagem

        if (!produto.caminhoImagem.isNullOrBlank()) {
            val file = File(produto.caminhoImagem!!)
            if (file.exists()) {
                imgProduto.setImageBitmap(BitmapFactory.decodeFile(file.absolutePath))
            } else {
                imgProduto.setImageResource(R.drawable.ic_placeholder)
            }
        } else {
            imgProduto.setImageResource(R.drawable.ic_placeholder)
        }
    }

    // 🔹 Limpa o formulário
    private fun limparCampos() {
        produtoSelecionado = null
        caminhoImagemSelecionada = null
        etNome.text.clear()
        etQuantidade.text.clear()
        etValorEstimado.text.clear()
        etValorRevenda.text.clear()
        etObservacoes.text.clear()
        imgProduto.setImageResource(R.drawable.ic_placeholder)
    }

    // 🔹 Copia imagem escolhida para pasta interna do app
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
}
