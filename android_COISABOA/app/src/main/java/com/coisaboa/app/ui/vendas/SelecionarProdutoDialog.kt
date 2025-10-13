package com.coisaboa.app.ui.vendas

import android.app.Dialog
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.view.LayoutInflater
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.DialogFragment
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.coisaboa.app.R
import com.coisaboa.app.data.entity.ProductEntity

/**
 * 🔍 SelecionarProdutoDialog
 * Diálogo reutilizável para escolher produtos do estoque com filtro de busca.
 */
class SelecionarProdutoDialog(
    private val produtos: List<ProductEntity>,
    private val onProdutoSelecionado: (ProductEntity) -> Unit
) : DialogFragment() {

    override fun onCreateDialog(savedInstanceState: Bundle?): Dialog {
        val context = requireContext()
        val view = LayoutInflater.from(context).inflate(R.layout.dialog_selecionar_produto, null)

        val etBuscar = view.findViewById<EditText>(R.id.etBuscarProduto)
        val rvLista = view.findViewById<RecyclerView>(R.id.rvListaProdutos)
        val tvEmpty = view.findViewById<TextView>(R.id.tvSemResultados)

        val adapter = ProdutoDialogAdapter { produto ->
            onProdutoSelecionado(produto)
            dismiss()
        }

        rvLista.layoutManager = LinearLayoutManager(context)
        rvLista.adapter = adapter
        adapter.submitList(produtos)

        // 🔎 Atualiza lista ao digitar
        etBuscar.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                val query = s.toString().trim().lowercase()
                val filtrados = produtos.filter { it.nome.lowercase().contains(query) }
                adapter.submitList(filtrados)
                tvEmpty.visibility = if (filtrados.isEmpty()) TextView.VISIBLE else TextView.GONE
            }
            override fun afterTextChanged(s: Editable?) {}
        })

        return AlertDialog.Builder(context)
            .setTitle("Selecionar produto")
            .setView(view)
            .setNegativeButton("Cancelar", null)
            .create()
    }
}
