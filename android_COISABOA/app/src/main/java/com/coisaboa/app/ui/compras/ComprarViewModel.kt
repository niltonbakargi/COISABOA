package com.coisaboa.app.ui.compras

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.repository.PurchaseRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * 💼 ComprarViewModel
 * Responsável por intermediar a tela de compras (UI) com o repositório de dados.
 */
class ComprarViewModel(
    private val repository: PurchaseRepository
) : ViewModel() {

    /**
     * 🔹 Registra uma nova compra no banco e atualiza o estoque automaticamente.
     *
     * @param purchase Objeto da compra
     * @param onSucesso Callback com o ID da compra salva
     * @param onErro Callback em caso de falha
     */
    fun registrarCompra(
        purchase: PurchaseEntity,
        onSucesso: (Long) -> Unit,
        onErro: (Throwable) -> Unit
    ) {
        viewModelScope.launch(Dispatchers.IO) {
            try {
                val id = repository.insertCompra(purchase)
                onSucesso(id)
            } catch (e: Throwable) {
                onErro(e)
            }
        }
    }
}
