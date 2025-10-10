package com.coisaboa.app.utils

import android.content.Context
import android.graphics.Bitmap
import android.util.Log
import java.io.File
import java.io.FileOutputStream
import java.io.IOException
import java.text.SimpleDateFormat
import java.util.*

/**
 * Classe utilitária para salvar e gerenciar fotos tiradas pelo app COISABOA.
 * Opera 100% offline no armazenamento interno da aplicação.
 *
 * 📂 Local de salvamento: /data/data/com.coisaboa.app/files/compras/
 */
class MediaStorage(private val context: Context) {

    // 📁 Pasta principal onde as imagens serão armazenadas
    private val pastaCompras = File(context.filesDir, "compras")

    init {
        if (!pastaCompras.exists()) {
            val criada = pastaCompras.mkdirs()
            if (!criada) {
                Log.e("MediaStorage", "❌ Falha ao criar pasta de imagens: ${pastaCompras.absolutePath}")
            }
        }
    }

    /**
     * 💾 Salva uma imagem (Bitmap) no armazenamento interno e retorna o caminho completo.
     *
     * @param bitmap  Imagem capturada pela câmera (miniatura ou redimensionada).
     * @param tipo    Identificador do tipo de foto ("produto", "nota", etc.).
     * @return Caminho absoluto do arquivo salvo ou string vazia se falhar.
     */
    fun salvarImagem(bitmap: Bitmap, tipo: String): String {
        val timeStamp = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.getDefault()).format(Date())
        val nomeArquivo = "${tipo}_${timeStamp}.jpg"
        val arquivo = File(pastaCompras, nomeArquivo)

        return try {
            FileOutputStream(arquivo).use { out ->
                bitmap.compress(Bitmap.CompressFormat.JPEG, 90, out)
                out.flush()
            }
            Log.i("MediaStorage", "✅ Imagem salva em: ${arquivo.absolutePath}")
            arquivo.absolutePath
        } catch (e: IOException) {
            Log.e("MediaStorage", "❌ Erro ao salvar imagem: ${e.message}")
            ""
        }
    }

    /**
     * 📸 Lista todos os arquivos de imagem salvos na pasta "compras".
     * Pode ser usada para depuração ou histórico de fotos.
     */
    fun listarImagens(): List<File> {
        return pastaCompras.listFiles()?.toList()?.sortedByDescending { it.lastModified() } ?: emptyList()
    }

    /**
     * 🧹 Remove todas as imagens salvas (útil para backups ou limpeza de cache).
     */
    fun limparImagens(): Boolean {
        var sucesso = true
        pastaCompras.listFiles()?.forEach {
            if (!it.delete()) sucesso = false
        }
        return sucesso
    }

    /**
     * 🔍 Verifica se uma imagem existe em disco.
     *
     * @param caminho Caminho absoluto do arquivo.
     */
    fun existeImagem(caminho: String?): Boolean {
        if (caminho.isNullOrBlank()) return false
        val arquivo = File(caminho)
        return arquivo.exists() && arquivo.isFile
    }
}
