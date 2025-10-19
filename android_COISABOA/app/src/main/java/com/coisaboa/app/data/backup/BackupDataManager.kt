package com.coisaboa.app.data.backup

import android.content.Context
import android.os.Environment
import android.util.Log
import com.coisaboa.app.config.DatabaseProvider
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
