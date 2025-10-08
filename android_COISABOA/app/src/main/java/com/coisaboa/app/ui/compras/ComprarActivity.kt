package com.coisaboa.app.ui.compras

import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class ComprarActivity : AppCompatActivity() {

    private lateinit var etProduto: EditText
    private lateinit var etQuantidade: EditText
    private lateinit var etValorUnitario: EditText
    private lateinit var etValorRevenda: EditText
    private lateinit var tvValorTotal: TextView
    private lateinit var spPagamento: Spinner
    private lateinit var btnSalvar: Button

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

        // Popular spinner
        val formasPagamento = arrayOf(
            "Dinheiro",
            "Cartão Crédito",
            "Cartão Débito",
            "PIX",
            "Transferência",
            "Outro"
        )
        spPagamento.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, formasPagamento)

        // Função de cálculo do total
        val calcularTotal = {
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            val total = qtd * valor
            tvValorTotal.text = "R$ %.2f".format(total)
        }

        // Criar um TextWatcher para atualizar o total
        val watcher = object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                calcularTotal()
            }
            override fun afterTextChanged(s: Editable?) {}
        }

        // Associar aos campos
        etQuantidade.addTextChangedListener(watcher)
        etValorUnitario.addTextChangedListener(watcher)

        // Botão salvar compra
        btnSalvar.setOnClickListener {
            val produto = etProduto.text.toString().trim()
            val qtd = etQuantidade.text.toString().toIntOrNull() ?: 0
            val valor = etValorUnitario.text.toString().toDoubleOrNull() ?: 0.0
            val revenda = etValorRevenda.text.toString().toDoubleOrNull() ?: 0.0
            val pagamento = spPagamento.selectedItem.toString()

            if (produto.isEmpty() || qtd <= 0 || valor <= 0.0) {
                Toast.makeText(this, "Preencha todos os campos obrigatórios!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val valorTotal = qtd * valor
            val resumo = """
                ✅ Compra salva com sucesso!
                Produto: $produto
                Quantidade: $qtd
                Valor Unitário: R$ %.2f
                Valor Total: R$ %.2f
                Valor Revenda: R$ %.2f
                Pagamento: $pagamento
            """.trimIndent().format(valor, valorTotal, revenda)

            Toast.makeText(this, resumo, Toast.LENGTH_LONG).show()
            finish()
        }
    }
}
