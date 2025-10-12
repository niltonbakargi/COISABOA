package com.coisaboa.app.ui.compras

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import com.coisaboa.app.data.repository.PurchaseRepository

/**
 * 🏗️ ComprarViewModelFactory
 * Responsável por criar instâncias de [ComprarViewModel],
 * injetando corretamente o [PurchaseRepository].
 *
 * Essa fábrica garante a injeção segura de dependências
 * para o uso do ViewModel no Android Jetpack.
 */
class ComprarViewModelFactory(
    private val purchaseRepository: PurchaseRepository
) : ViewModelProvider.Factory {

    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        // Garante que estamos criando o ViewModel correto
        if (modelClass.isAssignableFrom(ComprarViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return ComprarViewModel(purchaseRepository) as T
        }

        // Caso ocorra uso incorreto da factory
        throw IllegalArgumentException(
            "Classe ViewModel desconhecida: ${modelClass.name}"
        )
    }
}
