package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "products")
data class ProductEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val nome: String,
    val quantidade: Int,
    val valor: Double,
    val imagemPath: String? = null
)