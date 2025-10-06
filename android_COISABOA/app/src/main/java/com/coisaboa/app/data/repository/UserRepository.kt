package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.UserDao
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.utils.PasswordUtils

class UserRepository(private val dao: UserDao) {

    suspend fun login(username: String, password: String): UserEntity? {
        val user = dao.findByUsername(username)
        return if (user != null && PasswordUtils.verifyPassword(password, user.passwordHash)) user else null
    }

    suspend fun register(username: String, password: String, nome: String): Boolean {
        val exists = dao.findByUsername(username)
        if (exists != null) return false

        val newUser = UserEntity(
            username = username,
            passwordHash = PasswordUtils.hashPassword(password),
            nome = nome
        )
        dao.insert(newUser)
        return true
    }

    suspend fun ensureDefaultAdmin() {
        val admin = dao.findByUsername("admin")
        if (admin == null) {
            val adminUser = UserEntity(
                username = "admin",
                passwordHash = PasswordUtils.hashPassword("admin"),
                nome = "Administrador"
            )
            dao.insert(adminUser)
        }
    }
}
