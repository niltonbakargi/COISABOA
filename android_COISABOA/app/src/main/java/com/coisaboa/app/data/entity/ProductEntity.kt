package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * Entidade que representa um produto no estoque.
 */
@Entity(tableName = "products")
data class ProductEntity(
    @PrimaryKey(autoGenerate = true)
    val id: Long = 0,

    // Nome do produto (usado para identificação)
    val nome: String,

    // Valor unitário de compra
    val valorUnitario: Double,

    // Valor previsto para revenda
    val valorRevenda: Double,

    // Quantidade atual em estoque
    val stock: Int = 0,

    // Caminho opcional da imagem do produto
    val imagem: String? = null
)
