package com.coisaboa.app.ui.dashboard

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityDashboardBinding
import com.coisaboa.app.ui.main.MainActivity

class DashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDashboardBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Painel do Sistema"

        // Botões principais
        binding.btnComprar.setOnClickListener { /* TODO: Implementar */ }
        binding.btnVender.setOnClickListener { /* TODO: Implementar */ }
        binding.btnRelatorios.setOnClickListener { /* TODO: Implementar */ }
        binding.btnGerenciar.setOnClickListener { /* TODO: Implementar */ }

        // Backup
        binding.btnBackup.setOnClickListener {
            // TODO: implementar rotina de backup
        }

        // Sair
        binding.btnSair.setOnClickListener {
            val intent = Intent(this, MainActivity::class.java)
            startActivity(intent)
            finish()
        }
    }
}
