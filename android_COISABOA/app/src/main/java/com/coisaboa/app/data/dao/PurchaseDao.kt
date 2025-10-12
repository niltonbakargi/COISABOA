package com.coisaboa.app.data.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.coisaboa.app.data.entity.PurchaseEntity

/**
 * 🔹 PurchaseDao
 * Interface de acesso ao banco de dados local (Room) para operações de compras.
 */
@Dao
interface PurchaseDao {

    // ➕ Inserir ou atualizar uma compra
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(purchase: PurchaseEntity): Long

    // 📋 Buscar todas as compras, mais recentes primeiro
    @Query("SELECT * FROM purchase ORDER BY dataCompra DESC")
    suspend fun getAll(): List<PurchaseEntity>

    // 🔍 Buscar compra pelo ID
    @Query("SELECT * FROM purchase WHERE id = :id LIMIT 1")
    suspend fun getById(id: Long): PurchaseEntity?

    // 🔍 Buscar compras por nome do produto
    @Query("SELECT * FROM purchase WHERE produtoNome LIKE '%' || :nome || '%' ORDER BY dataCompra DESC")
    suspend fun getByProductName(nome: String): List<PurchaseEntity>

    // ❌ Excluir uma compra específica
    @Delete
    suspend fun delete(purchase: PurchaseEntity)
}
    