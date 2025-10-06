package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity

class ProductRepository(private val dao: ProductDao) {

    // ✅ "suspend" para poder chamar o DAO direto
    suspend fun getAll(): List<ProductEntity> = dao.getAll()

    suspend fun insert(product: ProductEntity) = dao.insert(product)

    suspend fun delete(product: ProductEntity) = dao.delete(product)
}
