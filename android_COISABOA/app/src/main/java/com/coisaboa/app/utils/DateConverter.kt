package com.coisaboa.app.utils

import androidx.room.TypeConverter
import java.util.*

/**
 * 🔹 DateConverter
 * Converte automaticamente java.util.Date ⇆ Long (timestamp) para o Room.
 */
class DateConverter {
    @TypeConverter
    fun fromTimestamp(value: Long?): Date? = value?.let { Date(it) }

    @TypeConverter
    fun dateToTimestamp(date: Date?): Long? = date?.time
}
