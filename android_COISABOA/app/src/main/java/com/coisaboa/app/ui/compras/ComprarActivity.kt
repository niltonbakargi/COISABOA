package com.coisaboa.app.ui.compras

import android.app.Activity
import android.content.Intent
import android.graphics.Bitmap
import android.graphics.ImageDecoder
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.provider.MediaStore
import android.widget.*
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.widget.addTextChangedListener
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.data.repository.PurchaseRepository
import com.coisaboa.app.utils.MediaStorage
import java.util.*

/**
 * 🛒 ComprarActivity (versão final)
 * Registra novas compras e atualiza o estoque somente após a confirmação.
 */
class ComprarActivity : AppCompatActivity() {

    // 🔹 Componentes de interface
    private lateinit var etProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorUnitario: EditText
    private lateinit var etValorRevenda: EditText
    private lateinit var tvValorTotal: TextView
    private lateinit var spPagamento: Spinner
    private lateinit var btnSalvar: Button
    private lateinit var btnFotoProduto: Button
    private lateinit var btnFotoVendedor: Button
    private lateinit var imgPreviewProduto: ImageView
    private lateinit var imgPreviewVendedor: ImageView

    // 🔹 Estado interno
    private var caminhoFotoProduto: String? = null
    private var caminhoFotoVendedor: String? = null
    private var tipoFotoAtual: String = ""

    // 🔹 Utilitário de salvamento de imagens
    private val mediaStorage by lazy { MediaStorage(this) }

    // 🔹 Injeção de dependências com repositórios corretos
    private val viewModel: ComprarViewModel by viewModels {
        val db = DatabaseProvider.get(this)
        val productRepo = ProductRepository(db.productDao())
        val purchaseRepo = PurchaseRepository(db.purchaseDao(), productRepo)
        ComprarViewModelFactory(purchaseRepo)
    }

    // 🖼️ Launcher de galeria
    private val galeriaLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode == Activity.RESULT_OK && result.data?.data != null) {
                val uri: Uri = result.data!!.data!!
                processarImagemSelecionada(uri)
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_comprar)
        supportActionBar?.title = "Nova Compra"

        inicializarComponentes()
        configurarEventos()
    }

    /** 🧩 Inicializa os elementos da interface */
    private fun inicializarComponentes() {
        etProduto = findViewById(R.id.etProduto)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorUnitario = findViewById(R.id.etValorUnitario)
        etValorRevenda = findViewById(R.id.etValorRevenda)
        tvValorTotal = findViewById(R.id.tvValorTotal)
        spPagamento = findViewById(R.id.spPagamento)
        btnSalvar = findViewById(R.id.btnSalvarCompra)
        btnFotoProduto = findViewById(R.id.btnFotoProduto)
        btnFotoVendedor = findViewById(R.id.btnFotoVendedor)
        imgPreviewProduto = findViewById(R.id.imgPreviewProduto)
        imgPreviewVendedor = findViewById(R.id.imgPreviewVendedor)

        // 💳 Formas de pagamento
        val formasPagamento = arrayOf(
            "Dinheiro", "PIX", "Cartão de Crédito", "Cartão de Débito", "Transferência", "Outro"
        )
        spPagamento.adapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_dropdown_item,
            formasPagamento
        )
    }

    /** ⚙️ Configura os eventos da tela */
    private fun configurarEventos() {
        // Atualiza total automaticamente
        val atualizarTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            tvValorTotal.text = "R$ %.2f".format(qtd * valor)
        }

        etQuantidade.addTextChangedListener { atualizarTotal() }
        etValorUnitario.addTextChangedListener { atualizarTotal() }

        btnFotoProduto.setOnClickListener {
            tipoFotoAtual = "produto"
            abrirGaleria()
        }

        btnFotoVendedor.setOnClickListener {
            tipoFotoAtual = "vendedor"
            abrirGaleria()
        }

        btnSalvar.setOnClickListener { salvarCompra() }
    }

    /** 📂 Abre a galeria */
    private fun abrirGaleria() {
        val intent = Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI)
        galeriaLauncher.launch(intent)
    }

    /** 🖼️ Processa e salva a imagem localmente */
    private fun processarImagemSelecionada(uri: Uri) {
        try {
            val bitmap: Bitmap = if (Build.VERSION.SDK_INT >= 28) {
                ImageDecoder.decodeBitmap(ImageDecoder.createSource(contentResolver, uri))
            } else {
                @Suppress("DEPRECATION")
                MediaStore.Images.Media.getBitmap(contentResolver, uri)
            }

            val caminho = mediaStorage.salvarImagem(bitmap, tipoFotoAtual)
            atualizarPreview(bitmap, caminho)
        } catch (e: Exception) {
            Toast.makeText(this, "Erro ao carregar imagem: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }

    /** 🖼️ Atualiza os previews de imagem */
    private fun atualizarPreview(bitmap: Bitmap, caminho: String) {
        when (tipoFotoAtual) {
            "produto" -> {
                caminhoFotoProduto = caminho
                imgPreviewProduto.setImageBitmap(bitmap)
                Toast.makeText(this, "🖼️ Imagem do produto salva!", Toast.LENGTH_SHORT).show()
            }
            "vendedor" -> {
                caminhoFotoVendedor = caminho
                imgPreviewVendedor.setImageBitmap(bitmap)
                Toast.makeText(this, "🧾 Imagem da nota/vendedor salva!", Toast.LENGTH_SHORT).show()
            }
        }
    }

    /** 💾 Valida e salva a compra */
    private fun salvarCompra() {
        val produto = etProduto.text.toString().trim()
        val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
        val valorUnit = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
        val valorRevenda = etValorRevenda.text.toString().toDoubleOrNull()
        val formaPagamento = spPagamento.selectedItem?.toString() ?: "Não informado"

        // 🚨 Validação
        if (produto.isEmpty() || qtd <= 0 || valorUnit <= 0.0) {
            Toast.makeText(this, "Preencha todos os campos obrigatórios.", Toast.LENGTH_SHORT).show()
            return
        }

        val valorTotal = qtd * valorUnit

        val compra = PurchaseEntity(
            id = 0,
            produtoNome = produto,
            quantidade = qtd,
            valorUnitario = valorUnit,
            valorRevenda = valorRevenda,
            valorTotal = valorTotal,
            formaPagamento = formaPagamento,
            caminhoImagemProduto = caminhoFotoProduto,
            caminhoImagemNota = caminhoFotoVendedor,
            dataCompra = Date()
        )

        viewModel.registrarCompra(
            purchase = compra,
            onSucesso = { id ->
                runOnUiThread {
                    Toast.makeText(this, "✅ Compra registrada (ID $id)", Toast.LENGTH_LONG).show()
                    finish()
                }
            },
            onErro = { e ->
                runOnUiThread {
                    Toast.makeText(this, "❌ Erro ao salvar: ${e.message}", Toast.LENGTH_LONG).show()
                }
            }
        )
    }
}
