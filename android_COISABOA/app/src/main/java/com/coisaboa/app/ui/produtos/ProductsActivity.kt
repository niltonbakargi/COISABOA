package com.coisaboa.app.ui.produtos

import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.coisaboa.app.databinding.ActivityProductsBinding
import com.coisaboa.app.config.DatabaseProvider
import kotlinx.coroutines.launch

class ProductsActivity : AppCompatActivity() {

    private lateinit var binding: ActivityProductsBinding
    private lateinit var adapter: ProductAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityProductsBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Produtos"

        // ✅ Cria adapter com lista vazia e clique
        adapter = ProductAdapter(mutableListOf()) { produto ->
            Toast.makeText(this, "Selecionou: ${produto.nome}", Toast.LENGTH_SHORT).show()
        }

        binding.recyclerView.layoutManager = LinearLayoutManager(this)
        binding.recyclerView.adapter = adapter

        // ✅ Carrega os produtos do banco (Room)
        carregarProdutos()
    }

    private fun carregarProdutos() {
        val dao = DatabaseProvider.get(this).productDao()

        lifecycleScope.launch {
            val produtos = dao.getAll()
            adapter.updateList(produtos)
        }
    }
}
