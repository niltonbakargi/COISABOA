package com.coisaboa.app.data.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.coisaboa.app.data.entity.PurchaseEntity

/**
 * DAO responsável pelo acesso à tabela de compras (purchases).
 */
@Dao
interface PurchaseDao {

    // Insere uma nova compra e retorna o ID gerado
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(purchase: PurchaseEntity): Long

    // Retorna todas as compras registradas
    @Query("SELECT * FROM purchases ORDER BY dataCompra DESC")
    suspend fun getAll(): List<PurchaseEntity>

    // Conta o número total de compras
    @Query("SELECT COUNT(*) FROM purchases")
    suspend fun count(): Int

    // Limpa todas as compras (uso interno em caso de reset)
    @Query("DELETE FROM purchases")
    suspend fun clearAll()
}
