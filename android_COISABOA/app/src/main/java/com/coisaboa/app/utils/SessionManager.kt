package com.coisaboa.app.utils

import android.content.Context

object SessionManager {
    private const val PREF_NAME = "coisaboa_session"
    private const val KEY_USER_ID = "user_id"
    private const val KEY_USER_NAME = "user_name"

    fun login(context: Context, userId: Int, userName: String) {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        prefs.edit().apply {
            putInt(KEY_USER_ID, userId)
            putString(KEY_USER_NAME, userName)
            apply()
        }
    }

    fun isLogged(context: Context): Boolean {
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        return prefs.contains(KEY_USER_ID)
    }

    fun getUserName(context: Context): String? {
        return context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_USER_NAME, null)
    }

    fun logout(context: Context) {
        context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .edit().clear().apply()
    }
}
