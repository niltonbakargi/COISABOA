package com.coisaboa.app.ui.login

import android.app.Application
import androidx.lifecycle.*
import com.coisaboa.app.config.DatabaseProvider
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.data.repository.UserRepository
import kotlinx.coroutines.launch

class LoginViewModel(app: Application) : AndroidViewModel(app) {

    private val repo = UserRepository(DatabaseProvider.get(app).userDao())

    private val _loading = MutableLiveData(false)
    val loading: LiveData<Boolean> = _loading

    private val _error = MutableLiveData<String?>(null)
    val error: LiveData<String?> = _error

    private val _success = MutableLiveData<UserEntity?>(null)
    val success: LiveData<UserEntity?> = _success

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
}