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
 * Opera 100% offline e armazena em pasta privada externa do app.
 *
 * 📂 Local de salvamento:
 * /storage/emulated/0/Android/data/com.coisaboa.app/files/compras/
 */
class MediaStorage(private val context: Context) {

    // 📁 Pasta principal (armazenamento externo privado do app)
    private val pastaCompras: File = File(context.getExternalFilesDir("compras"), "")

    init {
        if (!pastaCompras.exists()) {
            val criada = pastaCompras.mkdirs()
            if (!criada) {
                Log.e("MediaStorage", "❌ Falha ao criar pasta de imagens: ${pastaCompras.absolutePath}")
            } else {
                Log.i("MediaStorage", "📁 Pasta criada: ${pastaCompras.absolutePath}")
            }
        } else {
            Log.i("MediaStorage", "📂 Pasta já existente: ${pastaCompras.absolutePath}")
        }
    }

    /**
     * 💾 Salva uma imagem (Bitmap) no armazenamento e retorna o caminho absoluto.
     *
     * @param bitmap  Imagem vinda da galeria ou câmera.
     * @param tipo    Identificador da foto ("produto", "vendedor", "nota", etc.).
     * @return Caminho absoluto do arquivo salvo.
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
            arquivo.absolutePath // ✅ Caminho visível e decodificável
        } catch (e: IOException) {
            Log.e("MediaStorage", "❌ Erro ao salvar imagem: ${e.message}")
            ""
        }
    }

    /** 📸 Lista todas as imagens salvas na pasta "compras". */
    fun listarImagens(): List<File> {
        return pastaCompras.listFiles()?.toList()?.sortedByDescending { it.lastModified() } ?: emptyList()
    }

    /** 🧹 Remove todas as imagens salvas. */
    fun limparImagens(): Boolean {
        var sucesso = true
        pastaCompras.listFiles()?.forEach {
            if (!it.delete()) sucesso = false
        }
        return sucesso
    }

    /** 🔍 Verifica se uma imagem existe em disco. */
    fun existeImagem(caminho: String?): Boolean {
        if (caminho.isNullOrBlank()) return false
        val arquivo = File(caminho)
        return arquivo.exists() && arquivo.isFile
    }
}
