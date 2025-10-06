package com.coisaboa.app.ui.produtos

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.repository.ProductRepository
import kotlinx.coroutines.launch

/**
 * ViewModel responsável por carregar e gerenciar os produtos.
 */
class ProductsViewModel(app: Application) : AndroidViewModel(app) {

    private val repo = ProductRepository(DatabaseProvider.get(app).productDao())

    private val _produtos = MutableLiveData<List<ProductEntity>>(emptyList())
    val produtos: LiveData<List<ProductEntity>> = _produtos

    private val _erro = MutableLiveData<String?>()
    val erro: LiveData<String?> = _erro

    init {
        carregarProdutos()
    }

    // ✅ Chamando o suspend dentro de uma coroutine
    fun carregarProdutos() {
        viewModelScope.launch {
            try {
                val lista = repo.getAll()
                _produtos.value = lista
            } catch (e: Exception) {
                _erro.value = "Erro ao carregar produtos: ${e.message}"
            }
        }
    }

    // Exemplo: inserir um produto
    fun adicionarProduto(produto: ProductEntity) {
        viewModelScope.launch {
            try {
                repo.insert(produto)
                carregarProdutos()
            } catch (e: Exception) {
                _erro.value = "Erro ao inserir produto: ${e.message}"
            }
        }
    }

    fun excluirProduto(produto: ProductEntity) {
        viewModelScope.launch {
            try {
                repo.delete(produto)
                carregarProdutos()
            } catch (e: Exception) {
                _erro.value = "Erro ao excluir produto: ${e.message}"
            }
        }
    }
}
