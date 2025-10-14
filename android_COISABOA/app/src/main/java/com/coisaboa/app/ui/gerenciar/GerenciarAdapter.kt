package com.coisaboa.app.ui.gerenciar

import android.graphics.BitmapFactory
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity
import java.io.File

/**
 * 🔹 GerenciarAdapter
 * Adaptador responsável por exibir os produtos do estoque.
 *
 * Funcionalidades:
 *  - Mostra miniatura do produto, nome, quantidade e preços.
 *  - Reage a cliques no item ou no ícone de edição.
 *  - Compatível com o modo "Excluir" da tela de Gerenciamento.
 */
class GerenciarAdapter(
    private val onProdutoSelecionado: (ProductEntity) -> Unit
) : ListAdapter<ProductEntity, GerenciarAdapter.ViewHolder>(DiffCallback()) {

    /**
     * 🎨 ViewHolder — representa cada item da lista (um produto).
     */
    inner class ViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val imgThumb: ImageView = itemView.findViewById(R.id.imgThumb)
        private val tvNome: TextView = itemView.findViewById(R.id.tvNome)
        private val tvQtd: TextView = itemView.findViewById(R.id.tvQtd)
        private val tvValores: TextView = itemView.findViewById(R.id.tvValores)
        private val btnEditar: ImageView = itemView.findViewById(R.id.btnEditar)

        /**
         * 🧩 Vincula os dados do produto à interface do item.
         */
        fun bind(produto: ProductEntity) {
            tvNome.text = produto.nome
            tvQtd.text = "Qtd: ${produto.quantidade}"
            tvValores.text = "Custo: R$ %.2f | Revenda: R$ %.2f".format(
                produto.valorEstimado ?: 0.0,
                produto.valorRevenda ?: 0.0
            )

            // 🖼️ Carrega imagem do produto, se existir
            val caminho = produto.caminhoImagem
            if (!caminho.isNullOrBlank()) {
                val arquivo = File(caminho)
                if (arquivo.exists()) {
                    val bitmap = BitmapFactory.decodeFile(arquivo.absolutePath)
                    imgThumb.setImageBitmap(bitmap)
                } else {
                    imgThumb.setImageResource(R.drawable.ic_placeholder)
                }
            } else {
                imgThumb.setImageResource(R.drawable.ic_placeholder)
            }

            // 🎯 Define os eventos de clique (item e botão de edição)
            itemView.setOnClickListener { onProdutoSelecionado(produto) }
            btnEditar.setOnClickListener { onProdutoSelecionado(produto) }
        }
    }

    /**
     * 🏗️ Cria a view de cada item da lista.
     */
    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val inflater = LayoutInflater.from(parent.context)
        val view = inflater.inflate(R.layout.item_produto_estoque, parent, false)
        return ViewHolder(view)
    }

    /**
     * 🔄 Vincula os dados de um produto ao ViewHolder.
     */
    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    /**
     * ⚡ DiffUtil — otimiza atualizações entre listas antigas e novas.
     */
    class DiffCallback : DiffUtil.ItemCallback<ProductEntity>() {
        override fun areItemsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean =
            oldItem == newItem
    }
}
