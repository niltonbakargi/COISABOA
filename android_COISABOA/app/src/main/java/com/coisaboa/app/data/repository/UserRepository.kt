package com.coisaboa.app.data.repository

import com.coisaboa.app.data.dao.UserDao
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.utils.PasswordUtils

/**
 * Repositório responsável por manipular dados de usuários no banco local.
 * Controla login, cadastro e criação do usuário administrador padrão.
 */
class UserRepository(private val dao: UserDao) {

    /**
     * Faz login verificando usuário e senha.
     * Retorna o UserEntity se for válido, ou null se falhar.
     */
    suspend fun login(username: String, password: String): UserEntity? {
        val user = dao.findByUsername(username)
        return if (user != null && PasswordUtils.verifyPassword(password, user.passwordHash)) {
            user
        } else {
            null
        }
    }

    /**
     * Registra um novo usuário se o username ainda não existir.
     * Retorna true se o registro foi bem-sucedido, false se já existe.
     */
    suspend fun register(username: String, password: String, nome: String): Boolean {
        val exists = dao.findByUsername(username)
        if (exists != null) return false

        val hashed = PasswordUtils.hashPassword(password)
        val newUser = UserEntity(
            username = username,
            passwordHash = hashed,
            nome = nome
        )

        dao.insert(newUser)
        return true
    }

    /**
     * Cria o usuário administrador padrão caso não exista.
     * Útil na primeira inicialização do aplicativo.
     */
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
