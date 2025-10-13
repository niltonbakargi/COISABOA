package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * 🧱 ProductEntity
 * Representa um produto armazenado no banco de dados local (Room).
 *
 * Utilizada pelas telas:
 *  - 📦 Gerenciar Estoque
 *  - 🛒 Compras (para criar/atualizar produtos)
 *  - 💰 Vendas (para selecionar e registrar saída)
 *
 * Cada produto possui valores de custo, revenda e imagem local,
 * garantindo que o sistema funcione 100% offline.
 */
@Entity(tableName = "product")
data class ProductEntity(

    /** 🔹 Identificador único gerado automaticamente pelo Room */
    @PrimaryKey(autoGenerate = true)
    val id: Long = 0L,

    /** 🏷️ Nome do produto */
    val nome: String,

    /** 📦 Quantidade atual disponível em estoque */
    val quantidade: Int = 0,

    /** 💰 Valor médio de aquisição (custo estimado) */
    val valorEstimado: Double? = null,

    /** 💲 Valor sugerido de revenda */
    val valorRevenda: Double? = null,

    /** 🖼️ Caminho da imagem local (armazenamento interno do app) */
    val caminhoImagem: String? = null,

    /** 📝 Observações adicionais do produto */
    val observacoes: String? = null
)
