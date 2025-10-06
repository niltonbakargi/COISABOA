package com.coisaboa.app.ui.produtos

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity

/**
 * Adapter para exibir a lista de produtos no RecyclerView.
 */
class ProductAdapter(
    private val produtos: MutableList<ProductEntity>,
    private val onItemClick: (ProductEntity) -> Unit
) : RecyclerView.Adapter<ProductAdapter.ProductViewHolder>() {

    inner class ProductViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val tvNome: TextView = itemView.findViewById(R.id.tvNome)
        val tvPreco: TextView = itemView.findViewById(R.id.tvPreco)
        val tvQuantidade: TextView = itemView.findViewById(R.id.tvQuantidade)

        fun bind(produto: ProductEntity) {
            tvNome.text = produto.nome
            tvPreco.text = "R$ %.2f".format(produto.preco)
            tvQuantidade.text = "Qtd: ${produto.quantidade}"

            itemView.setOnClickListener {
                onItemClick(produto)
            }
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

    fun updateList(newList: List<ProductEntity>) {
        produtos.clear()
        produtos.addAll(newList)
        notifyDataSetChanged()
    }
}
