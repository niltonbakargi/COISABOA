package com.coisaboa.app.data.dao

import androidx.room.*
import com.coisaboa.app.data.entity.PurchaseEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface PurchaseDao {
    @Query("SELECT * FROM purchases ORDER BY id DESC")
    fun getAll(): Flow<List<PurchaseEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(entity: PurchaseEntity): Long

    @Delete
    suspend fun delete(entity: PurchaseEntity)
}