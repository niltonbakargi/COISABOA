package com.coisaboa.app.config

import android.content.Context
import androidx.room.Room
import com.coisaboa.app.data.AppDatabase

/**
 * 🔹 DatabaseProvider
 * Responsável por criar e fornecer a instância única do banco de dados Room.
 */
object DatabaseProvider {

    // Guarda a instância do banco
    @Volatile
    private var INSTANCE: AppDatabase? = null

    /**
     * Retorna a instância do banco de dados (singleton)
     */
    fun get(context: Context): AppDatabase {
        return INSTANCE ?: synchronized(this) {
            val instance = Room.databaseBuilder(
                context.applicationContext,
                AppDatabase::class.java,
                "coisaboa.db"
            )
                .fallbackToDestructiveMigration() // recria se versão mudar
                .build()
            INSTANCE = instance
            instance
        }
    }
}
