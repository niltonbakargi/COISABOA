package com.coisaboa.app.data.backup

import android.content.Context
import android.os.Environment
import android.util.Log
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.SaleEntity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Date

class BackupDataManager(private val context: Context) {

    private val TAG = "BackupDataManager"

    /**
     * Copia imagens dos produtos para a pasta de backup
     */
    private suspend fun copiarImagensParaBackup(backupDir: File, productDao: ProductDao): Int {
        var imagensCopiadas = 0
        try {
            val imagensDir = File(backupDir, "imagens")
            if (!imagensDir.exists()) {
                imagensDir.mkdirs()
            }

            val produtos = withContext(Dispatchers.IO) { productDao.getAll() }
            produtos.forEach { produto ->
                produto.caminhoImagem?.let { caminhoImagem ->
                    if (caminhoImagem.isNotEmpty()) {
                        val arquivoOriginal = File(caminhoImagem)
                        if (arquivoOriginal.exists()) {
                            val extensao = arquivoOriginal.extension
                            val nomeArquivo = "produto_${produto.id}.$extensao"
                            val arquivoBackup = File(imagensDir, nomeArquivo)
                            
                            arquivoOriginal.copyTo(arquivoBackup, overwrite = true)
                            imagensCopiadas++
                            Log.d(TAG, "📸 Imagem copiada: $nomeArquivo")
                        } else {
                            Log.w(TAG, "⚠️ Imagem não encontrada: $caminhoImagem")
                        }
                    }
                }
            }
            Log.d(TAG, "✅ $imagensCopiadas imagens copiadas para backup")
        } catch (e: Exception) {
            Log.e(TAG, "❌ Erro ao copiar imagens: ${e.message}")
        }
        return imagensCopiadas
    }

