package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.dao.PurchaseDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity

class PurchaseRepository(
    private val purchaseDao: PurchaseDao,
    private val productDao: ProductDao
) {

    suspend fun registrarCompra(purchase: PurchaseEntity): Long {
        // Salva a compra
        val idCompra = purchaseDao.insert(purchase)

        // Verifica se o produto já existe
        val produtos = productDao.getAll()
        val existente = produtos.find { it.nome == purchase.produto }

        if (existente != null) {
            // Atualiza estoque
            productDao.increaseStock(existente.id, purchase.quantidade)
        } else {
            // Cria novo produto no estoque
            val novo = ProductEntity(
                id = 0,
                nome = purchase.produto,
                valorUnitario = purchase.valorUnitario,
                valorRevenda = purchase.valorRevenda,
                stock = purchase.quantidade,
                imagem = purchase.caminhoImagemProduto
            )
            productDao.insert(novo)
        }

        return idCompra
    }

    suspend fun listarCompras() = purchaseDao.getAll()
}
