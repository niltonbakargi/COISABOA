package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "sales")
data class SaleEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val productId: Long,
    val quantidade: Int,
    val valorVendido: Double,
    val data: Long = System.currentTimeMillis()
)