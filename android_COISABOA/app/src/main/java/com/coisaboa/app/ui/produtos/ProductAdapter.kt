package com.coisaboa.app.ui.produtos

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity

class ProductAdapter : RecyclerView.Adapter<ProductAdapter.VH>() {

    private val items = mutableListOf<ProductEntity>()

    fun submitList(list: List<ProductEntity>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    class VH(v: View): RecyclerView.ViewHolder(v) {
        val tvName: TextView = v.findViewById(R.id.tvName)
        val tvInfo: TextView = v.findViewById(R.id.tvInfo)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VH {
        val v = LayoutInflater.from(parent.context).inflate(R.layout.item_product, parent, false)
        return VH(v)
    }

    override fun onBindViewHolder(holder: VH, position: Int) {
        val it = items[position]
        holder.tvName.text = it.nome
        holder.tvInfo.text = "Qtd: ${it.quantidade}  •  R$ ${"%.2f".format(it.valor)}"
    }

    override fun getItemCount() = items.size
}