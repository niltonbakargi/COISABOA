package com.coisaboa.app.ui.backup

import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.coisaboa.app.R
import com.coisaboa.app.data.backup.BackupDataManager
import kotlinx.coroutines.launch

class BackupActivity : AppCompatActivity() {

    private lateinit var backupManager: BackupDataManager
    private lateinit var btnFazerBackup: Button
    private lateinit var btnRestaurarBackup: Button
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

        btnVoltar.setOnClickListener {
            finish()
        }
    }

    private fun fazerBackup() {
        lifecycleScope.launch {
            btnFazerBackup.isEnabled = false
            tvStatus.text = "Criando backup..."

            try {
                val success = backupManager.fazerBackupCompleto()

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
        if (!backupManager.existeBackupDisponivel()) {
            Toast.makeText(this, "Nenhum backup disponivel para restaurar", Toast.LENGTH_LONG).show()
            return
        }
        Toast.makeText(this, "Funcionalidade de restauracao em desenvolvimento", Toast.LENGTH_LONG).show()
    }

    private fun atualizarStatus() {
        val existeBackup = backupManager.existeBackupDisponivel()
        val backupInfo = backupManager.getBackupInfo()
        
        if (existeBackup) {
            tvBackupInfo.text = """
                Status do Backup:
                • Backup disponivel: SIM
                • Informacao: $backupInfo
                • Pronto para restaurar: SIM
            """.trimIndent()
            btnRestaurarBackup.isEnabled = true
        } else {
            tvBackupInfo.text = """
                Status do Backup:
                • Backup disponivel: NAO
                • Informacao: $backupInfo
                • Recomendacao: Faca seu primeiro backup!
            """.trimIndent()
            btnRestaurarBackup.isEnabled = false
        }
    }
}