    /**
     * Restaura imagens do backup para o dispositivo
     */
    private suspend fun restaurarImagens(backupDir: File, productDao: ProductDao): Int {
        var imagensRestauradas = 0
        try {
            val imagensDir = File(backupDir, "imagens")
            if (!imagensDir.exists()) {
                Log.d(TAG, "📁 Nenhuma pasta de imagens encontrada no backup")
                return 0
            }

            val produtos = withContext(Dispatchers.IO) { productDao.getAll() }
            val arquivosImagem = imagensDir.listFiles { file -> 
                file.extension in listOf("jpg", "jpeg", "png", "webp")
            }

            arquivosImagem?.forEach { arquivoBackup ->
                try {
                    // Extrair ID do produto do nome do arquivo: produto_123.jpg → 123
                    val nome = arquivoBackup.nameWithoutExtension
                    val idProduto = nome.removePrefix("produto_").toLongOrNull()
                    
                    idProduto?.let { id ->
                        val produto = produtos.find { it.id == id }
                        produto?.let { p ->
                            // Criar diretório de imagens do app se não existir
                            val picsDir = File(Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_PICTURES), "COISABOA")
                            if (!picsDir.exists()) {
                                picsDir.mkdirs()
                            }
                            
                            val novoCaminho = File(picsDir, "produto_${p.id}.${arquivoBackup.extension}")
                            arquivoBackup.copyTo(novoCaminho, overwrite = true)
                            
                            // Atualizar caminho da imagem no produto
                            val produtoAtualizado = p.copy(caminhoImagem = novoCaminho.absolutePath)
                            productDao.insert(produtoAtualizado)
                            
                            imagensRestauradas++
                            Log.d(TAG, "📸 Imagem restaurada: ${novoCaminho.name}")
                        }
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "❌ Erro ao restaurar imagem ${arquivoBackup.name}: ${e.message}")
                }
            }
            
            Log.d(TAG, "✅ $imagensRestauradas imagens restauradas")
        } catch (e: Exception) {
            Log.e(TAG, "❌ Erro ao restaurar imagens: ${e.message}")
        }
        return imagensRestauradas
    }

    suspend fun fazerBackup(): Boolean = withContext(Dispatchers.IO) {
        try {
            val database = DatabaseProvider.get(context)
            val productDao = database.productDao()
            val saleDao = database.saleDao()

            val json = JSONObject().apply {
                put("timestamp", System.currentTimeMillis())
                put("appVersion", "1.0")
                
                // Produtos
                put("products", JSONArray().apply {
                    productDao.getAll().forEach { product ->
                        put(JSONObject().apply {
                            put("nome", product.nome)
                            put("quantidade", product.quantidade)
                            put("valorEstimado", product.valorEstimado ?: 0.0)
                            put("valorRevenda", product.valorRevenda ?: 0.0)
                            put("caminhoImagem", product.caminhoImagem ?: "")
                            put("observacoes", product.observacoes ?: "")
                        })
                    }
                })
                
                // Vendas
                put("sales", JSONArray().apply {
                    saleDao.getAll().forEach { sale ->
                        put(JSONObject().apply {
                            put("produtoNome", sale.produtoNome)
                            put("quantidade", sale.quantidade)
                            put("valorUnitario", sale.valorUnitario)
                            put("valorTotal", sale.valorTotal)
                            put("valorEstimado", sale.valorEstimado ?: 0.0)
                            put("formaPagamento", sale.formaPagamento ?: "")
                            put("observacoes", sale.observacoes ?: "")
                            put("dataVenda", sale.dataVenda?.time ?: System.currentTimeMillis())
                        })
                    }
                })
            }

            // Criar pasta com data
            val backupDir = getBackupDirectory()
            Log.d(TAG, "📁 Salvando backup em: ${backupDir.absolutePath}")
            
            if (!backupDir.exists()) {
                backupDir.mkdirs()
                Log.d(TAG, "✅ Diretório criado: ${backupDir.exists()}")
            }

            // Nome do arquivo com data e hora
            val fileDateFormat = SimpleDateFormat("yyyy-MM-dd_HH-mm-ss")
            val timestamp = fileDateFormat.format(Date())
            val backupFileName = "backup_$timestamp.json"
            val backupFile = File(backupDir, backupFileName)

            FileOutputStream(backupFile).use { 
                it.write(json.toString(2).toByteArray()) 
            }

            Log.d(TAG, "✅ Backup salvo com sucesso!")
            Log.d(TAG, "📊 Arquivo: ${backupFile.name}")
            Log.d(TAG, "📁 Caminho: ${backupFile.absolutePath}")
            
            // Copiar imagens para o backup
            val imagensCopiadas = copiarImagensParaBackup(backupDir, productDao)
            Log.d(TAG, "📸 $imagensCopiadas imagens incluídas no backup")
            
            true
        } catch (e: Exception) {
            Log.e(TAG, "❌ Erro no backup: ${e.message}", e)
            false
        }
    }

    suspend fun restaurarBackup(): Boolean = withContext(Dispatchers.IO) {
        try {
            val database = DatabaseProvider.get(context)
            val productDao = database.productDao()
            val saleDao = database.saleDao()

            val backupDir = getBackupDirectory()
            val backupFiles = backupDir.listFiles { file -> file.name.endsWith(".json") }
            val backupFile = backupFiles?.maxByOrNull { it.lastModified() }

            if (backupFile == null || !backupFile.exists()) {
                Log.e(TAG, "❌ Arquivo de backup não encontrado")
                return@withContext false
            }

            val jsonString = backupFile.readText()
            val json = JSONObject(jsonString)

            // Restaurar produtos (com IDs zerados)
            json.getJSONArray("products").let { products ->
                for (i in 0 until products.length()) {
                    val p = products.getJSONObject(i)
                    productDao.insert(ProductEntity(
                        id = 0,
                        nome = p.getString("nome"),
                        quantidade = p.getInt("quantidade"),
                        valorEstimado = p.getDouble("valorEstimado"),
                        valorRevenda = p.getDouble("valorRevenda"),
                        caminhoImagem = p.getString("caminhoImagem"),
                        observacoes = p.getString("observacoes")
                    ))
                }
            }

            // Restaurar vendas (com IDs zerados)
            json.getJSONArray("sales").let { sales ->
                for (i in 0 until sales.length()) {
                    val s = sales.getJSONObject(i)
                    saleDao.insert(SaleEntity(
                        id = 0,
                        produtoNome = s.getString("produtoNome"),
                        quantidade = s.getInt("quantidade"),
                        valorUnitario = s.getDouble("valorUnitario"),
                        valorTotal = s.getDouble("valorTotal"),
                        valorEstimado = s.getDouble("valorEstimado"),
                        formaPagamento = s.getString("formaPagamento"),
                        observacoes = s.getString("observacoes"),
                        dataVenda = Date(s.getLong("dataVenda"))
                    ))
                }
            }

            Log.d(TAG, "✅ Restauração concluída do arquivo: ${backupFile.name}")
            
            // Restaurar imagens do backup
            val imagensRestauradas = restaurarImagens(backupDir, productDao)
            Log.d(TAG, "📸 $imagensRestauradas imagens restauradas")
            
            true
        } catch (e: Exception) {
            Log.e(TAG, "❌ Erro na restauração: ${e.message}", e)
            false
        }
    }

    /**
     * CAMINHO COM DATA - Salva em Documents/COISABOA_AAAA-MM-DD/
     */
    private fun getBackupDirectory(): File {
        // Formatar data atual: COISABOA_2024-01-19
        val dateFormat = SimpleDateFormat("yyyy-MM-dd")
        val dataAtual = dateFormat.format(Date())
        val nomePasta = "COISABOA_$dataAtual"
        
        // Salva em Documents/COISABOA_AAAA-MM-DD/
        val docsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOCUMENTS)
        val coisaboaDir = File(docsDir, nomePasta)
        
        Log.d(TAG, "📁 Diretório de backup: ${coisaboaDir.absolutePath}")
        
        return coisaboaDir
    }

    fun backupExiste(): Boolean {
        val backupDir = getBackupDirectory()
        return backupDir.exists() && backupDir.listFiles { file -> file.name.endsWith(".json") }?.isNotEmpty() == true
    }

    fun getBackupPath(): String {
        val backupDir = getBackupDirectory()
        val backupFile = backupDir.listFiles { file -> file.name.endsWith(".json") }?.maxByOrNull { it.lastModified() }
        return backupFile?.absolutePath ?: "Nenhum backup encontrado"
    }

    fun getBackupInfo(): String {
        val backupDir = getBackupDirectory()
        val backupFile = backupDir.listFiles { file -> file.name.endsWith(".json") }?.maxByOrNull { it.lastModified() }
        return if (backupFile != null && backupFile.exists()) {
            "Backup: ${backupFile.name}"
        } else {
            "Sem backup"
        }
    }
}

