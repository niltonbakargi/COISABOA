package com.coisaboa.app.data.dao

import androidx.room.*
import com.coisaboa.app.data.entity.ProductEntity

@Dao
interface ProductDao {

    @Query("SELECT * FROM products")
    suspend fun getAll(): List<ProductEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(product: ProductEntity)

    @Delete
    suspend fun delete(product: ProductEntity)
}
