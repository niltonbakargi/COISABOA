package com.coisaboa.app.ui.compras

import android.app.Activity
import android.content.Intent
import android.graphics.Bitmap
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
import com.coisaboa.app.data.repository.PurchaseRepository
import com.coisaboa.app.utils.MediaStorage
import java.util.*

class ComprarActivity : AppCompatActivity() {

    // 🔹 Referências da UI
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

    // 🔹 Caminhos das fotos salvas
    private var caminhoFotoProduto: String? = null
    private var caminhoFotoVendedor: String? = null

    // 🔹 Controle da câmera
    private var tipoFotoAtual: String = ""

    // 🔹 Classe utilitária para salvar imagens localmente
    private val mediaStorage by lazy { MediaStorage(this) }

    // 🔹 ViewModel
    private val viewModel: ComprarViewModel by viewModels {
        val db = DatabaseProvider.get(this)
        val repo = PurchaseRepository(db.purchaseDao(), db.productDao())
        ComprarViewModelFactory(repo)
    }

    // 🔹 Launcher moderno para capturar imagem
    private val cameraLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode == Activity.RESULT_OK && result.data != null) {
                val bitmap = result.data!!.extras?.get("data") as? Bitmap
                if (bitmap != null) {
                    val caminho = mediaStorage.salvarImagem(bitmap, tipoFotoAtual)
                    when (tipoFotoAtual) {
                        "produto" -> {
                            caminhoFotoProduto = caminho
                            imgPreviewProduto.setImageBitmap(bitmap)
                            Toast.makeText(this, "📷 Foto do produto salva!", Toast.LENGTH_SHORT).show()
                        }
                        "vendedor" -> {
                            caminhoFotoVendedor = caminho
                            imgPreviewVendedor.setImageBitmap(bitmap)
                            Toast.makeText(this, "🧑‍🌾 Foto do vendedor salva!", Toast.LENGTH_SHORT).show()
                        }
                    }
                } else {
                    Toast.makeText(this, "Erro ao capturar imagem!", Toast.LENGTH_SHORT).show()
                }
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_comprar)
        supportActionBar?.title = "Nova Compra"

        inicializarComponentes()
        configurarEventos()
    }

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

        // 🪙 Opções de pagamento
        val formasPagamento = arrayOf(
            "Dinheiro", "Cartão Crédito", "Cartão Débito", "PIX", "Transferência", "Outro"
        )
        spPagamento.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, formasPagamento)
    }

    private fun configurarEventos() {
        // 🧮 Atualiza valor total automaticamente
        val atualizarTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            val total = qtd * valor
            tvValorTotal.text = "R$ %.2f".format(total)
        }
        etQuantidade.addTextChangedListener { atualizarTotal() }
        etValorUnitario.addTextChangedListener { atualizarTotal() }

        // 📸 Foto do produto
        btnFotoProduto.setOnClickListener {
            tipoFotoAtual = "produto"
            abrirCamera()
        }

        // 📸 Foto do vendedor
        btnFotoVendedor.setOnClickListener {
            tipoFotoAtual = "vendedor"
            abrirCamera()
        }

        // 💾 Salvar compra
        btnSalvar.setOnClickListener {
            salvarCompra()
        }
    }

    private fun abrirCamera() {
        val intent = Intent(MediaStore.ACTION_IMAGE_CAPTURE)
        if (intent.resolveActivity(packageManager) != null) {
            cameraLauncher.launch(intent)
        } else {
            Toast.makeText(this, "Câmera não disponível!", Toast.LENGTH_SHORT).show()
        }
    }

    private fun salvarCompra() {
        val produto = etProduto.text.toString().trim()
        val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
        val valorUnit = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
        val valorRevenda = etValorRevenda.text.toString().toDoubleOrNull() ?: 0.0
        val formaPagamento = spPagamento.selectedItem.toString()
        val valorTotal = qtd * valorUnit

        if (produto.isEmpty() || qtd <= 0 || valorUnit <= 0.0) {
            Toast.makeText(this, "Preencha todos os campos obrigatórios!", Toast.LENGTH_SHORT).show()
            return
        }

        val compra = PurchaseEntity(
            id = 0,
            produto = produto,
            quantidade = qtd,
            valorUnitario = valorUnit,
            valorTotal = valorTotal,
            valorRevenda = valorRevenda,
            formaPagamento = formaPagamento,
            caminhoImagemProduto = caminhoFotoProduto,
            caminhoImagemNota = caminhoFotoVendedor, // usando campo nota para foto do vendedor
            dataCompra = Date()
        )

        viewModel.registrarCompra(
            purchase = compra,
            onSucesso = { id ->
                Toast.makeText(this, "✅ Compra registrada! ID: $id", Toast.LENGTH_LONG).show()
                finish()
            },
            onErro = { e ->
                Toast.makeText(this, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            }
        )
    }
}
