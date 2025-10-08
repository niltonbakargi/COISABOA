package com.coisaboa.app.utils

import android.text.Editable
import android.text.TextWatcher

/**
 * Classe utilitária simples para facilitar o uso de TextWatcher
 * — permite passar apenas uma função lambda quando o texto muda.
 */
class TextWatcherAdapter(
    private val onTextChanged: (String) -> Unit
) : TextWatcher {

    override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
    override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
        onTextChanged.invoke(s?.toString() ?: "")
    }
    override fun afterTextChanged(s: Editable?) {}
}
