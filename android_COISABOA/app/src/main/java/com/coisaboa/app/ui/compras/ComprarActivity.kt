package com.coisaboa.app.ui.compras
import androidx.core.widget.addTextChangedListener
import android.app.Activity
import android.content.Intent
import android.graphics.Bitmap
import android.os.Bundle
import android.provider.MediaStore
import android.widget.*
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.repository.PurchaseRepository
import com.coisaboa.app.utils.MediaStorage
import java.io.File
import java.util.*

class ComprarActivity : AppCompatActivity() {

    private lateinit var etProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorUnitario: EditText
    private lateinit var etValorRevenda: EditText
    private lateinit var tvValorTotal: TextView
    private lateinit var spPagamento: Spinner
    private lateinit var btnSalvar: Button
    private lateinit var btnFotoProduto: Button
    private lateinit var btnFotoNota: Button
    private lateinit var imgPreviewProduto: ImageView
    private lateinit var imgPreviewNota: ImageView

    private var caminhoFotoProduto: String? = null
    private var caminhoFotoNota: String? = null

    companion object {
        private const val REQ_FOTO_PRODUTO = 101
        private const val REQ_FOTO_NOTA = 102
    }

    private val viewModel: ComprarViewModel by viewModels {
        val db = DatabaseProvider.get(this)
        val repo = PurchaseRepository(db.purchaseDao(), db.productDao())
        ComprarViewModelFactory(repo)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_comprar)
        supportActionBar?.title = "Nova Compra"

        // Referências
        etProduto = findViewById(R.id.etProduto)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorUnitario = findViewById(R.id.etValorUnitario)
        etValorRevenda = findViewById(R.id.etValorRevenda)
        tvValorTotal = findViewById(R.id.tvValorTotal)
        spPagamento = findViewById(R.id.spPagamento)
        btnSalvar = findViewById(R.id.btnSalvarCompra)
        btnFotoProduto = findViewById(R.id.btnFotoProduto)
        btnFotoNota = findViewById(R.id.btnFotoNota)
        imgPreviewProduto = findViewById(R.id.imgPreviewProduto)
        imgPreviewNota = findViewById(R.id.imgPreviewNota)

        // Spinner de pagamento
        val formasPagamento = arrayOf(
            "Dinheiro", "Cartão Crédito", "Cartão Débito", "PIX", "Transferência", "Outro"
        )
        spPagamento.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, formasPagamento)

        // Calcula o valor total automaticamente
        val atualizarTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            val total = qtd * valor
            tvValorTotal.text = "R$ %.2f".format(total)
        }
        etQuantidade.addTextChangedListener { atualizarTotal() }
        etValorUnitario.addTextChangedListener { atualizarTotal() }

        // Botão para tirar foto do produto
        btnFotoProduto.setOnClickListener {
            val intent = Intent(MediaStore.ACTION_IMAGE_CAPTURE)
            startActivityForResult(intent, REQ_FOTO_PRODUTO)
        }

        // Botão para tirar foto da nota
        btnFotoNota.setOnClickListener {
            val intent = Intent(MediaStore.ACTION_IMAGE_CAPTURE)
            startActivityForResult(intent, REQ_FOTO_NOTA)
        }

        // Botão para salvar compra
        btnSalvar.setOnClickListener {
            salvarCompra()
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
            caminhoImagemNota = caminhoFotoNota,
            dataCompra = Date()
        )

        viewModel.registrarCompra(
            purchase = compra,
            onSucesso = { id ->
                Toast.makeText(this, "Compra registrada! ID: $id", Toast.LENGTH_LONG).show()
                finish()
            },
            onErro = { e ->
                Toast.makeText(this, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            }
        )
    }

    // Recebe as fotos tiradas pela câmera
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)

        if (resultCode == Activity.RESULT_OK && data != null) {
            val bitmap = data.extras?.get("data") as? Bitmap ?: return
            val mediaStorage = MediaStorage(this)

            when (requestCode) {
                REQ_FOTO_PRODUTO -> {
                    caminhoFotoProduto = mediaStorage.salvarImagem(bitmap, "produto")
                    imgPreviewProduto.setImageBitmap(bitmap)
                }
                REQ_FOTO_NOTA -> {
                    caminhoFotoNota = mediaStorage.salvarImagem(bitmap, "nota")
                    imgPreviewNota.setImageBitmap(bitmap)
                }
            }
        }
    }
}
