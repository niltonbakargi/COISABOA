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
 * Contém todas as entidades (tabelas) e seus respectivos DAOs.
 * O Room utiliza esta classe para gerar automaticamente o código
 * de acesso ao banco SQLite.
 *
 * ⚙️ Atualize o número da versão (`version = X`) sempre que:
 *   • Adicionar/remover entidades
 *   • Alterar campos de tabelas
 *   • Modificar relações entre tabelas
 */
@Database(
    entities = [
        ProductEntity::class,   // Estoque de produtos
        PurchaseEntity::class,  // Compras realizadas
        SaleEntity::class,      // Vendas registradas
        UserEntity::class       // Usuários do sistema
    ],
    version = 12,               // ⬅️ Aumentado para forçar recriação e incluir tabela 'sale'
    exportSchema = false
)
@TypeConverters(Converters::class) // 🔄 Converte tipos complexos (ex: Date <-> Long)
abstract class AppDatabase : RoomDatabase() {

    /** 🔗 DAOs responsáveis pelo CRUD de cada tabela */
    abstract fun productDao(): ProductDao
    abstract fun purchaseDao(): PurchaseDao
    abstract fun saleDao(): SaleDao
    abstract fun userDao(): UserDao
}
