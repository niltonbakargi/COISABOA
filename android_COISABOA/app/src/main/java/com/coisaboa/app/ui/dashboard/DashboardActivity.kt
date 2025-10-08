package com.coisaboa.app.ui.dashboard

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityDashboardBinding
import com.coisaboa.app.ui.main.MainActivity
import com.coisaboa.app.ui.compras.ComprarActivity   // ✅ Módulo de compras
import com.coisaboa.app.ui.vendas.VenderActivity    // ✅ Módulo de vendas

class DashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDashboardBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Painel do Sistema"

        // 🛒 Botão COMPRAR — abre a tela de Nova Compra
        binding.btnComprar.setOnClickListener {
            val intent = Intent(this, ComprarActivity::class.java)
            startActivity(intent)
        }

        // 🏷️ Botão VENDER — abre a tela de Nova Venda
        binding.btnVender.setOnClickListener {
            val intent = Intent(this, VenderActivity::class.java)
            startActivity(intent)
        }

        // 📊 Botão RELATÓRIOS — (em breve)
        binding.btnRelatorios.setOnClickListener {
            // TODO: Implementar tela de relatórios
        }

        // ⚙️ Botão GERENCIAR — (em breve)
        binding.btnGerenciar.setOnClickListener {
            // TODO: Implementar tela de gerenciamento
        }

        // 💾 Botão BACKUP — (em breve)
        binding.btnBackup.setOnClickListener {
            // TODO: Implementar rotina de backup
        }

        // 🚪 Botão SAIR — retorna para a tela inicial
        binding.btnSair.setOnClickListener {
            val intent = Intent(this, MainActivity::class.java)
            startActivity(intent)
            finish()
        }
    }
}
