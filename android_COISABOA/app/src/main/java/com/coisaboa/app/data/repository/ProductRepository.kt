package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

/**
 * 🧩 ProductRepository
 * Camada intermediária entre o DAO (Room) e a lógica de negócio.
 * Responsável por isolar o acesso ao banco de dados.
 */
class ProductRepository(
    private val dao: ProductDao
) {

    // 📋 Retorna todos os produtos cadastrados
    suspend fun getAll(): List<ProductEntity> = withContext(Dispatchers.IO) {
        dao.getAll()
    }

    // 🔍 Busca um produto pelo ID
    suspend fun getById(id: Long): ProductEntity? = withContext(Dispatchers.IO) {
        dao.getById(id)
    }

    // 🔍 Busca um produto pelo nome
    suspend fun getByName(nome: String): ProductEntity? = withContext(Dispatchers.IO) {
        dao.getByName(nome)
    }

    // ➕ Insere ou atualiza um produto (Room lida com conflito)
    suspend fun insertOrUpdate(produto: ProductEntity): Unit = withContext(Dispatchers.IO) {
        dao.insert(produto)
    }

    // 🔺 Aumenta o estoque de um produto existente
    suspend fun increaseStock(productId: Long, quantity: Int): Unit = withContext(Dispatchers.IO) {
        val produto = dao.getById(productId)
        if (produto != null) {
            val novaQtd = (produto.quantidade ?: 0) + quantity
            dao.updateQuantidade(productId, novaQtd)
        } else {
            throw IllegalArgumentException("Produto não encontrado para aumentar estoque.")
        }
    }

    // 🔻 Diminui o estoque (com verificação)
    suspend fun decreaseStock(productId: Long, quantity: Int): Unit = withContext(Dispatchers.IO) {
        val produto = dao.getById(productId)
        if (produto != null) {
            val atual = produto.quantidade ?: 0
            if (atual >= quantity) {
                dao.updateQuantidade(productId, atual - quantity)
            } else {
                throw IllegalStateException("Estoque insuficiente para remoção.")
            }
        } else {
            throw IllegalArgumentException("Produto não encontrado para diminuir estoque.")
        }
    }

    // ❌ Remove um produto do banco
    suspend fun delete(produto: ProductEntity): Unit = withContext(Dispatchers.IO) {
        dao.delete(produto)
    }
}
