package com.coisaboa.app.config

import android.content.Context
import androidx.room.Room
import com.coisaboa.app.data.AppDatabase

object DatabaseProvider {
    @Volatile
    private var INSTANCE: AppDatabase? = null

    fun get(context: Context): AppDatabase {
        return INSTANCE ?: synchronized(this) {
            val i = Room.databaseBuilder(
                context.applicationContext,
                AppDatabase::class.java,
                "coisaboa.db"
            ).fallbackToDestructiveMigration().build()
            INSTANCE = i
            i
        }
    }
}