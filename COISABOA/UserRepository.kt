package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.UserDao
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.utils.PasswordUtils

class UserRepository(private val dao: UserDao) {

    // Cria admin/admin@1234 na 1ª vez, se não existir
    suspend fun ensureDefaultAdmin() {
        val existing = dao.findByLogin("admin")
        if (existing == null) {
            val hash = PasswordUtils.sha256("1234")
            dao.insert(
                UserEntity(
                    nome = "Administrador",
                    login = "admin",
                    senhaHash = hash,
                    perfil = "ADMIN"
                )
            )
        }
    }

    suspend fun login(login: String, plainPassword: String): UserEntity? {
        val user = dao.findByLogin(login) ?: return null
        val hash = PasswordUtils.sha256(plainPassword)
        return if (user.senhaHash == hash) user else null
    }
}
