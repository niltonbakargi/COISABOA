package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

/**
 * 🧩 ProductRepository
 * Camada intermediária entre o DAO e a lógica de negócio.
 * Garante consistência e segurança no controle de estoque.
 */
class ProductRepository(
    private val dao: ProductDao
) {

    /** 📋 Retorna todos os produtos cadastrados */
    suspend fun getAll(): List<ProductEntity> = withContext(Dispatchers.IO) {
        dao.getAll()
    }

    /** 🔍 Busca produto por ID */
    suspend fun getById(id: Long): ProductEntity? = withContext(Dispatchers.IO) {
        dao.getById(id)
    }

    /** 🔍 Busca produto por nome */
    suspend fun getByName(nome: String): ProductEntity? = withContext(Dispatchers.IO) {
        dao.getByName(nome)
    }

    /** ➕ Insere ou atualiza produto */
    suspend fun insertOrUpdate(produto: ProductEntity): Long = withContext(Dispatchers.IO) {
        dao.insert(produto)
    }

    /** 🔺 Aumenta o estoque de forma segura */
    suspend fun increaseStock(productId: Long, quantity: Int) = withContext(Dispatchers.IO) {
        if (quantity <= 0) throw IllegalArgumentException("Quantidade inválida para aumento de estoque.")
        val rows = dao.adjustStock(productId, quantity)
        if (rows == 0) throw IllegalStateException("Produto não encontrado para aumento de estoque.")
    }

    /** 🔻 Diminui o estoque com verificação (não permite negativo) */
    suspend fun decreaseStock(productId: Long, quantity: Int) = withContext(Dispatchers.IO) {
        if (quantity <= 0) throw IllegalArgumentException("Quantidade inválida para baixa de estoque.")
        val rows = dao.tryDecreaseStock(productId, quantity)
        if (rows == 0) throw IllegalStateException("Estoque insuficiente ou produto inexistente.")
    }

    /** ❌ Exclui produto */
    suspend fun delete(produto: ProductEntity) = withContext(Dispatchers.IO) {
        dao.delete(produto)
    }
}

