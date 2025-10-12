package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import java.util.Date

/**
 * 🧾 SaleEntity
 * Representa uma venda registrada no sistema COISABOA.
 */
@Entity(tableName = "sale")
data class SaleEntity(
    @PrimaryKey(autoGenerate = true)
    val id: Long = 0L,

    val produtoNome: String,
    val quantidade: Int,
    val valorUnitario: Double,
    val valorTotal: Double,
    val formaPagamento: String,
    val dataVenda: Date = Date()
)
