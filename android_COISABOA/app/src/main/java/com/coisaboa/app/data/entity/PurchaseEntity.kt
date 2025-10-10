package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import java.util.*

@Entity(tableName = "purchases")
data class PurchaseEntity(
    @PrimaryKey(autoGenerate = true)
    val id: Long = 0,
    val produto: String,
    val quantidade: Int,
    val valorUnitario: Double,
    val valorTotal: Double,
    val valorRevenda: Double,
    val formaPagamento: String,
    val caminhoImagemProduto: String?,
    val caminhoImagemNota: String?,
    val dataCompra: Date
)
