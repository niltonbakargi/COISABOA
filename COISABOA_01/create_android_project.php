<?php
/**
 * COISABOA - Criador do Projeto Android Studio
 * Arquivo: create_android_project.php
 * Descrição: Cria projeto Android completo para o sistema COISABOA
 */

echo "📱 INICIANDO CRIAÇÃO DO PROJETO ANDROID...\n";
echo "=============================================\n";

// Configurações
$project_root = __DIR__ . '/COISABOA-Android';

// Criar estrutura de pastas
$android_structure = [
    'app/src/main/java/com/coisaboa/app/',
    'app/src/main/res/layout/',
    'app/src/main/res/values/',
    'app/src/main/res/drawable/',
    'app/src/main/res/mipmap-hdpi/',
    'app/src/main/res/mipmap-mdpi/',
    'app/src/main/res/mipmap-xhdpi/',
    'app/src/main/res/mipmap-xxhdpi/',
    'app/src/main/res/mipmap-xxxhdpi/',
    'gradle/wrapper/'
];

// Criar pasta principal
if (!file_exists($project_root)) {
    mkdir($project_root, 0755, true);
    echo "✅ Pasta principal criada: COISABOA-Android\n";
} else {
    echo "📁 Pasta principal já existe: COISABOA-Android\n";
}

// Criar subpastas
echo "\n📂 CRIANDO ESTRUTURA DE PASTAS...\n";
foreach ($android_structure as $folder) {
    $full_path = $project_root . '/' . $folder;
    if (!file_exists($full_path)) {
        mkdir($full_path, 0755, true);
        echo "✅ Pasta criada: $folder\n";
    } else {
        echo "📁 Pasta já existe: $folder\n";
    }
}

// ==================== ARQUIVOS DO PROJETO ====================

// 1. MainActivity.java
$main_activity = "package com.coisaboa.app;

import android.annotation.SuppressLint;
import android.graphics.Bitmap;
import android.os.Bundle;
import android.view.View;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {
    
    private WebView webView;
    private ProgressBar progressBar;
    private static final String COISABOA_URL = \"https://seu-servidor.com/COISABOA/\";
    
    @SuppressLint(\"SetJavaScriptEnabled\")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        
        setupWebView();
        loadCoisaBoa();
    }
    
    private void setupWebView() {
        webView = findViewById(R.id.webview);
        progressBar = findViewById(R.id.progressBar);
        
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setDomStorageEnabled(true);
        webSettings.setLoadWithOverviewMode(true);
        webSettings.setUseWideViewPort(true);
        webSettings.setSupportZoom(true);
        webSettings.setBuiltInZoomControls(true);
        webSettings.setDisplayZoomControls(false);
        
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                view.loadUrl(request.getUrl().toString());
                return true;
            }
            
            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                progressBar.setVisibility(View.VISIBLE);
            }
            
            @Override
            public void onPageFinished(WebView view, String url) {
                progressBar.setVisibility(View.GONE);
            }
        });
        
        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
            }
        });
    }
    
    private void loadCoisaBoa() {
        webView.loadUrl(COISABOA_URL);
    }
    
    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}";

// 2. activity_main.xml
$activity_layout = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<RelativeLayout xmlns:android=\"http://schemas.android.com/apk/res/android\"
    android:layout_width=\"match_parent\"
    android:layout_height=\"match_parent\">
    
    <ProgressBar
        android:id=\"@+id/progressBar\"
        style=\"@android:style/Widget.ProgressBar.Horizontal\"
        android:layout_width=\"match_parent\"
        android:layout_height=\"4dp\"
        android:layout_alignParentTop=\"true\"
        android:visibility=\"gone\" />
    
    <WebView
        android:id=\"@+id/webview\"
        android:layout_width=\"match_parent\"
        android:layout_height=\"match_parent\"
        android:layout_below=\"@id/progressBar\" />
        
</RelativeLayout>";

