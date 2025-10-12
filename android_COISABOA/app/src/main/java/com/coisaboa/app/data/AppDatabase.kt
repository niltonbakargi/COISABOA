package com.coisaboa.app.data

import androidx.room.Database
import androidx.room.RoomDatabase
import androidx.room.TypeConverters
import com.coisaboa.app.data.dao.ProductDao
import com.coisaboa.app.data.dao.PurchaseDao
import com.coisaboa.app.data.dao.SaleDao
import com.coisaboa.app.data.dao.UserDao
import com.coisaboa.app.data.entity.ProductEntity
import com.coisaboa.app.data.entity.PurchaseEntity
import com.coisaboa.app.data.entity.SaleEntity
import com.coisaboa.app.data.entity.UserEntity
import com.coisaboa.app.utils.Converters

/**
 * 🧩 AppDatabase — Banco de Dados Principal do COISABOA
 *
 * Centraliza todas as tabelas (entidades) e seus respectivos DAOs.
 * O Room utiliza esta classe como ponto de entrada para gerar
 * automaticamente o código de acesso ao banco SQLite local.
 *
 * ⚙️ Importante:
 * - Sempre incremente o número da versão (`version = X`) ao:
 *     ▫️ Adicionar/remover entidades
 *     ▫️ Alterar campos nas tabelas
 *     ▫️ Modificar relacionamentos ou tipos de dados
 * - O método `fallbackToDestructiveMigration()` no DatabaseProvider
 *   recria automaticamente o banco se houver mudanças de schema.
 */
@Database(
    entities = [
        ProductEntity::class,   // 🧺 Produtos cadastrados no estoque
        PurchaseEntity::class,  // 🛒 Compras realizadas (entrada)
        SaleEntity::class,      // 💰 Vendas registradas (saída)
        UserEntity::class       // 👤 Usuários do sistema
    ],
    version = 14,               // ⬆️ Incrementado para forçar recriação do banco
    exportSchema = false
)
@TypeConverters(Converters::class) // 🔄 Converte tipos complexos (Date <-> Long, etc.)
abstract class AppDatabase : RoomDatabase() {

    // 🧩 DAOs — Interfaces de acesso aos dados
    abstract fun productDao(): ProductDao
    abstract fun purchaseDao(): PurchaseDao
    abstract fun saleDao(): SaleDao
    abstract fun userDao(): UserDao
}
