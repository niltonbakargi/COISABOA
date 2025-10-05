package com.coisaboa.app.ui.relatorios

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class ReportsActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_reports)
        supportActionBar?.title = "Relatórios"
    }
}