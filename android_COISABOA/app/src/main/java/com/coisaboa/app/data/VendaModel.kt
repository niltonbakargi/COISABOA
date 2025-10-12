package com.coisaboa.app.data

data class VendaModel(
    val produto: String,
    val quantidade: Int,
    val valorUnitario: Double,
    val pagamento: String,
    val observacoes: String?,
    val imagemUri: String?,
    val data: Long = System.currentTimeMillis()
)
