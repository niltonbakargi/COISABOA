package com.coisaboa.app.ui.compras

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.repository.PurchaseRepository
import kotlinx.coroutines.launch

/**
 * ViewModel responsável por controlar a lógica da tela de compras.
 * Recebe as informações da interface e repassa ao repositório.
 */
class ComprarViewModel(
    private val repository: PurchaseRepository
) : ViewModel() {

    /**
     * Registra uma compra no banco de dados local.
     * Recebe o objeto completo da compra e chama o repositório.
     */
    fun registrarCompra(
        purchase: PurchaseEntity,
        onSucesso: (Long) -> Unit,
        onErro: (Throwable) -> Unit
    ) {
        viewModelScope.launch {
            try {
                val id = repository.registrarCompra(purchase)
                onSucesso(id)
            } catch (e: Exception) {
                onErro(e)
            }
        }
    }
}
