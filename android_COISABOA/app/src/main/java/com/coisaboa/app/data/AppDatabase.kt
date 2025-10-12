package com.coisaboa.app.data

import androidx.room.Database
import androidx.room.RoomDatabase
import androidx.room.TypeConverters
import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.dao.PurchaseDao
import com.coisaboa.app.data.dao.UserDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.utils.Converters

/**
 * ✅ Banco de dados principal do aplicativo COISABOA.
 * Inclui todas as entidades locais e converte automaticamente datas.
 */
@Database(
    entities = [
        UserEntity::class,
        ProductEntity::class,
        PurchaseEntity::class
    ],
    version = 9, // ✅ aumente sempre que alterar entidades
    exportSchema = false
)
@TypeConverters(Converters::class) // 🔄 habilita conversão de Date <-> Long
abstract class AppDatabase : RoomDatabase() {

    // 🔗 Acesso aos DAOs do sistema
    abstract fun userDao(): UserDao
    abstract fun productDao(): ProductDao
    abstract fun purchaseDao(): PurchaseDao
}
