package com.coisaboa.app.ui.backup

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.os.Environment
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.FileProvider
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.R
import com.coisaboa.app.data.backup.BackupDataManager
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileInputStream
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.zip.ZipEntry
import java.util.zip.ZipOutputStream

class BackupActivity : AppCompatActivity() {

    private lateinit var backupManager: BackupDataManager
    private lateinit var btnFazerBackup: Button
    private lateinit var btnRestaurarBackup: Button
    private lateinit var btnSalvarBackup: Button
    private lateinit var btnCompartilharBackup: Button
    private lateinit var btnVoltar: Button
    private lateinit var tvStatus: TextView
    private lateinit var tvBackupInfo: TextView

    private val fileProviderAuthority = "com.coisaboa.app.fileprovider"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_backup)
        supportActionBar?.title = "Backup de Dados"

        backupManager = BackupDataManager(this)

        btnFazerBackup = findViewById(R.id.btnFazerBackup)
        btnRestaurarBackup = findViewById(R.id.btnRestaurarBackup)
        btnSalvarBackup = findViewById(R.id.btnSalvarBackup)
        btnCompartilharBackup = findViewById(R.id.btnCompartilharBackup)
        btnVoltar = findViewById(R.id.btnVoltarBackup)
        tvStatus = findViewById(R.id.tvBackupStatus)
        tvBackupInfo = findViewById(R.id.tvBackupInfo)

        configurarEventos()
        atualizarStatus()
    }

    private fun configurarEventos() {
        btnFazerBackup.setOnClickListener {
            fazerBackup()
        }

        btnRestaurarBackup.setOnClickListener {
            restaurarBackup()
        }

        btnSalvarBackup.setOnClickListener {
            salvarBackupNoAparelho()
        }

        btnCompartilharBackup.setOnClickListener {
            compartilharBackup()
        }

        btnVoltar.setOnClickListener {
            finish()
        }
    }

    private fun fazerBackup() {
        lifecycleScope.launch {
            btnFazerBackup.isEnabled = false
            tvStatus.text = "Criando backup..."

            try {
                val success = backupManager.fazerBackup()

                if (success) {
                    tvStatus.text = "Backup concluído!"
                    Toast.makeText(this@BackupActivity, "Backup criado com sucesso!", Toast.LENGTH_LONG).show()
                    atualizarStatus()
                } else {
                    tvStatus.text = "Falha ao criar backup"
                    Toast.makeText(this@BackupActivity, "Erro ao criar backup", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                tvStatus.text = "Erro: ${e.message}"
                Toast.makeText(this@BackupActivity, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                btnFazerBackup.isEnabled = true
            }
        }
    }

    private fun restaurarBackup() {
        if (!backupManager.backupExiste()) {
            Toast.makeText(this, "Nenhum backup disponível para restaurar", Toast.LENGTH_LONG).show()
            return
        }
        Toast.makeText(this, "Funcionalidade de restauração em desenvolvimento", Toast.LENGTH_LONG).show()
    }

    // FUNÇÃO CORRIGIDA: Salvar Backup no Aparelho
    private fun salvarBackupNoAparelho() {
        if (!backupManager.backupExiste()) {
            Toast.makeText(this, "Faça um backup primeiro antes de salvar", Toast.LENGTH_LONG).show()
            return
        }

        lifecycleScope.launch {
            btnSalvarBackup.isEnabled = false
            tvStatus.text = "Salvando backup no aparelho..."

            try {
                val resultado = withContext(Dispatchers.IO) {
                    criarBackupCompactado()
                }

                if (resultado.first != null) {
                    tvStatus.text = "Backup salvo com sucesso!"
                    Toast.makeText(
                        this@BackupActivity, 
                        "Backup salvo: ${resultado.second}", 
                        Toast.LENGTH_LONG
                    ).show()
                } else {
                    tvStatus.text = "Erro ao salvar backup"
                    Toast.makeText(
                        this@BackupActivity, 
                        "Erro: ${resultado.second}", 
                        Toast.LENGTH_LONG
                    ).show()
                }
            } catch (e: Exception) {
                tvStatus.text = "Erro ao salvar"
                Toast.makeText(
                    this@BackupActivity, 
                    "Erro: ${e.message}", 
                    Toast.LENGTH_LONG
                ).show()
                e.printStackTrace()
            } finally {
                btnSalvarBackup.isEnabled = true
            }
        }
    }

    // FUNÇÃO CORRIGIDA: Compartilhar Backup
    private fun compartilharBackup() {
        if (!backupManager.backupExiste()) {
            Toast.makeText(this, "Faça um backup primeiro antes de compartilhar", Toast.LENGTH_LONG).show()
            return
        }

        lifecycleScope.launch {
            btnCompartilharBackup.isEnabled = false
            tvStatus.text = "Preparando backup para compartilhamento..."

            try {
                val resultado = withContext(Dispatchers.IO) {
                    criarBackupCompactado()
                }

                if (resultado.first != null) {
                    compartilharArquivo(resultado.first!!)
                    tvStatus.text = "Backup pronto para compartilhar!"
                } else {
                    tvStatus.text = "Erro ao preparar"
                    Toast.makeText(
                        this@BackupActivity, 
                        "Erro ao preparar backup: ${resultado.second}", 
                        Toast.LENGTH_LONG
                    ).show()
                }
            } catch (e: Exception) {
                tvStatus.text = "Erro ao compartilhar"
                Toast.makeText(
                    this@BackupActivity, 
                    "Erro: ${e.message}", 
                    Toast.LENGTH_LONG
                ).show()
                e.printStackTrace()
            } finally {
                btnCompartilharBackup.isEnabled = true
            }
        }
    }

    // FUNÇÃO CORRIGIDA: Buscar diretório de backup correto
    private fun getBackupDirectory(): File? {
        return try {
            // Primeiro tenta o diretório padrão do BackupDataManager (Documents/COISABOA_...)
            val documentsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOCUMENTS)
            val coisaboaDirs = documentsDir.listFiles { file -> 
                file.isDirectory && file.name.startsWith("COISABOA_")
            }
            
            // Pega o diretório mais recente
            coisaboaDirs?.maxByOrNull { it.lastModified() }
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }

    // FUNÇÃO CORRIGIDA: Criar backup compactado
    private fun criarBackupCompactado(): Pair<File?, String> {
        return try {
            // Buscar o diretório de backup correto
            val backupDir = getBackupDirectory()
            
            if (backupDir == null || !backupDir.exists()) {
                return Pair(null, "Diretório de backup não encontrado")
            }

            val arquivos = backupDir.listFiles()
            if (arquivos == null || arquivos.isEmpty()) {
                return Pair(null, "Nenhum arquivo de backup encontrado no diretório")
            }

            // Criar nome com timestamp
            val timestamp = SimpleDateFormat("yyyy-MM-dd_HH-mm-ss", Locale.getDefault()).format(Date())
            val zipFileName = "COISABOA_Backup_$timestamp.zip"
            
            // Salvar na pasta Downloads
            val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
            val coisaboaDir = File(downloadsDir, "COISABOA")
            
            if (!coisaboaDir.exists()) {
                coisaboaDir.mkdirs()
            }

            val zipFile = File(coisaboaDir, zipFileName)

            // Criar arquivo ZIP
            FileOutputStream(zipFile).use { fos ->
                ZipOutputStream(fos).use { zos ->
                    arquivos.forEach { arquivo ->
                        if (arquivo.isFile) {
                            try {
                                zos.putNextEntry(ZipEntry(arquivo.name))
                                FileInputStream(arquivo).use { input ->
                                    input.copyTo(zos)
                                }
                                zos.closeEntry()
                            } catch (e: Exception) {
                                e.printStackTrace()
                            }
                        } else if (arquivo.isDirectory) {
                            // Se for diretório (como "imagens"), compacta recursivamente
                            adicionarPastaAoZip(arquivo, arquivo.name, zos)
                        }
                    }
                }
            }

            // Verificar se o arquivo foi criado com sucesso
            if (zipFile.exists() && zipFile.length() > 0) {
                Pair(zipFile, zipFile.absolutePath)
            } else {
                if (zipFile.exists()) zipFile.delete()
                Pair(null, "Falha ao criar arquivo ZIP")
            }
        } catch (e: Exception) {
            e.printStackTrace()
            Pair(null, "Erro: ${e.message ?: "Erro desconhecido"}")
        }
    }

    // FUNÇÃO AUXILIAR: Compactar pasta recursivamente
    private fun adicionarPastaAoZip(pasta: File, caminhoBase: String, zos: ZipOutputStream) {
        pasta.listFiles()?.forEach { arquivo ->
            val caminhoZip = if (caminhoBase.isEmpty()) arquivo.name else "$caminhoBase/${arquivo.name}"
            
            if (arquivo.isDirectory) {
                adicionarPastaAoZip(arquivo, caminhoZip, zos)
            } else {
                try {
                    zos.putNextEntry(ZipEntry(caminhoZip))
                    FileInputStream(arquivo).use { input ->
                        input.copyTo(zos)
                    }
                    zos.closeEntry()
                } catch (e: Exception) {
                    e.printStackTrace()
                }
            }
        }
    }

    // FUNÇÃO CORRIGIDA: Compartilhar arquivo
    private fun compartilharArquivo(arquivo: File) {
        try {
            // Verificar se o arquivo existe
            if (!arquivo.exists()) {
                Toast.makeText(this, "Arquivo de backup não encontrado", Toast.LENGTH_LONG).show()
                return
            }

            // Usar FileProvider para compartilhar o arquivo de forma segura
            val uri: Uri = FileProvider.getUriForFile(
                this,
                fileProviderAuthority,
                arquivo
            )
            
            val shareIntent = Intent().apply {
                action = Intent.ACTION_SEND
                type = "application/zip"
                putExtra(Intent.EXTRA_STREAM, uri)
                putExtra(Intent.EXTRA_SUBJECT, "Backup COISABOA - ${SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.getDefault()).format(Date())}")
                putExtra(Intent.EXTRA_TEXT, "Backup do aplicativo COISABOA contendo dados de produtos e vendas.")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            
            // Criar chooser para selecionar app de compartilhamento
            val chooserIntent = Intent.createChooser(shareIntent, "Compartilhar Backup COISABOA")
            
            // Garantir permissões para apps específicos
            val resInfoList = packageManager.queryIntentActivities(chooserIntent, 0)
            for (resolveInfo in resInfoList) {
                val packageName = resolveInfo.activityInfo.packageName
                grantUriPermission(packageName, uri, Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            
            startActivity(chooserIntent)
            
        } catch (e: Exception) {
            Toast.makeText(this, "Erro ao compartilhar: ${e.message}", Toast.LENGTH_LONG).show()
            e.printStackTrace()
        }
    }

    private fun atualizarStatus() {
        val existeBackup = backupManager.backupExiste()
        val backupInfo = backupManager.getBackupInfo()
        
        // Verificar também se o diretório de backup existe
        val backupDir = getBackupDirectory()
        val backupFiles = backupDir?.listFiles()?.isNotEmpty() ?: false
        
        if (existeBackup && backupFiles) {
            tvBackupInfo.text = """
                Status do Backup:
                • Backup disponível: SIM
                • Informação: $backupInfo
                • Diretório: ${backupDir?.name ?: "Não encontrado"}
                • Arquivos: ${backupDir?.listFiles()?.size ?: 0}
                • Pronto para salvar/compartilhar: SIM
            """.trimIndent()
            tvStatus.text = "Backup disponível"
            btnRestaurarBackup.isEnabled = true
            btnSalvarBackup.isEnabled = true
            btnCompartilharBackup.isEnabled = true
        } else {
            tvBackupInfo.text = """
                Status do Backup:
                • Backup disponível: ${if (existeBackup) "SIM" else "NÃO"}
                • Informação: $backupInfo
                • Diretório encontrado: ${if (backupDir != null) "SIM" else "NÃO"}
                • Arquivos: ${backupDir?.listFiles()?.size ?: 0}
                • Recomendação: Faça seu primeiro backup!
            """.trimIndent()
            tvStatus.text = "Nenhum backup disponível"
            btnRestaurarBackup.isEnabled = false
            btnSalvarBackup.isEnabled = false
            btnCompartilharBackup.isEnabled = false
        }
    }

    // CORREÇÃO: Método onDestroy() corrigido
    override fun onDestroy() {
        super.onDestroy()
        // Revogar todas as permissões de URI concedidas (maneira correta)
        revokeUriPermission(null, Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_GRANT_WRITE_URI_PERMISSION)
    }
}