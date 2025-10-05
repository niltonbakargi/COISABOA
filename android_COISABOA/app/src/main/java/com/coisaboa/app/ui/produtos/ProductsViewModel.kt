package com.coisaboa.app.ui.produtos

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.asLiveData
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.data.entity.ProductEntity
import kotlinx.coroutines.launch

class ProductsViewModel(app: Application): AndroidViewModel(app) {
    private val dao = DatabaseProvider.get(app).productDao()
    private val repo = ProductRepository(dao)

    val products = repo.getAll().asLiveData()

    fun add(nome: String, quantidade: Int, valor: Double, imagemPath: String? = null) = viewModelScope.launch {
        repo.add(nome, quantidade, valor, imagemPath)
    }

    fun update(entity: ProductEntity) = viewModelScope.launch {
        repo.update(entity)
    }

    fun delete(entity: ProductEntity) = viewModelScope.launch {
        repo.delete(entity)
    }
}