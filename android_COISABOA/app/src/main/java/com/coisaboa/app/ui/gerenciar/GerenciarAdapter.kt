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
 * Exibe a lista de produtos cadastrados no estoque,
 * mostrando miniatura, nome, quantidade e valores.
 * Permite selecionar um item para edição.
 */
class GerenciarAdapter(
    private val onEditar: (ProductEntity) -> Unit
) : ListAdapter<ProductEntity, GerenciarAdapter.ViewHolder>(GerenciarAdapter.DiffCallback()) {

    /**
     * 🎨 ViewHolder — representa um item da lista.
     * Mapeia o layout item_produto_estoque.xml.
     */
    inner class ViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        private val imgThumb: ImageView = view.findViewById(R.id.imgThumb)
        private val tvNome: TextView = view.findViewById(R.id.tvNome)
        private val tvQtd: TextView = view.findViewById(R.id.tvQtd)
        private val tvValores: TextView = view.findViewById(R.id.tvValores)
        private val btnEditar: ImageView = view.findViewById(R.id.btnEditar)

        /**
         * 🧩 Liga os dados do produto aos elementos visuais.
         */
        fun bind(produto: ProductEntity) {
            tvNome.text = produto.nome
            tvQtd.text = "Qtd: ${produto.quantidade}"
            tvValores.text = "Custo: R$ %.2f | Revenda: R$ %.2f".format(
                produto.valorEstimado ?: 0.0,
                produto.valorRevenda ?: 0.0
            )

            // 🖼️ Carrega imagem local (ou placeholder)
            val caminho = produto.caminhoImagem
            if (!caminho.isNullOrBlank()) {
                val file = File(caminho)
                if (file.exists()) {
                    val bmp = BitmapFactory.decodeFile(file.absolutePath)
                    imgThumb.setImageBitmap(bmp)
                } else {
                    imgThumb.setImageResource(R.drawable.ic_placeholder)
                }
            } else {
                imgThumb.setImageResource(R.drawable.ic_placeholder)
            }

            // 🎯 Clique para editar
            btnEditar.setOnClickListener { onEditar(produto) }
            itemView.setOnClickListener { onEditar(produto) }
        }
    }

    /**
     * 🏗️ Cria o layout de cada item (ViewHolder)
     */
    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        // ⚠️ Usar LayoutInflater.from(parent.context) diretamente
        // garante que o compilador escolha o overload correto.
        val inflater = LayoutInflater.from(parent.context)
        val view = inflater.inflate(R.layout.item_produto_estoque as Int, parent, false)
        return ViewHolder(view)
    }

    /**
     * 🔄 Vincula os dados do produto a um item da lista
     */
    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    /**
     * ⚡ DiffUtil — otimiza atualizações da lista
     */
    class DiffCallback : DiffUtil.ItemCallback<ProductEntity>() {
        override fun areItemsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: ProductEntity, newItem: ProductEntity): Boolean =
            oldItem == newItem
    }
}
