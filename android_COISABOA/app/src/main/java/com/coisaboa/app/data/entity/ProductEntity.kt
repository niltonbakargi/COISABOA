package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "products")
data class ProductEntity(
    @PrimaryKey(autoGenerate = true) val id: Int = 0,
    val nome: String,
    val preco: Double,
    val quantidade: Int
)
