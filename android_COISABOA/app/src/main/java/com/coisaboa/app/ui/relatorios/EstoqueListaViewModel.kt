package com.coisaboa.app.ui.relatorios

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.repository.ProductRepository
import kotlinx.coroutines.launch

class EstoqueListaViewModel(
    private val productRepository: ProductRepository
) : ViewModel() {

    private val _produtos = MutableLiveData<List<ProductEntity>>()
    val produtos: LiveData<List<ProductEntity>> = _produtos

    fun carregarProdutosOrdenados(ordem: OrdemEstoque) {
        viewModelScope.launch {
            val todosProdutos = productRepository.getAll()
            val produtosOrdenados = when (ordem) {
                OrdemEstoque.ALFABETICA -> todosProdutos.sortedBy { it.nome }
                OrdemEstoque.MAIS_RECENTES -> todosProdutos.sortedByDescending { it.id }
                OrdemEstoque.MAIS_ANTIGOS -> todosProdutos.sortedBy { it.id }
            }
            _produtos.value = produtosOrdenados
        }
    }
}