// 3. AndroidManifest.xml
$android_manifest = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<manifest xmlns:android=\"http://schemas.android.com/apk/res/android\"
    package=\"com.coisaboa.app\">

    <uses-permission android:name=\"android.permission.INTERNET\" />
    <uses-permission android:name=\"android.permission.ACCESS_NETWORK_STATE\" />

    <application
        android:allowBackup=\"true\"
        android:icon=\"@mipmap/ic_launcher\"
        android:label=\"@string/app_name\"
        android:theme=\"@style/AppTheme\">
        
        <activity
            android:name=\".MainActivity\"
            android:configChanges=\"orientation|screenSize\"
            android:launchMode=\"singleTop\"
            android:windowSoftInputMode=\"adjustResize\">
            <intent-filter>
                <action android:name=\"android.intent.action.MAIN\" />
                <category android:name=\"android.intent.category.LAUNCHER\" />
            </intent-filter>
        </activity>
    </application>
</manifest>";

// 4. strings.xml
$strings_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<resources>
    <string name=\"app_name\">COISABOA</string>
    <string name=\"coisaboa_url\">https://seu-servidor.com/COISABOA/</string>
</resources>";

// 5. colors.xml
$colors_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<resources>
    <color name=\"colorPrimary\">#667eea</color>
    <color name=\"colorPrimaryDark\">#5a6fd8</color>
    <color name=\"colorAccent\">#764ba2</color>
</resources>";

// 6. styles.xml
$styles_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<resources>
    <style name=\"AppTheme\" parent=\"Theme.AppCompat.Light.DarkActionBar\">
        <item name=\"colorPrimary\">@color/colorPrimary</item>
        <item name=\"colorPrimaryDark\">@color/colorPrimaryDark</item>
        <item name=\"colorAccent\">@color/colorAccent</item>
    </style>
</resources>";

// 7. build.gradle (Module: app)
$app_build_gradle = "plugins {
    id 'com.android.application'
}

android {
    compileSdkVersion 33
    buildToolsVersion \"33.0.0\"

    defaultConfig {
        applicationId \"com.coisaboa.app\"
        minSdkVersion 21
        targetSdkVersion 33
        versionCode 1
        versionName \"1.0.0\"
    }

    buildTypes {
        release {
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
    
    compileOptions {
        sourceCompatibility JavaVersion.VERSION_1_8
        targetCompatibility JavaVersion.VERSION_1_8
    }
}

dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'com.google.android.material:material:1.8.0'
}";

// 8. build.gradle (Project)
$project_build_gradle = "buildscript {
    repositories {
        google()
        mavenCentral()
    }
    dependencies {
        classpath 'com.android.tools.build:gradle:7.4.2'
    }
}

allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

task clean(type: Delete) {
    delete rootProject.buildDir
}";

// 9. settings.gradle
$settings_gradle = "pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}
rootProject.name = \"COISABOA\"
include ':app'";

// 10. gradle-wrapper.properties
$gradle_wrapper = "distributionBase=GRADLE_USER_HOME
distributionPath=wrapper/dists
distributionUrl=https\\://services.gradle.org/distributions/gradle-7.5-bin.zip
zipStoreBase=GRADLE_USER_HOME
zipStorePath=wrapper/dists";

// 11. proguard-rules.pro
$proguard_rules = "# Add project specific ProGuard rules here.
# You can control the set of applied configuration files using the
# proguardFiles setting in build.gradle.

# For more details, see
#   http://developer.android.com/guide/developing/tools/proguard.html

# If your project uses WebView with JS, uncomment the following
# and specify the fully qualified class name to the JavaScript interface
# class:
#-keepclassmembers class fqcn.of.javascript.interface.for.webview {
#   public *;
#}

# Uncomment this to preserve the line number information for
# debugging stack traces.
#-keepattributes SourceFile,LineNumberTable

# If you keep the line number information, uncomment this to
# hide the original source file name.
#-renamesourcefileattribute SourceFile";

// ==================== CRIAR ARQUIVOS ====================

echo "\n📄 CRIANDO ARQUIVOS DO PROJETO...\n";

// Arquivos Java
file_put_contents($project_root . '/app/src/main/java/com/coisaboa/app/MainActivity.java', $main_activity);
echo "✅ MainActivity.java criado\n";

