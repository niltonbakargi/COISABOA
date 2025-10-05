package com.coisaboa.app.ui.produtos

import android.os.Bundle
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import com.coisaboa.app.databinding.ActivityProductsBinding

class ProductsActivity : AppCompatActivity() {

    private lateinit var binding: ActivityProductsBinding
    private val vm: ProductsViewModel by viewModels()
    private val adapter = ProductAdapter()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityProductsBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Produtos"

        binding.rvProducts.apply {
            layoutManager = LinearLayoutManager(this@ProductsActivity)
            adapter = this@ProductsActivity.adapter
            setHasFixedSize(true)
        }

        vm.products.observe(this) { list ->
            adapter.submitList(list ?: emptyList())
        }

        binding.btnAddProduct.setOnClickListener {
            vm.add(
                nome = "Produto " + System.currentTimeMillis().toString().takeLast(4),
                quantidade = 1,
                valor = 10.0
            )
        }
    }
}
