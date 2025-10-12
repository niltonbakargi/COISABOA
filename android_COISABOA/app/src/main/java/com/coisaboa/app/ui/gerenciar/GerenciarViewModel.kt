package com.coisaboa.app.ui.gerenciar

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.repository.ProductRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

/**
 * 🔹 GerenciarViewModel
 * Responsável por gerenciar o estado e as operações da tela de estoque.
 * Atua como ponte entre o banco de dados (Room) e a interface de usuário.
 */
class GerenciarViewModel(
    private val repo: ProductRepository
) : ViewModel() {

    // 🔸 Fluxo reativo que mantém a lista de produtos atualizada
    private val _produtos = MutableStateFlow<List<ProductEntity>>(emptyList())
    val produtos: StateFlow<List<ProductEntity>> = _produtos

    /**
     * 📦 Carrega todos os produtos do banco e atualiza o fluxo reativo.
     */
    fun carregar() {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                val lista = repo.getAll()
                _produtos.update { lista }
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }

    /**
     * 💾 Insere ou atualiza um produto.
     * Após a operação, atualiza o fluxo de dados e executa o callback.
     */
    fun salvar(
        produto: ProductEntity,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.insertOrUpdate(produto)
                val lista = repo.getAll()
                _produtos.update { lista }
                onOk()
            } catch (e: Throwable) {
                onErro(e)
            }
        }
    }

    /**
     * 🗑️ Exclui um produto e atualiza o fluxo de dados.
     */
    fun excluir(
        produto: ProductEntity,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.delete(produto)
                val lista = repo.getAll()
                _produtos.update { lista }
                onOk()
            } catch (e: Throwable) {
                onErro(e)
            }
        }
    }

    /**
     * 🔄 Ajusta manualmente o estoque de um produto.
     * @param id Long → ID do produto
     * @param delta Int → quantidade (positivo = entrada, negativo = saída)
     */
    fun ajustarEstoque(
        id: Long,
        delta: Int,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                if (delta > 0) {
                    repo.increaseStock(id, delta)
                } else {
                    repo.decreaseStock(id, -delta)
                }

                val lista = repo.getAll()
                _produtos.update { lista }
                onOk()
            } catch (e: Throwable) {
                onErro(e)
            }
        }
    }
}
