plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("kotlin-kapt") // âœ… NecessÃ¡rio para o Room
}

android {
    namespace = "com.coisaboa.app"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.coisaboa.app"
        minSdk = 24
        targetSdk = 34
        versionCode = 1
        versionName = "1.0"
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        viewBinding = true // âœ… Habilita o ViewBinding
    }
}

dependencies {
    // ðŸ§© Banco de Dados Local (Room ORM)
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
    kapt("androidx.room:room-compiler:2.6.1")

    // ðŸ§  MVVM e Ciclo de Vida (ViewModel + LiveData)
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.8.4")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.4")
    implementation("androidx.lifecycle:lifecycle-livedata-ktx:2.8.4")

    // âš™ï¸ Activity KTX (para usar 'by viewModels()' nas Activities)
    implementation("androidx.activity:activity-ktx:1.9.2")

    // ðŸ”„ Coroutines (execuÃ§Ã£o assÃ­ncrona)
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.8.1")

    // ðŸ’¾ Gson (serializaÃ§Ã£o JSON, usado no VendaStorage)
    implementation("com.google.code.gson:gson:2.11.0")

    // ðŸŽ¨ Componentes bÃ¡sicos do Android
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("com.google.android.material:material:1.12.0")

    // ðŸ§ª Testes
    testImplementation("junit:junit:4.13.2")
    androidTestImplementation("androidx.test.ext:junit:1.2.1")
    androidTestImplementation("androidx.test.espresso:espresso-core:3.6.1")
}

