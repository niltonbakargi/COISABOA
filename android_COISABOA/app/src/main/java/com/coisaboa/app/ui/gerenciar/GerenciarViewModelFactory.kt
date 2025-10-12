package com.coisaboa.app.ui.gerenciar

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import com.coisaboa.app.data.repository.ProductRepository

/**
 * 🏗️ GerenciarViewModelFactory
 * Fábrica responsável por criar instâncias do [GerenciarViewModel],
 * injetando corretamente o [ProductRepository].
 */
class GerenciarViewModelFactory(
    private val productRepository: ProductRepository
) : ViewModelProvider.Factory {

    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(GerenciarViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return GerenciarViewModel(productRepository) as T
        }
        throw IllegalArgumentException("Classe ViewModel desconhecida: ${modelClass.name}")
    }
}
