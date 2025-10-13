package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.PurchaseDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

/**
 * 🔹 PurchaseRepository
 * Responsável por registrar compras e manter o estoque sincronizado com os produtos.
 *
 * - Atualiza automaticamente a quantidade e os valores de custo/revenda.
 * - Salva o caminho da imagem do produto quando disponível.
 * - Opera com Mutex para evitar concorrência em operações simultâneas.
 */
class PurchaseRepository(
    private val dao: PurchaseDao,
    private val productRepo: ProductRepository
) {
    // 🔐 Garante exclusividade durante operações críticas
    private val mutex = Mutex()

    /**
     * ➕ Registra uma nova compra e atualiza o produto correspondente.
     * Caso o produto ainda não exista, cria um novo automaticamente.
     */
    suspend fun insertCompra(purchase: PurchaseEntity): Long = mutex.withLock {
        val nomeProduto = purchase.produtoNome.trim()
        val produtoExistente = productRepo.getByName(nomeProduto)

        if (produtoExistente != null) {
            // 🔄 Atualiza o estoque e metadados do produto existente
            val novoCaminho = purchase.caminhoImagemProduto ?: produtoExistente.caminhoImagem
            val produtoAtualizado = produtoExistente.copy(
                quantidade = produtoExistente.quantidade + purchase.quantidade,
                valorEstimado = purchase.valorUnitario,
                valorRevenda = purchase.valorRevenda,
                caminhoImagem = novoCaminho
            )
            productRepo.insertOrUpdate(produtoAtualizado)
        } else {
            // 🆕 Cria um novo produto com os dados da compra
            val novoProduto = ProductEntity(
                id = 0,
                nome = nomeProduto,
                quantidade = purchase.quantidade,
                valorEstimado = purchase.valorUnitario,
                valorRevenda = purchase.valorRevenda,
                caminhoImagem = purchase.caminhoImagemProduto, // ✅ Imagem vinculada corretamente
                observacoes = "🆕 Produto criado automaticamente pela compra"
            )
            productRepo.insertOrUpdate(novoProduto)
        }

        // 💾 Registra a compra no banco
        dao.insert(purchase)
    }

    /** 📋 Retorna todas as compras registradas */
    suspend fun getAll(): List<PurchaseEntity> = dao.getAll()

    /** 🔍 Busca uma compra específica pelo ID */
    suspend fun getById(id: Long): PurchaseEntity? = dao.getById(id)

    /** 🔍 Busca todas as compras relacionadas a um determinado produto */
    suspend fun getByProductName(nome: String): List<PurchaseEntity> =
        dao.getByProductName(nome)

    /** ❌ Exclui uma compra do banco de dados */
    suspend fun delete(purchase: PurchaseEntity) = dao.delete(purchase)
}
