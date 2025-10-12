package com.coisaboa.app.data.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.Query
import com.coisaboa.app.data.entity.SaleEntity

/**
 * 🧾 SaleDao
 * Camada de acesso ao banco de dados (Room) para a tabela de vendas.
 * 
 * Responsável por inserir, listar e calcular totais das vendas.
 */
@Dao
interface SaleDao {

    /**
     * ➕ Insere uma nova venda no banco de dados.
     * Caso o ID já exista, o Room cria um novo registro automaticamente.
     */
    @Insert
    suspend fun insert(sale: SaleEntity): Long

    /**
     * 📋 Retorna todas as vendas registradas,
     * ordenadas da mais recente para a mais antiga.
     */
    @Query("SELECT * FROM sale ORDER BY dataVenda DESC")
    suspend fun getAll(): List<SaleEntity>

    /**
     * 💰 Retorna a soma total de todas as vendas registradas.
     * Retorna `null` se não houver vendas no banco.
     */
    @Query("SELECT SUM(valorTotal) FROM sale")
    suspend fun getTotalSalesValue(): Double?
}
