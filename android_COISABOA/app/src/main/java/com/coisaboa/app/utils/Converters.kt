package com.coisaboa.app.utils

import androidx.room.TypeConverter
import java.util.Date

/**
 * Converte objetos Date para Long (timestamp) e vice-versa
 * para permitir que o Room armazene datas no banco local.
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
