package com.coisaboa.app.ui.relatorios.adapters

import android.graphics.BitmapFactory
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.databinding.ItemEstoqueListaBinding
import java.io.File

class EstoqueListaAdapter : ListAdapter<ProductEntity, EstoqueListaAdapter.EstoqueViewHolder>(DiffCallback) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): EstoqueViewHolder {
        val binding = ItemEstoqueListaBinding.inflate(
            LayoutInflater.from(parent.context), 
            parent, 
            false
        )
        return EstoqueViewHolder(binding)
    }

    override fun onBindViewHolder(holder: EstoqueViewHolder, position: Int) {
        val product = getItem(position)
        holder.bind(product)
    }

    class EstoqueViewHolder(private val binding: ItemEstoqueListaBinding) : 
        RecyclerView.ViewHolder(binding.root) {

        fun bind(product: ProductEntity) {
            binding.tvNomeProduto.text = product.nome
            binding.tvQuantidade.text = "Quantidade: ${product.quantidade}"
            
            product.valorEstimado?.let {
                binding.tvValorCusto.text = "Custo: R$ ${"%.2f".format(it)}"
            } ?: run {
                binding.tvValorCusto.text = "Custo: Não informado"
            }
            
            product.valorRevenda?.let {
                binding.tvValorRevenda.text = "Revenda: R$ ${"%.2f".format(it)}"
            } ?: run {
                binding.tvValorRevenda.text = "Revenda: Não informado"
            }

            product.caminhoImagem?.let { caminho ->
                val file = File(caminho)
                if (file.exists()) {
                    val bitmap = BitmapFactory.decodeFile(file.absolutePath)
                    binding.ivProduto.setImageBitmap(bitmap)
                } else {
                    binding.ivProduto.setImageResource(R.drawable.ic_placeholder)
                }
            } ?: run {
                binding.ivProduto.setImageResource(R.drawable.ic_placeholder)
            }

            product.observacoes?.let { obs ->
                if (obs.isNotBlank()) {
                    binding.tvObservacoes.text = "Obs: $obs"
                    binding.tvObservacoes.visibility = View.VISIBLE
                } else {
                    binding.tvObservacoes.visibility = View.GONE
                }
            } ?: run {
                binding.tvObservacoes.visibility = View.GONE
            }
        }
    }

    companion object DiffCallback : DiffUtil.ItemCallback<ProductEntity>() {
        override fun areItemsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean {
            return oldItem.id == newItem.id
        }

        override fun areContentsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean {
            return oldItem == newItem
        }
    }
}