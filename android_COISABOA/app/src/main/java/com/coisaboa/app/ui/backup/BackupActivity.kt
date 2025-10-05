package com.coisaboa.app.ui.backup

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.coisaboa.app.R

class BackupActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_backup)
        supportActionBar?.title = "Backup"
    }
}