package com.coisaboa.app.ui.compras

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class PurchasesActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_purchases)
        supportActionBar?.title = "Compras"
    }
}