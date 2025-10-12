package com.coisaboa.app.data.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.coisaboa.app.data.entity.ProductEntity

/**
 * 🔹 ProductDao
 * Interface de acesso ao banco de dados local (Room) para produtos.
 * Permite inserir, buscar, atualizar e excluir produtos do estoque.
 */
@Dao
interface ProductDao {

    // ➕ Inserir ou atualizar produto (substitui se já existir com mesmo ID)
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(product: ProductEntity): Long

    // 📋 Buscar todos os produtos cadastrados
    @Query("SELECT * FROM product ORDER BY nome ASC")
    suspend fun getAll(): List<ProductEntity>

    // 🔍 Buscar produto pelo ID
    @Query("SELECT * FROM product WHERE id = :id LIMIT 1")
    suspend fun getById(id: Long): ProductEntity?

    // 🔍 Buscar produto pelo nome (primeira ocorrência)
    @Query("SELECT * FROM product WHERE nome = :nome LIMIT 1")
    suspend fun getByName(nome: String): ProductEntity?

    // 🔺 Aumentar a quantidade em estoque
    @Query("UPDATE product SET quantidade = quantidade + :quantity WHERE id = :productId")
    suspend fun increaseStock(productId: Long, quantity: Int)

    // 🔻 Diminuir a quantidade em estoque (somente se houver o suficiente)
    @Query("UPDATE product SET quantidade = quantidade - :quantity WHERE id = :productId AND quantidade >= :quantity")
    suspend fun decreaseStock(productId: Long, quantity: Int)

    // ❌ Excluir produto definitivamente
    @Delete
    suspend fun delete(product: ProductEntity)
}
