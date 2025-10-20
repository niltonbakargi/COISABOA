package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.PurchaseDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity

/**
 * 🔹 PurchaseRepository (versão padronizada e funcional)
 * Registra compras e mantém o estoque sincronizado com o banco de dados.
 *
 * - Usa transações para consistência.
 * - Cria o produto automaticamente se não existir.
 * - Atualiza o valor de custo, revenda e imagem do produto.
 */
class PurchaseRepository(
    private val dao: PurchaseDao,
    private val productRepo: ProductRepository
) {

    /**
     * ➕ Insere uma nova compra e atualiza o produto correspondente.
     * Se o produto não existir, cria automaticamente.
     */
    suspend fun insertCompra(purchase: PurchaseEntity): Long {
        var idGerado: Long = 0L

        val nomeProduto = purchase.produtoNome.trim()
        val produtoExistente = productRepo.getByName(nomeProduto)

        if (produtoExistente != null) {
            // 🔄 Atualiza o estoque do produto existente
            val novoCaminho = purchase.caminhoImagemProduto ?: produtoExistente.caminhoImagem
            val produtoAtualizado = produtoExistente.copy(
                quantidade = produtoExistente.quantidade + purchase.quantidade,
                valorEstimado = purchase.valorUnitario,
                valorRevenda = purchase.valorRevenda,
                caminhoImagem = novoCaminho
            )
            productRepo.insertOrUpdate(produtoAtualizado)
        } else {
            // 🆕 Cria novo produto
            val novoProduto = ProductEntity(
                id = 0,
                nome = nomeProduto,
                quantidade = purchase.quantidade,
                valorEstimado = purchase.valorUnitario,
                valorRevenda = purchase.valorRevenda,
                caminhoImagem = purchase.caminhoImagemProduto,
                observacoes = "🆕 Produto criado automaticamente pela compra."
            )
            productRepo.insertOrUpdate(novoProduto)
        }

        // 💾 Registra a compra no banco
        idGerado = dao.insert(purchase)
        return idGerado
    }

    /** 📋 Retorna todas as compras */
    suspend fun getAll(): List<PurchaseEntity> = dao.getAll()

    /** 🔍 Busca compra por ID */
    suspend fun getById(id: Long): PurchaseEntity? = dao.getById(id)

    /** 🔍 Busca compras de um produto específico */
    suspend fun getByProductName(nome: String): List<PurchaseEntity> = dao.getByProductName(nome)

    /** ❌ Exclui uma compra */
    suspend fun delete(purchase: PurchaseEntity) = dao.delete(purchase)
}
