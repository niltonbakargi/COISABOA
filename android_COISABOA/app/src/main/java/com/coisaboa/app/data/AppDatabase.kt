package com.coisaboa.app.data

import androidx.room.Database
import androidx.room.RoomDatabase
import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.dao.UserDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.UserEntity

@Database(
    entities = [UserEntity::class, ProductEntity::class],
    version = 2, // 🔹 aumente a versão
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun userDao(): UserDao
    abstract fun productDao(): ProductDao
}
