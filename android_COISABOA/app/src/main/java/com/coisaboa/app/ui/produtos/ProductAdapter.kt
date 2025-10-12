package com.coisaboa.app.ui.produtos

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity

/**
 * 🔹 ProductAdapter
 * Adapter responsável por exibir a lista de produtos no RecyclerView.
 * Mostra nome, preço de revenda (ou estimado) e quantidade atual.
 */
class ProductAdapter(
    private val produtos: MutableList<ProductEntity>,
    private val onItemClick: (ProductEntity) -> Unit
) : RecyclerView.Adapter<ProductAdapter.ProductViewHolder>() {

    inner class ProductViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvNome: TextView = itemView.findViewById(R.id.tvNome)
        private val tvPreco: TextView = itemView.findViewById(R.id.tvPreco)
        private val tvQuantidade: TextView = itemView.findViewById(R.id.tvQuantidade)

        fun bind(produto: ProductEntity) {
            // 🏷️ Nome
            tvNome.text = produto.nome

            // 💰 Preço — exibe valorRevenda se existir, senão valorEstimado
            val precoExibir = produto.valorRevenda ?: produto.valorEstimado ?: 0.0
            tvPreco.text = "R$ %.2f".format(precoExibir)

            // 📦 Quantidade
            tvQuantidade.text = "Qtd: ${produto.quantidade}"

            // 🔘 Clique no item
            itemView.setOnClickListener { onItemClick(produto) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_product, parent, false)
        return ProductViewHolder(view)
    }

    override fun onBindViewHolder(holder: ProductViewHolder, position: Int) {
        holder.bind(produtos[position])
    }

    override fun getItemCount(): Int = produtos.size

    /**
     * 🔄 Atualiza a lista de produtos exibida.
     */
    fun updateList(newList: List<ProductEntity>) {
        produtos.clear()
        produtos.addAll(newList)
        notifyDataSetChanged()
    }
}
