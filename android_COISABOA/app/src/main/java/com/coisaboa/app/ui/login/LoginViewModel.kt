package com.coisaboa.app.ui.login

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.viewModelScope
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.data.repository.UserRepository
import kotlinx.coroutines.launch

/**
 * ViewModel responsável pela autenticação e cadastro de usuários.
 * Funciona totalmente offline com Room Database (UserDao / UserRepository).
 */
class LoginViewModel(app: Application) : AndroidViewModel(app) {

    // 🔗 Instancia o repositório com base no DatabaseProvider local
    private val repository = UserRepository(DatabaseProvider.get(app).userDao())

    // --- LiveData para controle de estado da interface ---
    private val _loading = MutableLiveData(false)
    val loading: LiveData<Boolean> = _loading

    private val _error = MutableLiveData<String?>(null)
    val error: LiveData<String?> = _error

    private val _userLogged = MutableLiveData<UserEntity?>(null)
    val userLogged: LiveData<UserEntity?> = _userLogged

    private val _registrationSuccess = MutableLiveData(false)
    val registrationSuccess: LiveData<Boolean> = _registrationSuccess

    /**
     * Realiza o login do usuário.
     * Caso o banco esteja vazio, cria o admin padrão.
     */
    fun login(username: String, password: String) {
        viewModelScope.launch {
            _loading.value = true
            _error.value = null

            try {
                // Garante que o admin exista
                repository.ensureDefaultAdmin()

                // Faz o login
                val user = repository.login(username.trim(), password)

                if (user != null) {
                    _userLogged.value = user
                } else {
                    _error.value = "Usuário ou senha inválidos."
                }

            } catch (e: Exception) {
                _error.value = "Falha no login: ${e.message ?: "Erro desconhecido"}"
            } finally {
                _loading.value = false
            }
        }
    }

    /**
     * Realiza o cadastro de um novo usuário.
     * Caso o username já exista, retorna erro.
     */
    fun register(username: String, password: String) {
        viewModelScope.launch {
            _loading.value = true
            _error.value = null

            try {
                val created = repository.register(username.trim(), password, username.trim())

                if (created) {
                    _registrationSuccess.value = true
                } else {
                    _error.value = "Usuário já existe."
                }

            } catch (e: Exception) {
                _error.value = "Erro no cadastro: ${e.message ?: "Falha desconhecida"}"
            } finally {
                _loading.value = false
            }
        }
    }

    /**
     * Limpa mensagens e estados de erro/sucesso.
     */
    fun clearMessages() {
        _error.value = null
        _registrationSuccess.value = false
    }
}
