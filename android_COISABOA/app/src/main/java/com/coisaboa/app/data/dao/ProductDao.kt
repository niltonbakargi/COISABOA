package com.coisaboa.app.data.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.coisaboa.app.data.entity.ProductEntity

/**
 * 🧩 ProductDao
 * Interface de acesso ao banco de dados (Room) para a tabela de produtos.
 * Permite inserir, buscar, atualizar quantidades e excluir registros.
 */
@Dao
interface ProductDao {

    // ➕ Insere ou substitui um produto (Room substitui em caso de conflito de ID)
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(produto: ProductEntity): Long

    // 📋 Retorna todos os produtos, ordenados por nome
    @Query("SELECT * FROM product ORDER BY nome ASC")
    suspend fun getAll(): List<ProductEntity>

    // 🔍 Busca um produto pelo ID
    @Query("SELECT * FROM product WHERE id = :id LIMIT 1")
    suspend fun getById(id: Long): ProductEntity?

    // 🔍 Busca um produto pelo nome (não diferencia maiúsculas/minúsculas)
    @Query("SELECT * FROM product WHERE LOWER(nome) = LOWER(:nome) LIMIT 1")
    suspend fun getByName(nome: String): ProductEntity?

    // 🔺 Atualiza a quantidade de um produto diretamente
    @Query("UPDATE product SET quantidade = :novaQtd WHERE id = :id")
    suspend fun updateQuantidade(id: Long, novaQtd: Int)

    // 🔺 Aumenta o estoque de um produto existente
    @Query("UPDATE product SET quantidade = quantidade + :quantity WHERE id = :productId")
    suspend fun increaseStock(productId: Long, quantity: Int)

    // 🔻 Diminui o estoque (somente se houver quantidade suficiente)
    @Query("UPDATE product SET quantidade = quantidade - :quantity WHERE id = :productId AND quantidade >= :quantity")
    suspend fun decreaseStock(productId: Long, quantity: Int)

    // ❌ Exclui definitivamente um produto
    @Delete
    suspend fun delete(produto: ProductEntity)
}
