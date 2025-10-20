package com.coisaboa.app.data.backup

import android.content.Context
import android.os.Environment
import android.util.Log
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.entity.SaleEntity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.io.FileInputStream
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.*
import java.util.Locale
import java.util.zip.ZipEntry
import java.util.zip.ZipInputStream

class BackupDataManager(private val context: Context) {

    private val TAG = "BackupDataManager"
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd_HH-mm-ss", Locale.getDefault())
    private val displayDateFormat = SimpleDateFormat("dd/MM/yyyy HH:mm:ss", Locale.getDefault())

    /**
     * Cria diretório de backup com timestamp
     */
    private fun criarDiretorioBackup(): File {
        val timestamp = dateFormat.format(Date())
        val nomePasta = "COISABOA_$timestamp"
        
        val docsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOCUMENTS)
        val backupDir = File(docsDir, nomePasta)
        
        if (!backupDir.exists()) {
            backupDir.mkdirs()
            Log.d(TAG, "📁 Diretório criado: ${backupDir.absolutePath}")
        }
        
        return backupDir
    }

    /**
     * Copia TODAS as imagens para o backup (produtos + compras)
     */
    private suspend fun copiarTodasImagensParaBackup(
        backupDir: File, 
        productDao: ProductDao, 
        purchaseDao: com.coisaboa.app.data.dao.PurchaseDao
    ): BackupImagensResult {
        var sucessos = 0
        var falhas = 0
        
        return try {
            val imagensDir = File(backupDir, "imagens").apply { mkdirs() }

            // 1. Copiar imagens dos produtos (ProductEntity)
            val produtos = withContext(Dispatchers.IO) { productDao.getAll() }
            produtos.forEach { produto ->
                produto.caminhoImagem?.takeIf { it.isNotBlank() }?.let { caminhoImagem ->
                    if (copiarArquivoUnico(File(caminhoImagem), imagensDir)) {
                        sucessos++
                        Log.d(TAG, "✅ Imagem produto copiada: ${File(caminhoImagem).name}")
                    } else {
                        falhas++
                        Log.w(TAG, "⚠️ Imagem produto não encontrada: $caminhoImagem")
                    }
                }
            }

            // 2. Copiar imagens das compras (PurchaseEntity - produto + vendedor)
            val compras = withContext(Dispatchers.IO) { purchaseDao.getAll() }
            compras.forEach { compra ->
                // Imagem do produto da compra
                compra.caminhoImagemProduto?.takeIf { it.isNotBlank() }?.let { caminhoImagem ->
                    if (copiarArquivoUnico(File(caminhoImagem), imagensDir)) {
                        sucessos++
                        Log.d(TAG, "✅ Imagem produto compra copiada: ${File(caminhoImagem).name}")
                    } else {
                        falhas++
                        Log.w(TAG, "⚠️ Imagem produto compra não encontrada: $caminhoImagem")
                    }
                }

                // Imagem do vendedor/nota da compra
                compra.caminhoImagemNota?.takeIf { it.isNotBlank() }?.let { caminhoImagem ->
                    if (copiarArquivoUnico(File(caminhoImagem), imagensDir)) {
                        sucessos++
                        Log.d(TAG, "✅ Imagem vendedor/nota copiada: ${File(caminhoImagem).name}")
                    } else {
                        falhas++
                        Log.w(TAG, "⚠️ Imagem vendedor/nota não encontrada: $caminhoImagem")
                    }
                }
            }
            
            BackupImagensResult(sucessos, falhas)
        } catch (e: Exception) {
            Log.e(TAG, "❌ Erro geral ao copiar imagens: ${e.message}")
            BackupImagensResult(0, 0)
        }
    }

    /**
     * Método auxiliar para copiar arquivo único (evita duplicação)
     */
    private fun copiarArquivoUnico(arquivoOriginal: File, destinoDir: File): Boolean {
        return try {
            if (arquivoOriginal.exists() && arquivoOriginal.isFile) {
                val arquivoBackup = File(destinoDir, arquivoOriginal.name)
                // Só copia se o arquivo ainda não existe no backup (evita sobrescrever)
                if (!arquivoBackup.exists()) {
                    arquivoOriginal.copyTo(arquivoBackup, overwrite = false)
                }
                true
            } else {
                false
            }
        } catch (e: Exception) {
            false
        }
    }

    /**
     * Restaura TODAS as imagens do backup
     */
    private suspend fun restaurarTodasImagens(
        backupDir: File, 
        productDao: ProductDao, 
        purchaseDao: com.coisaboa.app.data.dao.PurchaseDao
    ): BackupImagensResult {
        var sucessos = 0
        var falhas = 0
        
        return try {
            val imagensDir = File(backupDir, "imagens")
            if (!imagensDir.exists()) {
                Log.d(TAG, "📁 Nenhuma pasta de imagens no backup")
                return BackupImagensResult(0, 0)
            }

            val produtos = withContext(Dispatchers.IO) { productDao.getAll() }
            val compras = withContext(Dispatchers.IO) { purchaseDao.getAll() }
            val arquivosImagem = imagensDir.listFiles { file -> 
                file.isFile && isArquivoImagem(file)
            }

            arquivosImagem?.forEach { arquivoBackup ->
                try {
                    // Tentar encontrar em produtos primeiro
                    val produtoCorrespondente = produtos.find { produto ->
                        produto.caminhoImagem?.contains(arquivoBackup.name) == true
                    }
                    
                    if (produtoCorrespondente != null) {
                        // Restaurar imagem de produto
                        produtoCorrespondente.caminhoImagem?.let { caminhoOriginal ->
                            if (restaurarArquivoUnico(arquivoBackup, File(caminhoOriginal))) {
                                sucessos++
                                Log.d(TAG, "✅ Imagem produto restaurada: ${arquivoBackup.name}")
                            } else {
                                falhas++
                            }
                        } ?: run { falhas++ }
                    } else {
                        // Tentar encontrar em compras
                        val compraCorrespondente = compras.find { compra ->
                            compra.caminhoImagemProduto?.contains(arquivoBackup.name) == true ||
                            compra.caminhoImagemNota?.contains(arquivoBackup.name) == true
                        }
                        
                        compraCorrespondente?.let { compra ->
                            val caminhoOriginal = when {
                                compra.caminhoImagemProduto?.contains(arquivoBackup.name) == true -> compra.caminhoImagemProduto
                                compra.caminhoImagemNota?.contains(arquivoBackup.name) == true -> compra.caminhoImagemNota
                                else -> null
                            }
                            
                            caminhoOriginal?.let { caminho ->
                                if (restaurarArquivoUnico(arquivoBackup, File(caminho))) {
                                    sucessos++
                                    val tipo = if (compra.caminhoImagemProduto?.contains(arquivoBackup.name) == true) "produto compra" else "vendedor/nota"
                                    Log.d(TAG, "✅ Imagem $tipo restaurada: ${arquivoBackup.name}")
                                } else {
                                    falhas++
                                }
                            } ?: run { falhas++ }
                        } ?: run { falhas++ }
                    }
                } catch (e: Exception) {
                    falhas++
                }
            }
            
            BackupImagensResult(sucessos, falhas)
        } catch (e: Exception) {
            Log.e(TAG, "❌ Erro geral ao restaurar imagens: ${e.message}")
            BackupImagensResult(0, 0)
        }
    }

    /**
     * Método auxiliar para restaurar arquivo único
     */
    private fun restaurarArquivoUnico(arquivoBackup: File, arquivoOriginal: File): Boolean {
        return try {
            arquivoOriginal.parentFile?.mkdirs()
            arquivoBackup.copyTo(arquivoOriginal, overwrite = true)
            true
        } catch (e: Exception) {
            false
        }
    }

    /**
     * Verifica se o arquivo é uma imagem
     */
    private fun isArquivoImagem(arquivo: File): Boolean {
        val extensoesImagem = arrayOf("jpg", "jpeg", "png", "gif", "bmp", "webp")
        return extensoesImagem.any { arquivo.extension.lowercase() == it }
    }

    /**
     * Cria JSON com dados do backup
     */
    private suspend fun criarJsonBackup(
        productDao: ProductDao, 
        saleDao: com.coisaboa.app.data.dao.SaleDao,
        purchaseDao: com.coisaboa.app.data.dao.PurchaseDao
    ): JSONObject {
        return JSONObject().apply {
            put("timestamp", System.currentTimeMillis())
            put("dataBackup", displayDateFormat.format(Date()))
            put("appVersion", "1.0")
            put("dispositivo", android.os.Build.MODEL)
            
            // Produtos
            put("products", JSONArray().apply {
                productDao.getAll().forEach { product ->
                    put(JSONObject().apply {
                        put("id", product.id)
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
                        put("id", sale.id)
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
            
            // Compras
            put("purchases", JSONArray().apply {
                purchaseDao.getAll().forEach { purchase ->
                    put(JSONObject().apply {
                        put("id", purchase.id)
                        put("produtoNome", purchase.produtoNome)
                        put("quantidade", purchase.quantidade)
                        put("valorUnitario", purchase.valorUnitario)
                        put("valorTotal", purchase.valorTotal)
                        put("valorRevenda", purchase.valorRevenda ?: 0.0)
                        put("formaPagamento", purchase.formaPagamento ?: "")
                        put("caminhoImagemProduto", purchase.caminhoImagemProduto ?: "")
                        put("caminhoImagemNota", purchase.caminhoImagemNota ?: "")
                        put("dataCompra", purchase.dataCompra.time)
                    })
                }
            })
        }
    }

    /**
     * Executa backup completo
     */
    suspend fun fazerBackup(): Boolean = withContext(Dispatchers.IO) {
        return@withContext try {
            val database = DatabaseProvider.get(context)
            val productDao = database.productDao()
            val saleDao = database.saleDao()
            val purchaseDao = database.purchaseDao()

            val backupDir = criarDiretorioBackup()
            val json = criarJsonBackup(productDao, saleDao, purchaseDao)

            // Salvar JSON
            val backupFile = File(backupDir, "backup_dados.json")
            FileOutputStream(backupFile).use { 
                it.write(json.toString(2).toByteArray()) 
            }

            // Copiar TODAS as imagens (produtos + compras)
            val resultadoImagens = copiarTodasImagensParaBackup(backupDir, productDao, purchaseDao)

            // Log resumido
            Log.d(TAG, buildString {
                append("✅ BACKUP CONCLUÍDO\n")
                append("📁 Local: ${backupDir.name}\n")
                append("📦 Produtos: ${json.getJSONArray("products").length()}\n")
                append("💰 Vendas: ${json.getJSONArray("sales").length()}\n")
                append("🛒 Compras: ${json.getJSONArray("purchases").length()}\n")
                append("📸 Total imagens: ${resultadoImagens.sucessos}\n")
                if (resultadoImagens.falhas > 0) {
                    append("⚠️ Falhas: ${resultadoImagens.falhas} imagens\n")
                }
            })

            true
        } catch (e: Exception) {
            Log.e(TAG, "❌ FALHA NO BACKUP: ${e.message}", e)
            false
        }
    }

    /**
     * Restaura backup completo
     */
    suspend fun restaurarBackup(): Boolean = withContext(Dispatchers.IO) {
        return@withContext try {
            val database = DatabaseProvider.get(context)
            val productDao = database.productDao()
            val saleDao = database.saleDao()
            val purchaseDao = database.purchaseDao()

            val backupDir = getBackupDirectoryMaisRecente()
            val backupFile = File(backupDir, "backup_dados.json")

            if (!backupFile.exists()) {
                Log.e(TAG, "❌ Arquivo de backup não encontrado")
                return@withContext false
            }

            val json = JSONObject(backupFile.readText())

            // Limpar dados existentes
            try {
                val produtosExistentes = productDao.getAll()
                produtosExistentes.forEach { produto ->
                    productDao.delete(produto)
                }
                Log.d(TAG, "✅ ${produtosExistentes.size} produtos antigos removidos")
            } catch (e: Exception) {
                Log.w(TAG, "⚠️ Não foi possível limpar produtos: ${e.message}")
            }

            // Restaurar produtos
            val produtosArray = json.getJSONArray("products")
            var produtosRestaurados = 0
            for (i in 0 until produtosArray.length()) {
                val p = produtosArray.getJSONObject(i)
                try {
                    productDao.insert(ProductEntity(
                        id = 0,
                        nome = p.getString("nome"),
                        quantidade = p.getInt("quantidade"),
                        valorEstimado = p.getDouble("valorEstimado"),
                        valorRevenda = p.getDouble("valorRevenda"),
                        caminhoImagem = p.optString("caminhoImagem", ""),
                        observacoes = p.optString("observacoes", "")
                    ))
                    produtosRestaurados++
                } catch (e: Exception) {
                    Log.e(TAG, "❌ Erro ao restaurar produto ${p.getString("nome")}: ${e.message}")
                }
            }

            // Restaurar vendas
            val vendasArray = json.getJSONArray("sales")
            var vendasRestauradas = 0
            for (i in 0 until vendasArray.length()) {
                val s = vendasArray.getJSONObject(i)
                try {
                    saleDao.insert(SaleEntity(
                        id = 0,
                        produtoNome = s.getString("produtoNome"),
                        quantidade = s.getInt("quantidade"),
                        valorUnitario = s.getDouble("valorUnitario"),
                        valorTotal = s.getDouble("valorTotal"),
                        valorEstimado = s.getDouble("valorEstimado"),
                        formaPagamento = s.optString("formaPagamento", ""),
                        observacoes = s.optString("observacoes", ""),
                        dataVenda = Date(s.getLong("dataVenda"))
                    ))
                    vendasRestauradas++
                } catch (e: Exception) {
                    Log.e(TAG, "❌ Erro ao restaurar venda ${s.getString("produtoNome")}: ${e.message}")
                }
            }

            // Restaurar compras
            val comprasArray = json.optJSONArray("purchases")
            var comprasRestauradas = 0
            if (comprasArray != null) {
                for (i in 0 until comprasArray.length()) {
                    val c = comprasArray.getJSONObject(i)
                    try {
                        purchaseDao.insert(PurchaseEntity(
                            id = 0,
                            produtoNome = c.getString("produtoNome"),
                            quantidade = c.getInt("quantidade"),
                            valorUnitario = c.getDouble("valorUnitario"),
                            valorTotal = c.getDouble("valorTotal"),
                            valorRevenda = c.optDouble("valorRevenda", 0.0),
                            formaPagamento = c.optString("formaPagamento", ""),
                            caminhoImagemProduto = c.optString("caminhoImagemProduto", ""),
                            caminhoImagemNota = c.optString("caminhoImagemNota", ""),
                            dataCompra = Date(c.getLong("dataCompra"))
                        ))
                        comprasRestauradas++
                    } catch (e: Exception) {
                        Log.e(TAG, "❌ Erro ao restaurar compra ${c.getString("produtoNome")}: ${e.message}")
                    }
                }
            }

            // Restaurar TODAS as imagens
            val resultadoImagens = restaurarTodasImagens(backupDir, productDao, purchaseDao)

            Log.d(TAG, buildString {
                append("✅ RESTAURAÇÃO CONCLUÍDA\n")
                append("📦 Produtos restaurados: $produtosRestaurados/${produtosArray.length()}\n")
                append("💰 Vendas restauradas: $vendasRestauradas/${vendasArray.length()}\n")
                append("🛒 Compras restauradas: $comprasRestauradas/${comprasArray?.length() ?: 0}\n")
                append("📸 Imagens restauradas: ${resultadoImagens.sucessos}\n")
                if (resultadoImagens.falhas > 0) {
                    append("⚠️ Falhas em imagens: ${resultadoImagens.falhas}\n")
                }
            })

            true
        } catch (e: Exception) {
            Log.e(TAG, "❌ FALHA NA RESTAURAÇÃO: ${e.message}", e)
            false
        }
    }

    /**
     * 🔥 ATUALIZADO: Encontra backup mais recente (Documents → Downloads)
     */
    private fun getBackupDirectoryMaisRecente(): File {
        // 1. PRIMEIRO: Tenta backup em Documents/
        val docsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOCUMENTS)
        val backupDirsDocs = docsDir.listFiles { file -> 
            file.isDirectory && file.name.startsWith("COISABOA_")
        }
        
        val backupDocs = backupDirsDocs?.maxByOrNull { it.lastModified() }
        if (backupDocs != null && File(backupDocs, "backup_dados.json").exists()) {
            Log.d(TAG, "✅ Backup local encontrado: ${backupDocs.name}")
            return backupDocs
        }
        
        // 2. SEGUNDO: Se não achou, busca ZIP em Downloads/COISABOA/
        Log.d(TAG, "🔍 Procurando backup em Downloads...")
        val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
        val coisaboaDir = File(downloadsDir, "COISABOA")
        
        if (coisaboaDir.exists() && coisaboaDir.isDirectory) {
            val zipFiles = coisaboaDir.listFiles { file -> 
                file.isFile && file.name.startsWith("COISABOA_Backup_") && file.name.endsWith(".zip")
            }
            
            val zipMaisRecente = zipFiles?.maxByOrNull { it.lastModified() }
            if (zipMaisRecente != null) {
                Log.d(TAG, "✅ Backup ZIP encontrado: ${zipMaisRecente.name}")
                return extrairZipParaTemp(zipMaisRecente)
            }
        }
        
        // 3. Se não achou nada, retorna fallback
        Log.d(TAG, "❌ Nenhum backup encontrado")
        return File(docsDir, "COISABOA_${SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date())}")
    }

    /**
     * 🔥 NOVO: Extrai ZIP para pasta temporária
     */
    private fun extrairZipParaTemp(zipFile: File): File {
        val timestamp = SimpleDateFormat("yyyy-MM-dd_HH-mm-ss", Locale.getDefault()).format(Date())
        val tempDir = File(context.filesDir, "backup_restaurado_$timestamp")
        tempDir.mkdirs()
        
        Log.d(TAG, "📦 Extraindo ZIP: ${zipFile.name}")
        
        ZipInputStream(FileInputStream(zipFile)).use { zis ->
            var entry: ZipEntry?
            while (zis.nextEntry.also { entry = it } != null) {
                val entryFile = File(tempDir, entry!!.name)
                if (entry!!.isDirectory) {
                    entryFile.mkdirs()
                } else {
                    entryFile.parentFile?.mkdirs()
                    FileOutputStream(entryFile).use { fos ->
                        zis.copyTo(fos)
                    }
                }
                zis.closeEntry()
            }
        }
        
        Log.d(TAG, "✅ ZIP extraído: ${tempDir.listFiles()?.size} arquivos")
        return tempDir
    }

    /**
     * 🔥 ATUALIZADO: Verifica se existe backup (local ou ZIP)
     */
    fun backupExiste(): Boolean {
        return try {
            val backupDir = getBackupDirectoryMaisRecente()
            File(backupDir, "backup_dados.json").exists()
        } catch (e: Exception) {
            false
        }
    }

    /**
     * 🔥 ATUALIZADO: Obtém informações do backup (local ou cloud)
     */
    fun getBackupInfo(): String {
        return try {
            val backupDir = getBackupDirectoryMaisRecente()
            if (File(backupDir, "backup_dados.json").exists()) {
                val imagensDir = File(backupDir, "imagens")
                val totalImagens = imagensDir.listFiles()?.size ?: 0
                
                if (backupDir.absolutePath.contains("backup_restaurado_")) {
                    "Backup Cloud (${backupDir.name})"
                } else {
                    "Backup: ${backupDir.name} ($totalImagens imagens)"
                }
            } else {
                "Nenhum backup encontrado"
            }
        } catch (e: Exception) {
            "Erro ao verificar backup"
        }
    }

    /**
     * Obtém caminho do backup
     */
    fun getBackupPath(): String {
        return try {
            getBackupDirectoryMaisRecente().absolutePath
        } catch (e: Exception) {
            "Caminho não disponível"
        }
    }

    /**
     * Obtém diretório de backup atual
     */
    fun getBackupDirectory(): File {
        return getBackupDirectoryMaisRecente()
    }

    /**
     * Resultado do processamento de imagens
     */
    private data class BackupImagensResult(
        val sucessos: Int,
        val falhas: Int
    )
}