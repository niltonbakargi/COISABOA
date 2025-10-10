package com.coisaboa.app.data

import androidx.room.Database
import androidx.room.RoomDatabase
import androidx.room.TypeConverters
import com.coisaboa.app.data.dao.*
import com.coisaboa.app.data.entity.*
import com.coisaboa.app.utils.Converters

/**
 * Banco de dados principal do COISABOA.
 * Inclui todas as entidades e DAOs usados localmente.
 */
@Database(
    entities = [
        UserEntity::class,
        ProductEntity::class,
        PurchaseEntity::class
    ],
    version = 1,
    exportSchema = false
)
@TypeConverters(Converters::class)
abstract class AppDatabase : RoomDatabase() {

    // 🔗 DAOs disponíveis para uso na aplicação
    abstract fun userDao(): UserDao
    abstract fun productDao(): ProductDao
    abstract fun purchaseDao(): PurchaseDao
}
