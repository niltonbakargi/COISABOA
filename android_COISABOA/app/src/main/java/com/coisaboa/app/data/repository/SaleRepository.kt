package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.SaleDao
import com.coisaboa.app.data.entity.SaleEntity
import java.util.Date

/**
 * 💼 SaleRepository
 * Repositório de vendas — intermedia entre o banco de dados e a camada de interface.
 */
class SaleRepository(
    private val dao: SaleDao,
    private val productRepo: ProductRepository
) {

    /**
     * 💾 Insere uma nova venda e atualiza o estoque do produto correspondente.
     */
    suspend fun insert(venda: SaleEntity) {
        dao.insert(venda)

        // Reduz o estoque do produto vendido, se existir
        val produto = productRepo.getByName(venda.produtoNome)
        if (produto != null) {
            val novaQtd = (produto.quantidade ?: 0) - venda.quantidade
            if (novaQtd >= 0) {
                productRepo.decreaseStock(produto.id, venda.quantidade)
            }
        }
    }

    /**
     * 📋 Retorna todas as vendas registradas.
     */
    suspend fun getAll(): List<SaleEntity> = dao.getAll()

    /**
     * 💰 Retorna o valor total de todas as vendas.
     */
    suspend fun getTotalSalesValue(): Double = dao.getTotalSalesValue() ?: 0.0

    /**
     * 📆 Retorna as vendas realizadas entre duas datas específicas.
     * Usa a propriedade `dataVenda` da entidade `SaleEntity`.
     */
    suspend fun getSalesBetween(inicio: Date, fim: Date): List<SaleEntity> {
        val todas = dao.getAll()
        return todas.filter { venda ->
            val data = venda.dataVenda ?: return@filter false
            !data.before(inicio) && !data.after(fim)
        }
    }
}
