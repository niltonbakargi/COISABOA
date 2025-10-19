package com.coisaboa.app.ui.relatorios

import androidx.appcompat.app.AppCompatActivity
import android.os.Bundle
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.databinding.ActivityEstoqueListaBinding
import com.coisaboa.app.ui.relatorios.adapters.EstoqueListaAdapter
import kotlinx.coroutines.launch

class EstoqueListaActivity : AppCompatActivity() {

    private lateinit var binding: ActivityEstoqueListaBinding
    private lateinit var adapter: EstoqueListaAdapter
    private lateinit var productRepository: ProductRepository
    
    // Estado da ordenação atual
    private var ordemAtual: OrdemEstoque = OrdemEstoque.ALFABETICA

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityEstoqueListaBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Inicializa o repositório
        val db = DatabaseProvider.get(this)
        productRepository = ProductRepository(db.productDao())

        setupRecyclerView()
        setupClickListeners()
        carregarProdutosOrdenados(ordemAtual)
        
        // ATUALIZA OS BOTÕES INICIALMENTE
        updateButtonStates()
    }

    private fun setupRecyclerView() {
        adapter = EstoqueListaAdapter()
        binding.rvProdutosEstoque.layoutManager = LinearLayoutManager(this)
        binding.rvProdutosEstoque.adapter = adapter
    }

    private fun setupClickListeners() {
        binding.btnOrdemAlfabetica.setOnClickListener {
            android.util.Log.d("ESTOQUE", "Botão A-Z clicado")
            ordemAtual = OrdemEstoque.ALFABETICA
            carregarProdutosOrdenados(ordemAtual)
            updateButtonStates()
        }

        binding.btnMaisRecentes.setOnClickListener {
            android.util.Log.d("ESTOQUE", "Botão Recentes clicado")
            ordemAtual = OrdemEstoque.MAIS_RECENTES
            carregarProdutosOrdenados(ordemAtual)
            updateButtonStates()
        }

        binding.btnMaisAntigos.setOnClickListener {
            android.util.Log.d("ESTOQUE", "Botão Antigos clicado")
            ordemAtual = OrdemEstoque.MAIS_ANTIGOS
            carregarProdutosOrdenados(ordemAtual)
            updateButtonStates()
        }

        binding.btnVoltar.setOnClickListener {
            finish()
        }
    }

    private fun carregarProdutosOrdenados(ordem: OrdemEstoque) {
        lifecycleScope.launch {
            try {
                val todosProdutos = productRepository.getAll()
                val produtosOrdenados = when (ordem) {
                    OrdemEstoque.ALFABETICA -> todosProdutos.sortedBy { it.nome }
                    OrdemEstoque.MAIS_RECENTES -> todosProdutos.sortedByDescending { it.id }
                    OrdemEstoque.MAIS_ANTIGOS -> todosProdutos.sortedBy { it.id }
                }
                adapter.submitList(produtosOrdenados)
                binding.tvTotalProdutos.text = "Total: ${produtosOrdenados.size} produtos"
                
                // Mostra/oculta empty state
                if (produtosOrdenados.isEmpty()) {
                    binding.tvEmptyState.visibility = android.view.View.VISIBLE
                    binding.rvProdutosEstoque.visibility = android.view.View.GONE
                } else {
                    binding.tvEmptyState.visibility = android.view.View.GONE
                    binding.rvProdutosEstoque.visibility = android.view.View.VISIBLE
                }
                
                android.util.Log.d("ESTOQUE", "Produtos carregados: ${produtosOrdenados.size} - Ordem: $ordem")
                
            } catch (e: Exception) {
                android.util.Log.e("ESTOQUE", "Erro ao carregar produtos: ${e.message}")
                binding.tvTotalProdutos.text = "Erro ao carregar produtos"
            }
        }
    }

    private fun updateButtonStates() {
        // Atualiza estado visual dos botões
        binding.btnOrdemAlfabetica.isSelected = ordemAtual == OrdemEstoque.ALFABETICA
        binding.btnMaisRecentes.isSelected = ordemAtual == OrdemEstoque.MAIS_RECENTES
        binding.btnMaisAntigos.isSelected = ordemAtual == OrdemEstoque.MAIS_ANTIGOS
        
        // Muda cor de fundo para visualização clara
        val selectedColor = ContextCompat.getColor(this, android.R.color.holo_blue_light)
        val normalColor = ContextCompat.getColor(this, android.R.color.transparent)
        
        binding.btnOrdemAlfabetica.setBackgroundColor(
            if (ordemAtual == OrdemEstoque.ALFABETICA) selectedColor else normalColor
        )
        binding.btnMaisRecentes.setBackgroundColor(
            if (ordemAtual == OrdemEstoque.MAIS_RECENTES) selectedColor else normalColor
        )
        binding.btnMaisAntigos.setBackgroundColor(
            if (ordemAtual == OrdemEstoque.MAIS_ANTIGOS) selectedColor else normalColor
        )
        
        // Muda cor do texto
        val selectedTextColor = ContextCompat.getColor(this, android.R.color.white)
        val normalTextColor = ContextCompat.getColor(this, android.R.color.black)
        
        binding.btnOrdemAlfabetica.setTextColor(
            if (ordemAtual == OrdemEstoque.ALFABETICA) selectedTextColor else normalTextColor
        )
        binding.btnMaisRecentes.setTextColor(
            if (ordemAtual == OrdemEstoque.MAIS_RECENTES) selectedTextColor else normalTextColor
        )
        binding.btnMaisAntigos.setTextColor(
            if (ordemAtual == OrdemEstoque.MAIS_ANTIGOS) selectedTextColor else normalTextColor
        )
        
        android.util.Log.d("ESTOQUE", "Botões atualizados - Ordem atual: $ordemAtual")
    }
}

enum class OrdemEstoque {
    ALFABETICA, MAIS_RECENTES, MAIS_ANTIGOS
}