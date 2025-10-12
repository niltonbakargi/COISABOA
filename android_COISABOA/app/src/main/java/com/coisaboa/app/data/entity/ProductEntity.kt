package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * Entidade que representa um produto armazenado no estoque local (Room Database).
 * Utilizada nas telas de Compras, Gerenciar Estoque e Vendas.
 */
@Entity(tableName = "product")
data class ProductEntity(

    // 🔹 Identificador único (gerado automaticamente pelo Room)
    @PrimaryKey(autoGenerate = true)
    val id: Long = 0L,  // ✅ agora é Long, compatível com o DAO e Repository

    // 🔹 Nome do produto
    val nome: String,

    // 🔹 Quantidade atual disponível em estoque
    val quantidade: Int = 0,

    // 🔹 Valor médio de aquisição (custo estimado)
    val valorEstimado: Double? = null,

    // 🔹 Valor sugerido de revenda
    val valorRevenda: Double? = null,

    // 🔹 Caminho da imagem local (URI ou path no armazenamento interno)
    val caminhoImagem: String? = null,

    // 🔹 Observações gerais
    val observacoes: String? = null
)
