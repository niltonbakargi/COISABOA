package com.coisaboa.app.utils

import android.content.Context

object SessionManager {
    private const val PREF = "coisaboa_session"
    private const val KEY_ID = "user_id"
    private const val KEY_NAME = "user_name"

    fun login(ctx: Context, id: Long, name: String) {
        ctx.getSharedPreferences(PREF, Context.MODE_PRIVATE)
            .edit()
            .putLong(KEY_ID, id)
            .putString(KEY_NAME, name)
            .apply()
    }

    fun logout(ctx: Context) {
        ctx.getSharedPreferences(PREF, Context.MODE_PRIVATE)
            .edit()
            .clear()
            .apply()
    }

    fun isLogged(ctx: Context): Boolean = getUserId(ctx) != 0L
    fun getUserId(ctx: Context): Long =
        ctx.getSharedPreferences(PREF, Context.MODE_PRIVATE).getLong(KEY_ID, 0L)
    fun getUserName(ctx: Context): String? =
        ctx.getSharedPreferences(PREF, Context.MODE_PRIVATE).getString(KEY_NAME, null)
}
