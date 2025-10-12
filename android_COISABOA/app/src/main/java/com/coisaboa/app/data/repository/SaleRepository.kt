package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.SaleDao
import com.coisaboa.app.data.entity.SaleEntity

/**
 * Repositório de vendas — intermedia entre o banco de dados e a camada de interface.
 */
class SaleRepository(
    private val dao: SaleDao,
    private val productRepo: ProductRepository
) {

    // Insere uma nova venda e atualiza o estoque
    suspend fun insert(venda: SaleEntity) {
        dao.insert(venda)

        // Reduz estoque do produto vendido
        val produto = productRepo.getByName(venda.produtoNome)
        if (produto != null) {
            val novaQtd = (produto.quantidade ?: 0) - venda.quantidade
            if (novaQtd >= 0) {
                productRepo.decreaseStock(produto.id, venda.quantidade)
            }
        }
    }

    suspend fun getAll(): List<SaleEntity> = dao.getAll()

    suspend fun getTotalSalesValue(): Double = dao.getTotalSalesValue() ?: 0.0
}
