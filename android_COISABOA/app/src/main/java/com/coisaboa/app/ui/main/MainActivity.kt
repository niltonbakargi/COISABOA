package com.coisaboa.app.ui.main

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityMainBinding
import com.coisaboa.app.ui.login.LoginActivity

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Tela Inicial"

        // 👉 Botão "Entrar no Sistema" vai para LoginActivity
        binding.btnEntrar.setOnClickListener {
            val intent = Intent(this, LoginActivity::class.java)
            startActivity(intent)
        }

        // 👉 Botão "Sair" fecha o app
        binding.btnLogout.setOnClickListener {
            finishAffinity()
        }
    }
}
