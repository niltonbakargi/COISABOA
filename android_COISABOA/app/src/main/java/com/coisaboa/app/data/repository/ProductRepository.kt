package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity

class ProductRepository(private val dao: ProductDao) {
    fun getAll() = dao.getAll()
    suspend fun add(nome: String, quantidade: Int, valor: Double, imagemPath: String? = null) =
        dao.insert(ProductEntity(nome = nome, quantidade = quantidade, valor = valor, imagemPath = imagemPath))
    suspend fun update(entity: ProductEntity) = dao.update(entity)
    suspend fun delete(entity: ProductEntity) = dao.delete(entity)
}