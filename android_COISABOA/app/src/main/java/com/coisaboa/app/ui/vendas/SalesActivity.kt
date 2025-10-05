package com.coisaboa.app.ui.vendas

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class SalesActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_sales)
        supportActionBar?.title = "Vendas"
    }
}