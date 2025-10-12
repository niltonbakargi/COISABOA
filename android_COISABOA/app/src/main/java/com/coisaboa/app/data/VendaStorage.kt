package com.coisaboa.app.data

import android.content.Context
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken

class VendaStorage(private val context: Context) {
    private val prefs = context.getSharedPreferences("vendas_local", Context.MODE_PRIVATE)
    private val gson = Gson()

    fun salvarVenda(venda: VendaModel) {
        val lista = listarVendas().toMutableList()
        lista.add(venda)
        prefs.edit().putString("vendas", gson.toJson(lista)).apply()
    }

    fun listarVendas(): List<VendaModel> {
        val json = prefs.getString("vendas", null) ?: return emptyList()
        val type = object : TypeToken<List<VendaModel>>() {}.type
        return gson.fromJson(json, type)
    }
}
