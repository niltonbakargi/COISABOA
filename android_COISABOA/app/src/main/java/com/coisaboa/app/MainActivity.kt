package com.coisaboa.app.COISABOA

import androidx.appcompat.app.AppCompatActivity
import android.os.Bundle
import com.coisaboa.app.R
import com.coisaboa.app.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        supportActionBar?.title = getString(R.string.app_name)
        binding.tvHello.text = """
    Olá, COISABOA!
    Esqueleto Android criado com sucesso.
""".trimIndent()
    }
}