package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity

/**
 * Camada de repositório responsável por intermediar o acesso
 * entre o banco de dados (Room) e a lógica de negócio.
 */
class ProductRepository(
    private val dao: ProductDao
) {
    // 📋 Retorna todos os produtos
    suspend fun getAll(): List<ProductEntity> = dao.getAll()

    // 🔍 Busca um produto pelo ID
    suspend fun getById(id: Long): ProductEntity? = dao.getById(id)

    // 🔍 Busca produto pelo nome
    suspend fun getByName(nome: String): ProductEntity? = dao.getByName(nome)

    // ➕ Insere ou atualiza um produto
    suspend fun insertOrUpdate(p: ProductEntity) = dao.insert(p)

    // 🔺 Aumenta o estoque de um produto
    suspend fun increaseStock(productId: Long, quantity: Int) =
        dao.increaseStock(productId, quantity)

    // 🔻 Diminui o estoque (se houver quantidade suficiente)
    suspend fun decreaseStock(productId: Long, quantity: Int) =
        dao.decreaseStock(productId, quantity)

    // ❌ Remove um produto
    suspend fun delete(p: ProductEntity) = dao.delete(p)
}
