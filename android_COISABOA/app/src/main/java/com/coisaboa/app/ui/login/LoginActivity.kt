package com.coisaboa.app.ui.login

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.databinding.ActivityLoginBinding
import com.coisaboa.app.ui.main.MainActivity
import com.coisaboa.app.utils.SessionManager

/**
 * LoginActivity
 * Tela de autenticação do aplicativo COISABOA.
 *
 * 🔹 Usa LoginViewModel para processar o login.
 * 🔹 Usa SessionManager para salvar a sessão do usuário.
 * 🔹 Evita o loop de login → main → login.
 */
class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding
    private val vm: LoginViewModel by viewModels()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = "Login - COISABOA"

        // 🔸 Se o usuário já estiver logado, vai direto pra tela principal
        if (SessionManager.isLogged(this)) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }

        // Observadores do ViewModel
        vm.loading.observe(this) { isLoading ->
            binding.btnDoLogin.isEnabled = !isLoading
        }

        vm.error.observe(this) { errorMsg ->
            errorMsg?.let {
                Toast.makeText(this, it, Toast.LENGTH_SHORT).show()
            }
        }

        vm.success.observe(this) { user ->
            user?.let {
                // Salva sessão local usando SessionManager
                SessionManager.login(this, it.id, it.nome)

                Toast.makeText(this, "Bem-vindo(a), ${it.nome}!", Toast.LENGTH_SHORT).show()

                // Redireciona para a tela principal
                startActivity(Intent(this, MainActivity::class.java))
                finish()
            }
        }

        // Clique do botão de login
        binding.btnDoLogin.setOnClickListener {
            val user = binding.etUser.text?.toString()?.trim().orEmpty()
            val pass = binding.etPass.text?.toString()?.trim().orEmpty()

            if (user.isBlank() || pass.isBlank()) {
                Toast.makeText(this, "Preencha usuário e senha.", Toast.LENGTH_SHORT).show()
            } else {
                // Aciona o login pelo ViewModel
                vm.login(user, pass)
            }
        }
    }
}
