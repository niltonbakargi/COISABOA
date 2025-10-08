package com.coisaboa.app.ui.login

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityLoginBinding
import com.coisaboa.app.ui.dashboard.DashboardActivity

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)
        supportActionBar?.title = "Login"

        val prefs = getSharedPreferences("usuarios", Context.MODE_PRIVATE)

        // Verifica se já há um usuário logado
        val usuarioLogado = prefs.getString("usuario_logado", null)
        if (usuarioLogado != null) {
            startActivity(Intent(this, DashboardActivity::class.java))
            finish()
        }

        // Botão "Entrar"
        binding.btnDoLogin.setOnClickListener {
            val user = binding.etUser.text.toString().trim()
            val pass = binding.etPass.text.toString().trim()

            val senhaSalva = prefs.getString(user, null)
            when {
                senhaSalva == null ->
                    Toast.makeText(this, "Usuário não encontrado", Toast.LENGTH_SHORT).show()
                senhaSalva == pass -> {
                    prefs.edit().putString("usuario_logado", user).apply()
                    Toast.makeText(this, "Bem-vindo, $user!", Toast.LENGTH_SHORT).show()
                    startActivity(Intent(this, DashboardActivity::class.java))
                    finish()
                }
                else ->
                    Toast.makeText(this, "Senha incorreta", Toast.LENGTH_SHORT).show()
            }
        }

        // Botão "Cadastrar"
        binding.btnRegister.setOnClickListener {
            val user = binding.etUser.text.toString().trim()
            val pass = binding.etPass.text.toString().trim()

            if (user.isEmpty() || pass.isEmpty()) {
                Toast.makeText(this, "Preencha usuário e senha", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            if (prefs.contains(user)) {
                Toast.makeText(this, "Usuário já existe", Toast.LENGTH_SHORT).show()
            } else {
                prefs.edit().putString(user, pass).apply()
                Toast.makeText(this, "Usuário $user cadastrado!", Toast.LENGTH_SHORT).show()
            }
        }
    }
}
