package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.SaleDao
import com.coisaboa.app.data.entity.SaleEntity
import java.util.Date

/**
 * 💼 SaleRepository (versão padronizada)
 * Gerencia as operações de venda e sincroniza o estoque.
 */
class SaleRepository(
    private val dao: SaleDao,
    private val productRepo: ProductRepository
) {

    /**
     * 💾 Insere uma nova venda e atualiza o estoque.
     */
    suspend fun insert(venda: SaleEntity) {
        dao.insert(venda)

        // Reduz o estoque do produto vendido
        val produto = productRepo.getByName(venda.produtoNome)
        if (produto != null) {
            val novaQtd = (produto.quantidade ?: 0) - (venda.quantidade ?: 0)
            if (novaQtd >= 0) {
                productRepo.decreaseStock(produto.id, venda.quantidade ?: 0)
            }
        }
    }

    /** 📋 Retorna todas as vendas */
    suspend fun getAll(): List<SaleEntity> = dao.getAll()

    /** 💰 Retorna o valor total de vendas */
    suspend fun getTotalSalesValue(): Double = dao.getTotalSalesValue() ?: 0.0

    /** 📆 Retorna vendas entre duas datas */
    suspend fun getSalesBetween(inicio: Date, fim: Date): List<SaleEntity> {
        val todas = dao.getAll()
        return todas.filter { venda ->
            val data = venda.dataVenda ?: return@filter false
            !data.before(inicio) && !data.after(fim)
        }
    }
}
