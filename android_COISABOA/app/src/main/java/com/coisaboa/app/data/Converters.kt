package com.coisaboa.app.data

import androidx.room.TypeConverter
import java.util.*

/**
 * Converte tipos não suportados diretamente pelo SQLite,
 * como Date <-> Long.
 */
class Converters {

    @TypeConverter
    fun fromTimestamp(value: Long?): Date? {
        return value?.let { Date(it) }
    }

    @TypeConverter
    fun dateToTimestamp(date: Date?): Long? {
        return date?.time
    }
}
