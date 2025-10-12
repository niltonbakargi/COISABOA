package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import java.util.Date

/**
 * 🧾 SaleEntity
 * Representa uma venda registrada no sistema COISABOA.
 * Inclui dados de produto, valores, forma de pagamento e observações.
 */
@Entity(tableName = "sale")
data class SaleEntity(

    // 🔑 Identificador único da venda
    @PrimaryKey(autoGenerate = true)
    val id: Long = 0L,

    // 📦 Nome do produto vendido
    val produtoNome: String,

    // 🔢 Quantidade vendida
    val quantidade: Int,

    // 💲 Valor unitário de venda
    val valorUnitario: Double,

    // 💰 Valor total calculado (quantidade × valorUnitário)
    val valorTotal: Double,

    // 💳 Forma de pagamento (PIX, dinheiro, cartão etc.)
    val formaPagamento: String,

    // 💵 Valor estimado (custo do produto no estoque)
    val valorEstimado: Double? = null,

    // 📝 Observações adicionais sobre a venda
    val observacoes: String? = null,

    // 📅 Data e hora do registro da venda
    val dataVenda: Date = Date()
)
