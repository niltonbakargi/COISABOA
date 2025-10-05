package com.coisaboa.app.ui.main

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R
import com.coisaboa.app.databinding.ActivityMainBinding
import com.coisaboa.app.ui.backup.BackupActivity
import com.coisaboa.app.ui.compras.PurchasesActivity
import com.coisaboa.app.ui.login.LoginActivity
import com.coisaboa.app.ui.produtos.ProductsActivity
import com.coisaboa.app.ui.relatorios.ReportsActivity
import com.coisaboa.app.ui.vendas.SalesActivity
import com.coisaboa.app.utils.SessionManager

/**
 * MainActivity
 * Tela principal do aplicativo COISABOA.
 *
 * 🔹 Exibe o menu principal com acesso às seções do sistema.
 * 🔹 Verifica se o usuário está logado ao iniciar.
 * 🔹 Permite logout e redirecionamento seguro.
 */
class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Define o título da barra superior
        supportActionBar?.title = getString(R.string.app_name)

        // Configura todos os botões da tela principal
        binding.apply {
            btnLogin.setOnClickListener {
                Toast.makeText(this@MainActivity, "Abrindo Login…", Toast.LENGTH_SHORT).show()
                startActivity(Intent(this@MainActivity, LoginActivity::class.java))
            }

            btnProdutos.setOnClickListener {
                startActivity(Intent(this@MainActivity, ProductsActivity::class.java))
            }

            btnVendas.setOnClickListener {
                startActivity(Intent(this@MainActivity, SalesActivity::class.java))
            }

            btnCompras.setOnClickListener {
                startActivity(Intent(this@MainActivity, PurchasesActivity::class.java))
            }

            btnRelatorios.setOnClickListener {
                startActivity(Intent(this@MainActivity, ReportsActivity::class.java))
            }

            btnBackup.setOnClickListener {
                startActivity(Intent(this@MainActivity, BackupActivity::class.java))
            }

            // 🚪 Botão de Logout
            btnLogout.setOnClickListener {
                SessionManager.logout(this@MainActivity)
                Toast.makeText(
                    this@MainActivity,
                    "Sessão encerrada com sucesso.",
                    Toast.LENGTH_SHORT
                ).show()
                startActivity(Intent(this@MainActivity, LoginActivity::class.java))
                finish()
            }
        }
    }

    override fun onStart() {
        super.onStart()
        // 🔒 Verifica se há sessão ativa
        if (!SessionManager.isLogged(this)) {
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
        }
    }
}
