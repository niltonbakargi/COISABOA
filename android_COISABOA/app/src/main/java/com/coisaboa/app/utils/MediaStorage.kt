package com.coisaboa.app.utils

import android.content.Context
import android.graphics.Bitmap
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.*

/**
 * Classe utilitária para salvar fotos tiradas pelo app no armazenamento interno.
 * Trabalha offline, usando a pasta /data/data/com.coisaboa.app/files/compras/
 */
class MediaStorage(private val context: Context) {

    private val pastaCompras = File(context.filesDir, "compras")

    init {
        if (!pastaCompras.exists()) {
            pastaCompras.mkdirs()
        }
    }

    /**
     * Salva uma imagem (Bitmap) no armazenamento interno e retorna o caminho absoluto do arquivo.
     *
     * @param bitmap Imagem capturada pela câmera.
     * @param tipo String de identificação ("produto", "nota", etc.)
     */
    fun salvarImagem(bitmap: Bitmap, tipo: String): String {
        val timeStamp = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.getDefault()).format(Date())
        val nomeArquivo = "${timeStamp}_${tipo}.jpg"
        val arquivo = File(pastaCompras, nomeArquivo)

        FileOutputStream(arquivo).use { out ->
            bitmap.compress(Bitmap.CompressFormat.JPEG, 90, out)
            out.flush()
        }

        return arquivo.absolutePath
    }

    /**
     * Lista todas as imagens salvas (útil para debug ou tela de histórico).
     */
    fun listarImagens(): List<File> {
        return pastaCompras.listFiles()?.toList() ?: emptyList()
    }

    /**
     * Remove todas as imagens da pasta (opcional, pode ser usada em backups).
     */
    fun limparImagens() {
        pastaCompras.listFiles()?.forEach { it.delete() }
    }
}
