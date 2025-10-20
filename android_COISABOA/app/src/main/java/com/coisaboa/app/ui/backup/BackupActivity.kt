package com.coisaboa.app.ui.backup

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.R
import com.coisaboa.app.data.backup.BackupDataManager
import kotlinx.coroutines.launch
import java.io.File
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

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_backup)
        supportActionBar?.title = "Backup de Dados"

        // Inicializar backup manager
        backupManager = BackupDataManager(this)

        // Inicializar views manualmente
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
                    tvStatus.text = "Backup concluido!"
                    Toast.makeText(this@BackupActivity, "Backup criado com sucesso!", Toast.LENGTH_LONG).show()
                } else {
                    tvStatus.text = "Falha ao criar backup"
                    Toast.makeText(this@BackupActivity, "Erro ao criar backup", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                tvStatus.text = "Erro: ${e.message}"
                Toast.makeText(this@BackupActivity, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                btnFazerBackup.isEnabled = true
                atualizarStatus()
            }
        }
    }

    private fun restaurarBackup() {
        if (!backupManager.backupExiste()) {
            Toast.makeText(this, "Nenhum backup disponivel para restaurar", Toast.LENGTH_LONG).show()
            return
        }
        Toast.makeText(this, "Funcionalidade de restauracao em desenvolvimento", Toast.LENGTH_LONG).show()
    }

    // NOVA FUNÇÃO: Salvar backup compactado no aparelho
    private fun salvarBackupNoAparelho() {
        if (!backupManager.backupExiste()) {
            Toast.makeText(this, "Faça um backup primeiro", Toast.LENGTH_LONG).show()
            return
        }

        lifecycleScope.launch {
            btnSalvarBackup.isEnabled = false
            tvStatus.text = "Compactando backup..."

            try {
                val zipFile = criarBackupCompactado()

                if (zipFile != null) {
                    tvStatus.text = "Backup salvo!"
                    Toast.makeText(this@BackupActivity, "Backup salvo: ${zipFile.name}", Toast.LENGTH_LONG).show()
                } else {
                    tvStatus.text = "Erro ao salvar"
                    Toast.makeText(this@BackupActivity, "Erro ao salvar backup", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                tvStatus.text = "Erro: ${e.message}"
                Toast.makeText(this@BackupActivity, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                btnSalvarBackup.isEnabled = true
            }
        }
    }

    // NOVA FUNÇÃO: Compartilhar backup
    private fun compartilharBackup() {
        if (!backupManager.backupExiste()) {
            Toast.makeText(this, "Faça um backup primeiro", Toast.LENGTH_LONG).show()
            return
        }

        lifecycleScope.launch {
            btnCompartilharBackup.isEnabled = false
            tvStatus.text = "Preparando compartilhamento..."

            try {
                val zipFile = criarBackupCompactado()
                if (zipFile != null) {
                    compartilharArquivo(zipFile)
                    tvStatus.text = "Pronto para compartilhar!"
                } else {
                    tvStatus.text = "Erro ao preparar"
                    Toast.makeText(this@BackupActivity, "Erro ao preparar backup", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                tvStatus.text = "Erro: ${e.message}"
                Toast.makeText(this@BackupActivity, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                btnCompartilharBackup.isEnabled = true
            }
        }
    }

    // FUNÇÃO CORRIGIDA: Agora retorna File? em vez de Boolean
    private fun criarBackupCompactado(): File? {
        return try {
            val backupDir = File(filesDir, "backup")
            if (!backupDir.exists() || backupDir.listFiles()?.isEmpty() != false) {
                return null
            }

            // Criar nome com timestamp
            val timestamp = SimpleDateFormat("yyyy-MM-dd_HH-mm-ss", Locale.getDefault()).format(Date())
            val zipFileName = "COISABOA_Backup_$timestamp.zip"
            
            // Salvar na pasta Downloads
            val downloadsDir = getExternalFilesDir(null) ?: filesDir
            val zipFile = File(downloadsDir, zipFileName)

            // Criar ZIP
            FileOutputStream(zipFile).use { fos ->
                ZipOutputStream(fos).use { zos ->
                    adicionarPastaAoZip(backupDir, "", zos)
                }
            }

            zipFile // Retorna o arquivo criado
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }

    private fun adicionarPastaAoZip(pasta: File, caminhoBase: String, zos: ZipOutputStream) {
        pasta.listFiles()?.forEach { arquivo ->
            val caminhoZip = if (caminhoBase.isEmpty()) arquivo.name else "$caminhoBase/${arquivo.name}"
            
            if (arquivo.isDirectory) {
                adicionarPastaAoZip(arquivo, caminhoZip, zos)
            } else {
                try {
                    zos.putNextEntry(ZipEntry(caminhoZip))
                    arquivo.inputStream().use { it.copyTo(zos) }
                    zos.closeEntry()
                } catch (e: Exception) {
                    // Ignorar erro em arquivo individual
                }
            }
        }
    }

    private fun compartilharArquivo(arquivo: File) {
        try {
            val uri = Uri.fromFile(arquivo)
            
            val shareIntent = Intent().apply {
                action = Intent.ACTION_SEND
                type = "application/zip"
                putExtra(Intent.EXTRA_STREAM, uri)
                putExtra(Intent.EXTRA_SUBJECT, "Backup COISABOA")
                putExtra(Intent.EXTRA_TEXT, "Backup do aplicativo COISABOA - ${Date()}")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            
            startActivity(Intent.createChooser(shareIntent, "Compartilhar Backup COISABOA"))
            
        } catch (e: Exception) {
            Toast.makeText(this, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }

    private fun atualizarStatus() {
        val existeBackup = backupManager.backupExiste()
        val backupInfo = backupManager.getBackupInfo()
        
        if (existeBackup) {
            tvBackupInfo.text = """
                Status do Backup:
                • Backup disponivel: SIM
                • Informacao: $backupInfo
                • Pronto para restaurar: SIM
            """.trimIndent()
            btnRestaurarBackup.isEnabled = true
            btnSalvarBackup.isEnabled = true
            btnCompartilharBackup.isEnabled = true
        } else {
            tvBackupInfo.text = """
                Status do Backup:
                • Backup disponivel: NAO
                • Informacao: $backupInfo
                • Recomendacao: Faca seu primeiro backup!
            """.trimIndent()
            btnRestaurarBackup.isEnabled = false
            btnSalvarBackup.isEnabled = false
            btnCompartilharBackup.isEnabled = false
        }
    }
}