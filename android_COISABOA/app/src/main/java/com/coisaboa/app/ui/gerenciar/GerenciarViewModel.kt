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
 * ⚙️ GerenciarViewModel
 * Camada de controle da tela de gerenciamento de estoque.
 * Faz a ponte entre a Activity (UI) e o Repository (dados Room).
 */
class GerenciarViewModel(
    private val repo: ProductRepository
) : ViewModel() {

    // ============================================================
    // 🔸 LISTA REATIVA DE PRODUTOS
    // ============================================================
    private val _produtos = MutableStateFlow<List<ProductEntity>>(emptyList())
    val produtos: StateFlow<List<ProductEntity>> = _produtos

    // ============================================================
    // 📦 CARREGAMENTO INICIAL
    // ============================================================

    /**
     * 📦 Carrega todos os produtos cadastrados no banco local.
     */
    fun carregar() {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                _produtos.update { repo.getAll() }
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }

    // ============================================================
    // 💾 INSERÇÃO / ATUALIZAÇÃO
    // ============================================================

    /**
     * 💾 Insere um novo produto ou atualiza um existente.
     * Após a operação, a lista reativa é atualizada.
     */
    fun salvar(
        produto: ProductEntity,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.insertOrUpdate(produto)
                sincronizar()
                onOk()
            } catch (e: Throwable) {
                e.printStackTrace()
                onErro(e)
            }
        }
    }

    // ============================================================
    // 🗑️ EXCLUSÃO TOTAL
    // ============================================================

    /**
     * 🗑️ Remove um produto definitivamente do banco.
     */
    fun excluir(
        produto: ProductEntity,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.delete(produto)
                sincronizar()
                onOk()
            } catch (e: Throwable) {
                e.printStackTrace()
                onErro(e)
            }
        }
    }

    // ============================================================
    // 🔺 AJUSTE DE ESTOQUE
    // ============================================================

    /**
     * 🔺 Aumenta o estoque de um produto existente.
     */
    fun aumentarEstoque(
        id: Long,
        quantidade: Int,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.increaseStock(id, quantidade)
                sincronizar()
                onOk()
            } catch (e: Throwable) {
                e.printStackTrace()
                onErro(e)
            }
        }
    }

    /**
     * 🔻 Diminui o estoque de um produto existente.
     * Caso a quantidade informada exceda o estoque atual,
     * o Repository/DAO garante que o valor não fique negativo.
     */
    fun diminuirEstoque(
        id: Long,
        quantidade: Int,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                repo.decreaseStock(id, quantidade)
                sincronizar()
                onOk()
            } catch (e: Throwable) {
                e.printStackTrace()
                onErro(e)
            }
        }
    }

    /**
     * ⚖️ Ajusta o estoque dinamicamente.
     * @param delta Quantidade positiva (entrada) ou negativa (saída)
     */
    fun ajustarEstoque(
        id: Long,
        delta: Int,
        onOk: () -> Unit = {},
        onErro: (Throwable) -> Unit = {}
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                if (delta > 0) repo.increaseStock(id, delta)
                else repo.decreaseStock(id, -delta)
                sincronizar()
                onOk()
            } catch (e: Throwable) {
                e.printStackTrace()
                onErro(e)
            }
        }
    }

    // ============================================================
    // 🔄 SINCRONIZAÇÃO GERAL
    // ============================================================

    /**
     * 🔄 Atualiza a lista reativa de produtos após qualquer operação.
     */
    private fun sincronizar() {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                val lista = repo.getAll()
                _produtos.update { lista }
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }
}
