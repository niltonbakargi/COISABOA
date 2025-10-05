package com.coisaboa.app.data.dao

import androidx.room.*
import com.coisaboa.app.data.entity.SaleEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface SaleDao {
    @Query("SELECT * FROM sales ORDER BY id DESC")
    fun getAll(): Flow<List<SaleEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(entity: SaleEntity): Long

    @Delete
    suspend fun delete(entity: SaleEntity)
}