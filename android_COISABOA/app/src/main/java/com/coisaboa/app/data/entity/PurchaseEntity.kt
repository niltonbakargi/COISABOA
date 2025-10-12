package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import java.util.*

/**
 * Entidade que representa uma compra registrada no sistema.
 */
@Entity(tableName = "purchase")
data class PurchaseEntity(

    @PrimaryKey(autoGenerate = true)
    val id: Long = 0L,

    // 🔹 Nome do produto comprado
    val produtoNome: String,

    // 🔹 Quantidade adquirida
    val quantidade: Int,

    // 🔹 Valor de aquisição (por unidade)
    val valorUnitario: Double,

    // 🔹 Valor total da compra (quantidade × valor unitário)
    val valorTotal: Double, // ✅ agora existe

    // 🔹 Valor sugerido de revenda
    val valorRevenda: Double? = null,

    // 🔹 Forma de pagamento (PIX, dinheiro, etc.)
    val formaPagamento: String? = null,

    // 🔹 Caminho da imagem do produto (opcional)
    val caminhoImagemProduto: String? = null,

    // 🔹 Caminho da imagem da nota fiscal / vendedor (opcional)
    val caminhoImagemNota: String? = null,

    // 🔹 Data em que a compra foi registrada
    val dataCompra: Date = Date()
)