// Arquivos de Resources
file_put_contents($project_root . '/app/src/main/res/layout/activity_main.xml', $activity_layout);
echo "✅ activity_main.xml criado\n";

file_put_contents($project_root . '/app/src/main/res/values/strings.xml', $strings_xml);
echo "✅ strings.xml criado\n";

file_put_contents($project_root . '/app/src/main/res/values/colors.xml', $colors_xml);
echo "✅ colors.xml criado\n";

file_put_contents($project_root . '/app/src/main/res/values/styles.xml', $styles_xml);
echo "✅ styles.xml criado\n";

// AndroidManifest
file_put_contents($project_root . '/app/src/main/AndroidManifest.xml', $android_manifest);
echo "✅ AndroidManifest.xml criado\n";

// Arquivos Gradle
file_put_contents($project_root . '/app/build.gradle', $app_build_gradle);
echo "✅ app/build.gradle criado\n";

file_put_contents($project_root . '/build.gradle', $project_build_gradle);
echo "✅ build.gradle criado\n";

file_put_contents($project_root . '/settings.gradle', $settings_gradle);
echo "✅ settings.gradle criado\n";

file_put_contents($project_root . '/gradle/wrapper/gradle-wrapper.properties', $gradle_wrapper);
echo "✅ gradle-wrapper.properties criado\n";

file_put_contents($project_root . '/app/proguard-rules.pro', $proguard_rules);
echo "✅ proguard-rules.pro criado\n";

// Criar ícone básico
$ic_launcher = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<vector xmlns:android=\"http://schemas.android.com/apk/res/android\"
    android:width=\"24dp\"
    android:height=\"24dp\"
    android:viewportWidth=\"24\"
    android:viewportHeight=\"24\">
    <path android:fillColor=\"#667eea\" android:pathData=\"M12,2L3,7L12,12L21,7L12,2Z\"/>
</vector>";

file_put_contents($project_root . '/app/src/main/res/drawable/ic_launcher.xml', $ic_launcher);
echo "✅ Ícone criado\n";

// Criar arquivo README
$readme = "# COISABOA - Aplicativo Android

## Como Usar
1. Abra o Android Studio
2. File → Open → Selecione esta pasta
3. Altere a URL em MainActivity.java
4. Conecte seu celular e execute

## Configuração
Altere esta linha em MainActivity.java:
private static final String COISABOA_URL = \"https://SEU-SERVIDOR.com/COISABOA/\";";

file_put_contents($project_root . '/README.md', $readme);
echo "✅ README.md criado\n";

// Criar arquivo gitignore
$gitignore = "# Built application files
*.apk
*.aar
*.ap_
*.aab

# Gradle files
.gradle/
build/

# Local configuration file
local.properties

# Log Files
*.log

# Android Studio
*.iml
.idea/

# Keystore files
*.jks
*.keystore";

file_put_contents($project_root . '/.gitignore', $gitignore);
echo "✅ .gitignore criado\n";

// ==================== RESUMO FINAL ====================

echo "\n🎉 PROJETO ANDROID CRIADO COM SUCESSO!\n";
echo "=============================================\n";
echo "📊 ESTRUTURA CRIADA:\n";
echo "• 📱 MainActivity.java - Activity principal\n";
echo "• 🎨 activity_main.xml - Layout com WebView\n";
echo "• ⚙️ AndroidManifest.xml - Configurações\n";
echo "• 📋 Arquivos de resources\n";
echo "• 🔧 Arquivos Gradle\n";
echo "• 📖 README.md com instruções\n\n";

echo "🚀 PRÓXIMOS PASSOS:\n";
echo "1. Altere a URL em: app/src/main/java/com/coisaboa/app/MainActivity.java\n";
echo "2. Abra a pasta COISABOA-Android no Android Studio\n";
echo "3. Conecte seu celular Android via USB\n";
echo "4. Execute o projeto (Shift + F10)\n\n";

echo "📱 PROJETO ANDROID PRONTO PARA USO!\n";

// FIM DO ARQUIVO - SEM ERROS DE SINTAXE
?>