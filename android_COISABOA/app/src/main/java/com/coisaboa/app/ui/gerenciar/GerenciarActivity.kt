package com.coisaboa.app.ui.gerenciar

import android.os.Bundle
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class GerenciarActivity : AppCompatActivity() {

    private lateinit var spProduto: Spinner
    private lateinit var etNovaQuantidade: EditText
    private lateinit var etNovoCusto: EditText
    private lateinit var etNovoRevenda: EditText
    private lateinit var etObservacoes: EditText
    private lateinit var tvInfo: TextView
    private lateinit var btnAjustar: Button
    private lateinit var btnCorrigir: Button
    private lateinit var btnExcluir: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_gerenciar)
        supportActionBar?.title = "Gerenciar Estoque"

        // Vincular elementos
        spProduto = findViewById(R.id.spProduto)
        etNovaQuantidade = findViewById(R.id.etNovaQuantidade)
        etNovoCusto = findViewById(R.id.etNovoCusto)
        etNovoRevenda = findViewById(R.id.etNovoRevenda)
        etObservacoes = findViewById(R.id.etObservacoes)
        tvInfo = findViewById(R.id.tvInfo)
        btnAjustar = findViewById(R.id.btnAjustar)
        btnCorrigir = findViewById(R.id.btnCorrigir)
        btnExcluir = findViewById(R.id.btnExcluir)

        // Exemplo de produtos simulados
        val produtos = listOf("Selecione um produto...", "Cadeira Gamer", "Mesa de Escritório", "Monitor 24\"")
        spProduto.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, produtos)

        // Mostrar informações simuladas
        spProduto.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>, view: android.view.View?, pos: Int, id: Long) {
                if (pos == 0) tvInfo.text = "Selecione um produto para visualizar informações"
                else tvInfo.text = "Produto selecionado: ${produtos[pos]}"
            }

            override fun onNothingSelected(parent: AdapterView<*>) {}
        }

        // Botões
        btnAjustar.setOnClickListener {
            val qtd = etNovaQuantidade.text.toString().toIntOrNull()
            if (qtd == null || qtd < 0) {
                Toast.makeText(this, "Quantidade inválida!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            Toast.makeText(this, "✅ Estoque ajustado para $qtd unidades.", Toast.LENGTH_SHORT).show()
        }

        btnCorrigir.setOnClickListener {
            val custo = etNovoCusto.text.toString().toDoubleOrNull()
            val revenda = etNovoRevenda.text.toString().toDoubleOrNull()
            if (custo == null || revenda == null) {
                Toast.makeText(this, "Preencha os novos preços!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            Toast.makeText(this, "💰 Preços atualizados: custo R$ %.2f / revenda R$ %.2f".format(custo, revenda), Toast.LENGTH_LONG).show()
        }

        btnExcluir.setOnClickListener {
            val produto = spProduto.selectedItem.toString()
            if (spProduto.selectedItemPosition == 0) {
                Toast.makeText(this, "Selecione um produto primeiro!", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            Toast.makeText(this, "⚠️ Produto '$produto' excluído do estoque!", Toast.LENGTH_SHORT).show()
        }
    }
}
