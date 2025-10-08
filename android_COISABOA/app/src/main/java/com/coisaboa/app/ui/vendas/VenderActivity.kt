package com.coisaboa.app.ui.vendas

import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class VenderActivity : AppCompatActivity() {

    private lateinit var etProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorUnitario: EditText
    private lateinit var tvValorTotal: TextView
    private lateinit var spPagamento: Spinner
    private lateinit var btnSalvarVenda: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_vender)
        supportActionBar?.title = "Nova Venda"

        etProduto = findViewById(R.id.etProduto)
        etQuantidade = findViewById(R.id.etQuantidade)
        etValorUnitario = findViewById(R.id.etValorUnitario)
        tvValorTotal = findViewById(R.id.tvValorTotal)
        spPagamento = findViewById(R.id.spPagamento)
        btnSalvarVenda = findViewById(R.id.btnSalvarVenda)

        // Popular spinner com formas de pagamento
        val formasPagamento = arrayOf("Dinheiro", "Cartão", "PIX", "Transferência", "Outro")
        spPagamento.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, formasPagamento)

        // Função de cálculo do total
        val calcularTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            val total = qtd * valor
            tvValorTotal.text = "R$ %.2f".format(total)
        }

        // TextWatcher nativo
        val watcher = object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                calcularTotal()
            }
            override fun afterTextChanged(s: Editable?) {}
        }

        etQuantidade.addTextChangedListener(watcher)
        etValorUnitario.addTextChangedListener(watcher)

        btnSalvarVenda.setOnClickListener {
            val produto = etProduto.text.toString()
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            val pagamento = spPagamento.selectedItem.toString()
            val total = qtd * valor

            if (produto.isEmpty() || qtd <= 0 || valor <= 0.0) {
                Toast.makeText(this, "Preencha todos os campos obrigatórios!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val resumo = """
                ✅ Venda registrada!
                Produto: $produto
                Quantidade: $qtd
                Valor Unitário: R$ %.2f
                Total: R$ %.2f
                Pagamento: $pagamento
            """.trimIndent().format(valor, total)

            Toast.makeText(this, resumo, Toast.LENGTH_LONG).show()
            finish()
        }
    }
}
