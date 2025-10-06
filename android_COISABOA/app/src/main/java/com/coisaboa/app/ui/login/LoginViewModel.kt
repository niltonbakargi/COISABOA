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

class LoginViewModel(app: Application) : AndroidViewModel(app) {

    // ✅ Usa o DatabaseProvider que você já tem
    private val repo = UserRepository(DatabaseProvider.get(app).userDao())

    private val _loading = MutableLiveData(false)
    val loading: LiveData<Boolean> = _loading

    private val _error = MutableLiveData<String?>(null)
    val error: LiveData<String?> = _error

    private val _success = MutableLiveData<UserEntity?>(null)
    val success: LiveData<UserEntity?> = _success

    private val _registrationSuccess = MutableLiveData<Boolean>(false)
    val registrationSuccess: LiveData<Boolean> = _registrationSuccess

    fun login(user: String, pass: String) {
        viewModelScope.launch {
            _loading.value = true
            _error.value = null
            try {
                repo.ensureDefaultAdmin()
                val u = repo.login(user.trim(), pass)
                if (u != null) _success.value = u
                else _error.value = "Usuário ou senha inválidos."
            } catch (e: Exception) {
                _error.value = "Falha no login: ${e.message}"
            } finally {
                _loading.value = false
            }
        }
    }

    fun register(username: String, password: String) {
        viewModelScope.launch {
            _loading.value = true
            _error.value = null
            try {
                val created = repo.register(username, password, username)
                if (created) {
                    _registrationSuccess.value = true
                } else {
                    _error.value = "Usuário já existe."
                }
            } catch (e: Exception) {
                _error.value = "Erro no cadastro: ${e.message}"
            } finally {
                _loading.value = false
            }
        }
    }

    fun clearMessages() {
        _error.value = null
        _registrationSuccess.value = false
    }
}
