package com.coisaboa.app.data.backup

import android.content.Context
import android.util.Log
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.SaleEntity
import com.coisaboa.app.data.repository.ProductRepository
import com.coisaboa.app.data.repository.SaleRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Gerenciador de backup usando JSONObject nativo
 */
class BackupDataManager(private val context: Context) {

    companion object {
        private const val TAG = "BackupDataManager"
        private const val BACKUP_FOLDER = "COISABOA"
        private const val CURRENT_BACKUP_FILE = "backup_data.json"
        private const val PREVIOUS_BACKUP_FILE = "backup_data_previous.json"
    }

    /**
     * Faz backup completo dos dados com rotação
     */
    suspend fun fazerBackupCompleto(): Boolean {
        return try {
            Log.d(TAG, "Iniciando backup completo...")

            // 1. Rotacionar backup anterior se existir
            rotacionarBackupAnterior()

            // 2. Coletar dados atuais
            val backupData = coletarDadosAtuais()

            // 3. Salvar novo backup
            val success = salvarBackup(backupData, CURRENT_BACKUP_FILE)

            if (success) {
                Log.d(TAG, "Backup concluído com sucesso")
                true
            } else {
                Log.e(TAG, "Falha ao salvar backup")
                false
            }

        } catch (e: Exception) {
            Log.e(TAG, "Erro durante backup: ${e.message}", e)
            false
        }
    }

    /**
     * Rotaciona backup atual para anterior
     */
    private fun rotacionarBackupAnterior() {
        try {
            val backupDir = getBackupDirectory()
            val currentFile = File(backupDir, CURRENT_BACKUP_FILE)
            val previousFile = File(backupDir, PREVIOUS_BACKUP_FILE)

            if (currentFile.exists()) {
                if (previousFile.exists()) {
                    previousFile.delete()
                }
                currentFile.renameTo(previousFile)
                Log.d(TAG, "Backup anterior rotacionado")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Erro ao rotacionar backup anterior: ${e.message}")
        }
    }

    /**
     * Coleta todos os dados atuais do banco
     */
    private suspend fun coletarDadosAtuais(): JSONObject {
        return withContext(Dispatchers.IO) {
            val database = DatabaseProvider.get(context)
            val productRepository = ProductRepository(database.productDao())
            val saleRepository = SaleRepository(database.saleDao(), productRepository)

            val products = productRepository.getAll()
            val sales = saleRepository.getAll()

            Log.d(TAG, "Dados coletados: ${products.size} produtos, ${sales.size} vendas")

            val jsonObject = JSONObject()
            
            // Metadata
            jsonObject.put("timestamp", SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault()).format(Date()))
            jsonObject.put("appVersion", "1.0.0")
            jsonObject.put("recordCounts", JSONObject().apply {
                put("products", products.size)
                put("sales", sales.size)
            })
            
            // Products array
            val productsArray = JSONArray()
            products.forEach { product ->
                val productJson = JSONObject()
                productJson.put("id", product.id)
                productJson.put("nome", product.nome)
                productJson.put("quantidade", product.quantidade)
                product.valorEstimado?.let { productJson.put("valorEstimado", it) }
                product.valorRevenda?.let { productJson.put("valorRevenda", it) }
                product.caminhoImagem?.let { productJson.put("caminhoImagem", it) }
                product.observacoes?.let { productJson.put("observacoes", it) }
                productsArray.put(productJson)
            }
            jsonObject.put("products", productsArray)
            
            // Sales array
            val salesArray = JSONArray()
            sales.forEach { sale ->
                val saleJson = JSONObject()
                saleJson.put("id", sale.id)
                saleJson.put("produtoNome", sale.produtoNome)
                saleJson.put("quantidade", sale.quantidade)
                saleJson.put("valorUnitario", sale.valorUnitario)
                saleJson.put("valorTotal", sale.valorTotal)
                sale.valorEstimado?.let { saleJson.put("valorEstimado", it) }
                sale.formaPagamento?.let { saleJson.put("formaPagamento", it) }
                sale.observacoes?.let { saleJson.put("observacoes", it) }
                sale.dataVenda?.let { saleJson.put("dataVenda", it.time) }
                salesArray.put(saleJson)
            }
            jsonObject.put("sales", salesArray)

            jsonObject
        }
    }

    /**
     * Salva dados em arquivo JSON
     */
    private fun salvarBackup(backupData: JSONObject, fileName: String): Boolean {
        return try {
            val backupDir = getBackupDirectory()
            if (!backupDir.exists()) {
                backupDir.mkdirs()
            }

            val backupFile = File(backupDir, fileName)
            val jsonString = backupData.toString(2) // indentação de 2 espaços

            FileOutputStream(backupFile).use { output ->
                output.write(jsonString.toByteArray())
            }

            Log.d(TAG, "Backup salvo: ${backupFile.absolutePath}")
            Log.d(TAG, "Tamanho do backup: ${jsonString.length} bytes")
            true

        } catch (e: Exception) {
            Log.e(TAG, "Erro ao salvar backup: ${e.message}")
            false
        }
    }

    /**
     * Obtém diretório de backup
     */
    private fun getBackupDirectory(): File {
        return File(context.getExternalFilesDir(null), BACKUP_FOLDER)
    }

    /**
     * Verifica se existe backup disponível
     */
    fun existeBackupDisponivel(): Boolean {
        val backupDir = getBackupDirectory()
        val currentFile = File(backupDir, CURRENT_BACKUP_FILE)
        val previousFile = File(backupDir, PREVIOUS_BACKUP_FILE)

        return currentFile.exists() || previousFile.exists()
    }

    /**
     * Obtém informações sobre os backups
     */
    fun getBackupInfo(): String {
        val backupDir = getBackupDirectory()
        val currentFile = File(backupDir, CURRENT_BACKUP_FILE)
        val previousFile = File(backupDir, PREVIOUS_BACKUP_FILE)

        return when {
            currentFile.exists() -> "Backup atual disponivel"
            previousFile.exists() -> "Backup anterior disponivel"
            else -> "Nenhum backup disponivel"
        }
    }
}
