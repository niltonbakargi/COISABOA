package com.coisaboa.app.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "users")
data class UserEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val nome: String,
    val login: String,
    val senhaHash: String,
    val perfil: String = "USER"
)