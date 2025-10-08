package com.coisaboa.app.ui.dashboard

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityDashboardBinding
import com.coisaboa.app.ui.compras.ComprarActivity
import com.coisaboa.app.ui.vendas.VenderActivity
import com.coisaboa.app.ui.gerenciar.GerenciarActivity
import com.coisaboa.app.ui.relatorios.RelatoriosActivity
import com.coisaboa.app.ui.backup.BackupActivity
import com.coisaboa.app.ui.main.MainActivity

class DashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDashboardBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Painel de Controle"

        // 🛒 Botão "Comprar"
        binding.btnComprar.setOnClickListener {
            startActivity(Intent(this, ComprarActivity::class.java))
        }

        // 🏷️ Botão "Vender"
        binding.btnVender.setOnClickListener {
            startActivity(Intent(this, VenderActivity::class.java))
        }

        // ⚙️ Botão "Gerenciar Estoque"
        binding.btnGerenciar.setOnClickListener {
            startActivity(Intent(this, GerenciarActivity::class.java))
        }

        // 📊 Botão "Relatórios"
        binding.btnRelatorios.setOnClickListener {
            startActivity(Intent(this, RelatoriosActivity::class.java))
        }

        // ☁️ Botão "Fazer Backup"
        binding.btnBackup.setOnClickListener {
            try {
                val intent = Intent(this, BackupActivity::class.java)
                startActivity(intent)
            } catch (e: Exception) {
                e.printStackTrace()
                android.widget.Toast.makeText(this, "Erro ao abrir backup: ${e.message}", android.widget.Toast.LENGTH_LONG).show()
            }
        }

        // 🚪 Botão "Sair"
        binding.btnSair.setOnClickListener {
            val intent = Intent(this, MainActivity::class.java)
            intent.flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_NEW_TASK
            startActivity(intent)
            finish()
        }
    }
}
