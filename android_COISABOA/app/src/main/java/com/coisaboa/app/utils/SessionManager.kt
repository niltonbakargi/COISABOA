package com.coisaboa.app.utils

import android.content.Context
import android.content.SharedPreferences

/**
 * SessionManager — Gerencia a sessão local do usuário.
 *
 * Armazena dados básicos de login (ID e Nome) em SharedPreferences
 * e mantém o estado de login entre as execuções do app.
 *
 * ✅ Funciona totalmente offline
 * ✅ Impede o "loop" de voltar para o LoginActivity
 * ✅ Compatível com a arquitetura atual (LoginViewModel e MainActivity)
 */
object SessionManager {

    // Nome do arquivo de preferências
    private const val PREF_NAME = "coisaboa_session"

    // Chaves usadas para armazenar dados
    private const val KEY_ID = "user_id"
    private const val KEY_NAME = "user_name"
    private const val KEY_LOGGED = "is_logged_in"

    // Retorna o objeto SharedPreferences
    private fun prefs(ctx: Context): SharedPreferences =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)

    /**
     * Salva as informações do login.
     * @param ctx Contexto (ex: this)
     * @param id  ID do usuário (pode ser 0L se offline)
     * @param name Nome do usuário
     */
    fun login(ctx: Context, id: Long, name: String) {
        prefs(ctx).edit()
            .putLong(KEY_ID, id)
            .putString(KEY_NAME, name)
            .putBoolean(KEY_LOGGED, true)
            .apply()
    }

    /**
     * Finaliza a sessão, limpando todos os dados.
     */
    fun logout(ctx: Context) {
        prefs(ctx).edit()
            .clear()
            .apply()
    }

    /**
     * Verifica se o usuário está logado.
     * Usa uma flag booleana persistente para segurança.
     */
    fun isLogged(ctx: Context): Boolean =
        prefs(ctx).getBoolean(KEY_LOGGED, false)

    /**
     * Retorna o ID do usuário logado.
     */
    fun getUserId(ctx: Context): Long =
        prefs(ctx).getLong(KEY_ID, 0L)

    /**
     * Retorna o nome do usuário logado.
     */
    fun getUserName(ctx: Context): String? =
        prefs(ctx).getString(KEY_NAME, null)
}
