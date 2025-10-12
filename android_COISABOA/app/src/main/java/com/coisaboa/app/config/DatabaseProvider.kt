package com.coisaboa.app.config

import android.content.Context
import androidx.room.Room
import androidx.room.RoomDatabase
import com.coisaboa.app.data.AppDatabase

/**
 * 🧩 DatabaseProvider
 *
 * Responsável por fornecer uma instância única (Singleton) do banco de dados Room.
 * Essa abordagem garante que apenas uma conexão com o banco seja aberta
 * durante todo o ciclo de vida do aplicativo.
 */
object DatabaseProvider {

    // 🔒 Instância única do banco (Thread-safe)
    @Volatile
    private var INSTANCE: AppDatabase? = null

    /**
     * Retorna a instância do banco de dados.
     * Caso ainda não exista, cria uma nova.
     */
    fun get(context: Context): AppDatabase {
        // Se já houver instância, retorna imediatamente
        val tempInstance = INSTANCE
        if (tempInstance != null) return tempInstance

        // Caso contrário, cria de forma sincronizada
        synchronized(this) {
            val instance = Room.databaseBuilder(
                context.applicationContext,
                AppDatabase::class.java,
                "coisaboa.db" // 📦 Nome do arquivo do banco local
            )
                // ⚠️ Força recriação do banco ao mudar versão
                // Ideal durante desenvolvimento, pois apaga dados antigos
                .fallbackToDestructiveMigration() // já tem, mas garante reset com versão nova

                // 🧱 Ativa o modo Write-Ahead Logging (melhor desempenho)
                .setJournalMode(RoomDatabase.JournalMode.WRITE_AHEAD_LOGGING)

                // 🔧 (Opcional futuro) .addMigrations() — usar quando quiser preservar dados
                .build()

            INSTANCE = instance
            return instance
        }
    }
}
