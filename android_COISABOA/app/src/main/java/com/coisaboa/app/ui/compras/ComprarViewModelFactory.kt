package com.coisaboa.app.ui.compras

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import com.coisaboa.app.data.repository.PurchaseRepository

/**
 * Fábrica que cria instâncias do ComprarViewModel
 * injetando o repositório corretamente.
 */
class ComprarViewModelFactory(
    private val repository: PurchaseRepository
) : ViewModelProvider.Factory {

    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(ComprarViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return ComprarViewModel(repository) as T
        }
        throw IllegalArgumentException("Classe ViewModel desconhecida")
    }
}
