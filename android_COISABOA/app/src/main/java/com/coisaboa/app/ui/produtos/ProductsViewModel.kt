package com.coisaboa.app.ui.produtos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.repository.ProductRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.update

class ProductsViewModel(
    private val repo: ProductRepository
) : ViewModel() {

    private val _produtos = MutableStateFlow<List<ProductEntity>>(emptyList())
    val produtos: StateFlow<List<ProductEntity>> = _produtos

    fun carregarProdutos() {
        viewModelScope.launch(Dispatchers.IO) {
            val lista = repo.getAll()
            _produtos.update { lista }
        }
    }

    fun salvarProduto(produto: ProductEntity, onSucesso: () -> Unit = {}, onErro: (Throwable) -> Unit = {}) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.insertOrUpdate(produto) // ✅ aqui a correção
                val lista = repo.getAll()
                _produtos.update { lista }
                onSucesso()
            } catch (e: Exception) {
                onErro(e)
            }
        }
    }

    fun excluirProduto(produto: ProductEntity, onSucesso: () -> Unit = {}, onErro: (Throwable) -> Unit = {}) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.delete(produto)
                val lista = repo.getAll()
                _produtos.update { lista }
                onSucesso()
            } catch (e: Exception) {
                onErro(e)
            }
        }
    }
}
