package com.coisaboa.app.ui.main

import android.content.Context
import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityMainBinding
import com.coisaboa.app.ui.login.LoginActivity

/**
 * Tela principal do aplicativo COISABOA.
 * Exibe uma saudação ao usuário e permite logout.
 */
class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Tela Principal"

        val prefs = getSharedPreferences("usuarios", Context.MODE_PRIVATE)
        val user = prefs.getString("usuario_logado", "Usuário")

        // Exibe saudação
        // binding.tvHello.text = "Olá, COISABOA!"

        // Botão de logout
        binding.btnLogout.setOnClickListener {
            prefs.edit().remove("usuario_logado").apply()
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
        }
    }
}
