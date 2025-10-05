package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "purchases")
data class PurchaseEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val productId: Long,
    val quantidade: Int,
    val valorCompra: Double,
    val data: Long = System.currentTimeMillis()
)