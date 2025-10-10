package com.coisaboa.app.config

import android.content.Context
import androidx.room.Room
import com.coisaboa.app.data.AppDatabase

/**
 * Singleton responsável por fornecer a instância única do banco Room.
 */
object DatabaseProvider {

    // Guarda a instância única do banco
    @Volatile
    private var INSTANCE: AppDatabase? = null

    /**
     * Retorna a instância do banco de dados.
     * Cria se ainda não existir.
     */
    fun get(context: Context): AppDatabase {
        return INSTANCE ?: synchronized(this) {
            val instance = Room.databaseBuilder(
                context.applicationContext,
                AppDatabase::class.java,
                "coisaboa.db"
            )
                .fallbackToDestructiveMigration() // recria o banco se mudar versão
                .build()
            INSTANCE = instance
            instance
        }
    }
}
