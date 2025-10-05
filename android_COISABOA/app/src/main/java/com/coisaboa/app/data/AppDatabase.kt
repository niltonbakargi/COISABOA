package com.coisaboa.app.data

import androidx.room.Database
import androidx.room.RoomDatabase
import com.coisaboa.app.data.dao.*
import com.coisaboa.app.data.entity.*

@Database(
    entities = [
        ProductEntity::class,
        SaleEntity::class,
        PurchaseEntity::class,
        UserEntity::class
    ],
    version = 1,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun productDao(): ProductDao
    abstract fun saleDao(): SaleDao
    abstract fun purchaseDao(): PurchaseDao
    abstract fun userDao(): UserDao
}