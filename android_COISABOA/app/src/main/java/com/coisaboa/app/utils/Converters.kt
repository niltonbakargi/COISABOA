package com.coisaboa.app.utils

import androidx.room.TypeConverter
import java.util.*

/**
 * Converte automaticamente Date ↔ Long (timestamp)
 * para o Room armazenar no SQLite.
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
