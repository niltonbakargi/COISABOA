package com.coisaboa.app.data.dao

import androidx.room.*
import com.coisaboa.app.data.entity.ProductEntity

/**
 * 🧩 ProductDao
 * DAO de acesso à tabela product (Room)
 * - Controle de estoque seguro (não permite valores negativos)
 * - Atualizações atômicas via SQL
 */
@Dao
interface ProductDao {

    /** ➕ Insere ou atualiza um produto */
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(produto: ProductEntity): Long

    /** 📋 Retorna todos os produtos */
    @Query("SELECT * FROM product ORDER BY nome ASC")
    suspend fun getAll(): List<ProductEntity>

    /** 🔍 Busca por ID */
    @Query("SELECT * FROM product WHERE id = :id LIMIT 1")
    suspend fun getById(id: Long): ProductEntity?

    /** 🔍 Busca por nome (case-insensitive) */
    @Query("SELECT * FROM product WHERE LOWER(nome) = LOWER(:nome) LIMIT 1")
    suspend fun getByName(nome: String): ProductEntity?

    /** 🔺 Atualiza a quantidade explicitamente */
    @Query("UPDATE product SET quantidade = :novaQtd WHERE id = :id")
    suspend fun updateQuantidade(id: Long, novaQtd: Int)

    /** ⚙️ Aumenta o estoque (atômico) */
    @Query("UPDATE product SET quantidade = quantidade + :delta WHERE id = :id")
    suspend fun adjustStock(id: Long, delta: Int): Int

    /** ⚙️ Diminui o estoque apenas se houver saldo suficiente */
    @Query("""
        UPDATE product 
        SET quantidade = quantidade - :q 
        WHERE id = :id AND quantidade >= :q
    """)
    suspend fun tryDecreaseStock(id: Long, q: Int): Int

    /** ❌ Exclui o produto */
    @Delete
    suspend fun delete(produto: ProductEntity)
}
