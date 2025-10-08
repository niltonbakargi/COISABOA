package com.coisaboa.app.ui.backup

import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityBackupBinding
import java.io.File
import java.io.FileOutputStream
import java.util.zip.ZipEntry
import java.util.zip.ZipOutputStream

class BackupActivity : AppCompatActivity() {

    private lateinit var binding: ActivityBackupBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityBackupBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Backup Manual"

        // 🟦 Quando clicar em "Fazer Backup"
        binding.btnBackup.setOnClickListener {
            try {
                val backupDir = File(getExternalFilesDir(null), "backups")
                if (!backupDir.exists()) backupDir.mkdirs()

                val backupName = "COISABOA_BACKUP_${System.currentTimeMillis()}.zip"
                val backupPath = File(backupDir, backupName)

                binding.tvStatus.text = "⏳ Gerando backup..."
                criarBackupZip(backupPath)

                binding.tvStatus.text = "✅ Backup criado com sucesso!\n${backupPath.absolutePath}"
                Toast.makeText(this, "Backup concluído!", Toast.LENGTH_SHORT).show()

            } catch (e: Exception) {
                binding.tvStatus.text = "❌ Erro ao criar backup: ${e.message}"
                Toast.makeText(this, "Erro: ${e.message}", Toast.LENGTH_LONG).show()
            }
        }

        // 🔙 Botão Voltar
        binding.btnVoltar.setOnClickListener {
            finish()
        }
    }

    /**
     * Função para criar um arquivo ZIP simulando o backup
     */
    private fun criarBackupZip(destino: File) {
        val zipOut = ZipOutputStream(FileOutputStream(destino))

        // Simula o backup de um "banco de dados"
        val bancoSimulado = File(getExternalFilesDir(null), "coisaboa.db")
        bancoSimulado.writeText("DADOS SIMULADOS DO BANCO DE DADOS COISABOA")
        adicionarArquivoZip(zipOut, bancoSimulado, "banco/coisaboa.db")

        // Simula uma pasta de imagens
        val imagensDir = File(getExternalFilesDir(null), "uploads")
        if (!imagensDir.exists()) imagensDir.mkdirs()

        val imagemExemplo = File(imagensDir, "exemplo.txt")
        imagemExemplo.writeText("imagem_fake")
        adicionarArquivoZip(zipOut, imagemExemplo, "uploads/exemplo.txt")

        // Adiciona informações do backup
        val info = """
            Backup do sistema COISABOA
            Data: ${java.util.Date()}
            Itens incluídos:
            - Banco de dados (simulado)
            - Pasta uploads (simulada)
        """.trimIndent()
        val infoFile = File(getExternalFilesDir(null), "info_backup.txt")
        infoFile.writeText(info)
        adicionarArquivoZip(zipOut, infoFile, "info/info_backup.txt")

        zipOut.close()
    }

    /**
     * Adiciona um arquivo ao ZIP
     */
    private fun adicionarArquivoZip(zipOut: ZipOutputStream, arquivo: File, nome: String) {
        val buffer = ByteArray(1024)
        val inputStream = arquivo.inputStream()
        zipOut.putNextEntry(ZipEntry(nome))
        var length: Int
        while (inputStream.read(buffer).also { length = it } > 0) {
            zipOut.write(buffer, 0, length)
        }
        zipOut.closeEntry()
        inputStream.close()
    }
}
