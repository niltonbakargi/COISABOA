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
 * Mostra nome, preço de revenda e quantidade atual em estoque.
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
            tvNome.text = produto.nome

            // Exibe o valor de revenda, ou valor unitário caso não tenha revenda
            val precoExibir = if (produto.valorRevenda > 0) produto.valorRevenda else produto.valorUnitario
            tvPreco.text = "R$ %.2f".format(precoExibir)

            // Exibe a quantidade atual (stock)
            tvQuantidade.text = "Qtd: ${produto.stock}"

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

    fun updateList(newList: List<ProductEntity>) {
        produtos.clear()
        produtos.addAll(newList)
        notifyDataSetChanged()
    }
}
