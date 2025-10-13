package com.coisaboa.app.ui.vendas

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity

/**
 * 📋 Adapter para o diálogo de seleção de produto
 * Mostra a lista filtrável de produtos do estoque e retorna o item selecionado.
 */
class ProdutoDialogAdapter(
    private val onItemClick: (ProductEntity) -> Unit
) : ListAdapter<ProductEntity, ProdutoDialogAdapter.ViewHolder>(DiffCallback) {

    object DiffCallback : DiffUtil.ItemCallback<ProductEntity>() {
        override fun areItemsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean =
            oldItem == newItem
    }

    inner class ViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvNome: TextView = itemView.findViewById(R.id.tvNomeProduto)
        private val tvQtd: TextView = itemView.findViewById(R.id.tvQuantidadeProduto)
        private val tvValor: TextView = itemView.findViewById(R.id.tvValorProduto)

        fun bind(produto: ProductEntity) {
            tvNome.text = produto.nome
            tvQtd.text = "Qtd: ${produto.quantidade}"
            tvValor.text = "R$ %.2f".format(produto.valorRevenda ?: produto.valorEstimado ?: 0.0)

            itemView.setOnClickListener { onItemClick(produto) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_produto_dialog, parent, false)
        return ViewHolder(view)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }
}
