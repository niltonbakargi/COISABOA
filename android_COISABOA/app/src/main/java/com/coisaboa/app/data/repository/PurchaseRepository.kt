package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.PurchaseDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

/**
 * 🔹 PurchaseRepository
 * Gerencia as compras e sincroniza o estoque de produtos.
 */
class PurchaseRepository(
    private val dao: PurchaseDao,
    private val productRepo: ProductRepository
) {
    // 🔐 Protege operações simultâneas
    private val mutex = Mutex()

    /**
     * ➕ Registra uma nova compra e atualiza o estoque.
     * Usa Mutex para evitar corrida de dados.
     */
    suspend fun insertCompra(purchase: PurchaseEntity): Long = mutex.withLock {
        val nomeProduto = purchase.produtoNome.trim() // ✅ corrigido o campo

        // 1️⃣ Verifica se o produto existe
        val produtoExistente = productRepo.getByName(nomeProduto)

        if (produtoExistente != null) {
            // 2️⃣ Atualiza o estoque existente
            productRepo.increaseStock(produtoExistente.id, purchase.quantidade)
        } else {
            // 3️⃣ Cria novo produto automaticamente
            val novoProduto = ProductEntity(
                id = 0,
                nome = nomeProduto,
                quantidade = purchase.quantidade,
                valorEstimado = purchase.valorUnitario,
                valorRevenda = purchase.valorRevenda,
                caminhoImagem = null,
                observacoes = "🆕 Produto criado automaticamente pela compra"
            )
            productRepo.insertOrUpdate(novoProduto)
        }

        // 4️⃣ Registra a compra
        dao.insert(purchase)
    }

    /** 📋 Retorna todas as compras */
    suspend fun getAll(): List<PurchaseEntity> = dao.getAll()

    /** 🔍 Busca uma compra pelo ID */
    suspend fun getById(id: Long): PurchaseEntity? = dao.getById(id)

    /** 🔍 Busca compras associadas a um produto */
    suspend fun getByProductName(nome: String): List<PurchaseEntity> =
        dao.getByProductName(nome)

    /** ❌ Exclui uma compra */
    suspend fun delete(purchase: PurchaseEntity) = dao.delete(purchase)
}
