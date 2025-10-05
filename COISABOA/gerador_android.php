<?php
/**
 * GERADOR COMPLETO ANDROID STUDIO - COISABOA
 * Cria toda a estrutura do projeto Android em um único arquivo
 */

class AndroidProjectGenerator {
    private $projectName = "COISABOA";
    private $packageName = "com.coisaboa.app";
    private $projectDir;
    
    public function __construct($outputDir = null) {
        $this->projectDir = $outputDir ?: __DIR__ . '/COISABOA_Android';
    }
    
    public function generateCompleteProject() {
        echo "🚀 INICIANDO CRIAÇÃO DO PROJETO ANDROID COMPLETO...\n\n";
        
        // Criar estrutura completa
        $this->createProjectStructure();
        $this->createGradleFiles();
        $this->createManifest();
        $this->createJavaFiles();
        $this->createLayoutFiles();
        $this->createResourceFiles();
        $this->createMenuFiles();
        $this->createConfigFiles();
        
        echo "\n✅ PROJETO ANDROID CRIADO COM SUCESSO!\n";
        echo "📁 Local: " . $this->projectDir . "\n";
        echo "📊 Total de arquivos criados: " . $this->countFiles() . "\n";
    }
    
    private function createProjectStructure() {
        $structure = [
            'app/src/main/java/com/coisaboa/app/activities',
            'app/src/main/java/com/coisaboa/app/fragments',
            'app/src/main/java/com/coisaboa/app/adapters',
            'app/src/main/java/com/coisaboa/app/models',
            'app/src/main/java/com/coisaboa/app/services',
            'app/src/main/java/com/coisaboa/app/utils',
            'app/src/main/res/layout',
            'app/src/main/res/drawable',
            'app/src/main/res/menu',
            'app/src/main/res/values',
            'app/src/main/res/values-night',
            'app/src/main/assets',
            'gradle/wrapper',
            'app/libs'
        ];
        
        foreach ($structure as $dir) {
            $fullPath = $this->projectDir . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
                echo "📁 Criado: $dir\n";
            }
        }
    }
    
    private function createGradleFiles() {
        // build.gradle (Project)
        $projectGradle = <<<'GRADLE'
// Top-level build file where you can add configuration options common to all sub-projects/modules.
buildscript {
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
}
GRADLE;
        $this->createFile('build.gradle', $projectGradle);

        // build.gradle (Module)
        $moduleGradle = <<<'GRADLE'
apply plugin: 'com.android.application'

android {
    compileSdkVersion 33
    
    defaultConfig {
        applicationId "com.coisaboa.app"
        minSdkVersion 21
        targetSdkVersion 33
        versionCode 1
        versionName "1.0"
        testInstrumentationRunner "androidx.test.runner.AndroidJUnitRunner"
    }
    
    buildTypes {
        release {
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
    
    compileOptions {
        sourceCompatibility JavaVersion.VERSION_1_8
        targetCompatibility JavaVersion.VERSION_1_8
    }
    
    buildFeatures {
        viewBinding true
    }
}

dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'com.google.android.material:material:1.8.0'
    implementation 'androidx.constraintlayout:constraintlayout:2.1.4'
    implementation 'androidx.recyclerview:recyclerview:1.3.0'
    implementation 'androidx.cardview:cardview:1.0.0'
    
    // Networking
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.10.0'
    
    // Lifecycle
    implementation 'androidx.lifecycle:lifecycle-viewmodel:2.6.1'
    implementation 'androidx.lifecycle:lifecycle-livedata:2.6.1'
    implementation 'androidx.lifecycle:lifecycle-runtime:2.6.1'
    
    // Navigation
    implementation 'androidx.navigation:navigation-fragment:2.5.3'
    implementation 'androidx.navigation:navigation-ui:2.5.3'
    
    // Image Loading
    implementation 'com.github.bumptech.glide:glide:4.14.2'
    annotationProcessor 'com.github.bumptech.glide:compiler:4.14.2'
    
    testImplementation 'junit:junit:4.13.2'
    androidTestImplementation 'androidx.test.ext:junit:1.1.5'
    androidTestImplementation 'androidx.test.espresso:espresso-core:3.5.1'
}
GRADLE;
        $this->createFile('app/build.gradle', $moduleGradle);

        // settings.gradle
        $settingsGradle = <<<'GRADLE'
pluginManagement {
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
rootProject.name = "COISABOA"
include ':app'
GRADLE;
        $this->createFile('settings.gradle', $settingsGradle);

        // gradle.properties
        $gradleProps = <<<'PROPS'
org.gradle.jvmargs=-Xmx2048m -Dfile.encoding=UTF-8
android.useAndroidX=true
android.enableJetifier=true
PROPS;
        $this->createFile('gradle.properties', $gradleProps);
    }
    
    private function createManifest() {
        $manifest = <<<'MANIFEST'
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.coisaboa.app">

    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />

    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:theme="@style/AppTheme"
        android:usesCleartextTraffic="true">
        
        <activity
            android:name=".activities.LoginActivity"
            android:exported="true"
            android:windowSoftInputMode="adjustResize">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
        
        <activity android:name=".activities.MainActivity" />
        <activity android:name=".activities.DashboardActivity" />
        <activity android:name=".activities.ProductFormActivity" />
        <activity android:name=".activities.SaleFormActivity" />
        <activity android:name=".activities.PurchaseFormActivity" />
        <activity android:name=".activities.ReportsActivity" />
        <activity android:name=".activities.BackupActivity" />
        <activity android:name=".activities.SettingsActivity" />

    </application>

</manifest>
MANIFEST;
        $this->createFile('app/src/main/AndroidManifest.xml', $manifest);
    }
    
    private function createJavaFiles() {
        // Activities
        $this->createLoginActivity();
        $this->createMainActivity();
        $this->createDashboardActivity();
        
        // Fragments
        $this->createDashboardFragment();
        $this->createProductsFragment();
        $this->createSalesFragment();
        $this->createPurchasesFragment();
        
        // Models
        $this->createUserModel();
        $this->createProductModel();
        $this->createSaleModel();
        $this->createPurchaseModel();
        
        // Adapters
        $this->createProductAdapter();
        $this->createSaleAdapter();
        $this->createPurchaseAdapter();
        
        // Services
        $this->createApiService();
        $this->createAuthService();
        
        // Utils
        $this->createSessionManager();
        $this->createNetworkUtil();
    }
    
    private function createLoginActivity() {
        $content = <<<'JAVA'
package com.coisaboa.app.activities;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.coisaboa.app.R;
import com.coisaboa.app.services.AuthService;
import com.coisaboa.app.utils.SessionManager;

public class LoginActivity extends AppCompatActivity {
    private EditText editEmail, editSenha;
    private Button btnLogin, btnRegister;
    private AuthService authService;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);
        
        initializeServices();
        initializeViews();
        setupClickListeners();
        checkExistingSession();
    }

    private void initializeServices() {
        authService = new AuthService(this);
        sessionManager = new SessionManager(this);
    }

    private void initializeViews() {
        editEmail = findViewById(R.id.editEmail);
        editSenha = findViewById(R.id.editSenha);
        btnLogin = findViewById(R.id.btnLogin);
        btnRegister = findViewById(R.id.btnRegister);
        
        // Preencher com dados de teste (como no PHP)
        editEmail.setText("admin@coisaboa.com");
        editSenha.setText("123456");
    }

    private void setupClickListeners() {
        btnLogin.setOnClickListener(v -> performLogin());
        btnRegister.setOnClickListener(v -> showRegisterDialog());
    }

    private void checkExistingSession() {
        if (sessionManager.isLoggedIn()) {
            navigateToMain();
        }
    }

    private void performLogin() {
        String email = editEmail.getText().toString().trim();
        String senha = editSenha.getText().toString().trim();

        if (email.isEmpty() || senha.isEmpty()) {
            showToast("Preencha todos os campos");
            return;
        }

        showLoading(true);
        
        // TODO: Integrar com API PHP
        // Simulação de login bem-sucedido
        if (email.equals("admin@coisaboa.com") && senha.equals("123456")) {
            sessionManager.saveUserSession("1", "Admin", email);
            navigateToMain();
        } else {
            showToast("Credenciais inválidas");
        }
        
        showLoading(false);
    }

    private void showRegisterDialog() {
        // TODO: Implementar diálogo de cadastro
        showToast("Funcionalidade de cadastro em desenvolvimento");
    }

    private void navigateToMain() {
        Intent intent = new Intent(this, MainActivity.class);
        startActivity(intent);
        finish();
    }

    private void showLoading(boolean loading) {
        btnLogin.setEnabled(!loading);
        btnRegister.setEnabled(!loading);
        btnLogin.setText(loading ? "Carregando..." : "Entrar");
    }

    private void showToast(String message) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show();
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/activities/LoginActivity.java', $content);
    }
    
    private function createMainActivity() {
        $content = <<<'JAVA'
package com.coisaboa.app.activities;

import android.os.Bundle;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import com.coisaboa.app.R;
import com.coisaboa.app.fragments.DashboardFragment;
import com.coisaboa.app.fragments.ProductsFragment;
import com.coisaboa.app.fragments.SalesFragment;
import com.coisaboa.app.fragments.PurchasesFragment;
import com.google.android.material.bottomnavigation.BottomNavigationView;

public class MainActivity extends AppCompatActivity {
    private BottomNavigationView bottomNav;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        
        initializeViews();
        setupNavigation();
        loadDefaultFragment();
    }

    private void initializeViews() {
        bottomNav = findViewById(R.id.bottomNav);
    }

    private void setupNavigation() {
        bottomNav.setOnItemSelectedListener(item -> {
            Fragment selectedFragment = null;
            
            int itemId = item.getItemId();
            if (itemId == R.id.nav_dashboard) {
                selectedFragment = new DashboardFragment();
            } else if (itemId == R.id.nav_products) {
                selectedFragment = new ProductsFragment();
            } else if (itemId == R.id.nav_sales) {
                selectedFragment = new SalesFragment();
            } else if (itemId == R.id.nav_purchases) {
                selectedFragment = new PurchasesFragment();
            }
            
            if (selectedFragment != null) {
                loadFragment(selectedFragment);
                return true;
            }
            
            return false;
        });
    }

    private void loadDefaultFragment() {
        loadFragment(new DashboardFragment());
        bottomNav.setSelectedItemId(R.id.nav_dashboard);
    }

    private void loadFragment(Fragment fragment) {
        getSupportFragmentManager()
            .beginTransaction()
            .replace(R.id.fragmentContainer, fragment)
            .commit();
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/activities/MainActivity.java', $content);
    }
    
    private function createDashboardActivity() {
        $content = <<<'JAVA'
package com.coisaboa.app.activities;

import android.os.Bundle;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import com.coisaboa.app.R;
import com.coisaboa.app.adapters.ProductAdapter;
import com.coisaboa.app.adapters.SaleAdapter;
import com.coisaboa.app.models.Product;
import com.coisaboa.app.models.Sale;
import com.coisaboa.app.utils.SessionManager;
import java.util.ArrayList;
import java.util.List;

public class DashboardActivity extends AppCompatActivity {
    private TextView txtWelcome, txtTotalProducts, txtTotalSales, txtTotalProfit;
    private RecyclerView recyclerRecentProducts, recyclerRecentSales;
    private ProductAdapter productAdapter;
    private SaleAdapter saleAdapter;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);
        
        initializeServices();
        initializeViews();
        setupAdapters();
        loadDashboardData();
    }

    private void initializeServices() {
        sessionManager = new SessionManager(this);
    }

    private void initializeViews() {
        txtWelcome = findViewById(R.id.txtWelcome);
        txtTotalProducts = findViewById(R.id.txtTotalProducts);
        txtTotalSales = findViewById(R.id.txtTotalSales);
        txtTotalProfit = findViewById(R.id.txtTotalProfit);
        recyclerRecentProducts = findViewById(R.id.recyclerRecentProducts);
        recyclerRecentSales = findViewById(R.id.recyclerRecentSales);
        
        String userName = sessionManager.getUserName();
        txtWelcome.setText("Olá, " + (userName != null ? userName.split(" ")[0] : "Usuário") + "! 👋");
    }

    private void setupAdapters() {
        productAdapter = new ProductAdapter(new ArrayList<>());
        saleAdapter = new SaleAdapter(new ArrayList<>());
        
        recyclerRecentProducts.setAdapter(productAdapter);
        recyclerRecentSales.setAdapter(saleAdapter);
    }

    private void loadDashboardData() {
        // TODO: Buscar dados da API PHP
        // Dados mockados baseados no seu sistema PHP
        txtTotalProducts.setText("45");
        txtTotalSales.setText("R$ 2.300,00");
        txtTotalProfit.setText("R$ 800,00");
        
        // Dados mockados para exemplos
        List<Product> recentProducts = new ArrayList<>();
        recentProducts.add(new Product("Notebook Dell", 1, 2500.00, 3200.00));
        recentProducts.add(new Product("Mouse Gamer", 5, 89.90, 129.90));
        
        List<Sale> recentSales = new ArrayList<>();
        recentSales.add(new Sale("Notebook Dell", 1, 3200.00, "2024-01-15"));
        recentSales.add(new Sale("Mouse Gamer", 2, 259.80, "2024-01-14"));
        
        productAdapter.updateList(recentProducts);
        saleAdapter.updateList(recentSales);
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/activities/DashboardActivity.java', $content);
    }
    
    private function createDashboardFragment() {
        $content = <<<'JAVA'
package com.coisaboa.app.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.coisaboa.app.R;
import com.coisaboa.app.adapters.ProductAdapter;
import com.coisaboa.app.adapters.SaleAdapter;
import com.coisaboa.app.models.Product;
import com.coisaboa.app.models.Sale;
import com.coisaboa.app.utils.SessionManager;
import java.util.ArrayList;
import java.util.List;

public class DashboardFragment extends Fragment {
    private TextView txtWelcome, txtStatsProducts, txtStatsSales, txtStatsProfit;
    private RecyclerView recyclerRecentProducts, recyclerRecentSales;
    private ProductAdapter productAdapter;
    private SaleAdapter saleAdapter;
    private SessionManager sessionManager;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_dashboard, container, false);
        
        initializeServices();
        initializeViews(view);
        setupRecyclerViews();
        loadDashboardData();
        
        return view;
    }

    private void initializeServices() {
        sessionManager = new SessionManager(requireContext());
    }

    private void initializeViews(View view) {
        txtWelcome = view.findViewById(R.id.txtWelcome);
        txtStatsProducts = view.findViewById(R.id.txtStatsProducts);
        txtStatsSales = view.findViewById(R.id.txtStatsSales);
        txtStatsProfit = view.findViewById(R.id.txtStatsProfit);
        recyclerRecentProducts = view.findViewById(R.id.recyclerRecentProducts);
        recyclerRecentSales = view.findViewById(R.id.recyclerRecentSales);
        
        String userName = sessionManager.getUserName();
        txtWelcome.setText("Bem-vindo, " + (userName != null ? userName.split(" ")[0] : "Usuário") + "! 👋");
    }

    private void setupRecyclerViews() {
        recyclerRecentProducts.setLayoutManager(new LinearLayoutManager(getContext()));
        recyclerRecentSales.setLayoutManager(new LinearLayoutManager(getContext()));
        
        productAdapter = new ProductAdapter(new ArrayList<>());
        saleAdapter = new SaleAdapter(new ArrayList<>());
        
        recyclerRecentProducts.setAdapter(productAdapter);
        recyclerRecentSales.setAdapter(saleAdapter);
    }

    private void loadDashboardData() {
        // TODO: Buscar dados da API PHP
        txtStatsProducts.setText("45 itens");
        txtStatsSales.setText("R$ 2.300,00");
        txtStatsProfit.setText("R$ 800,00");
        
        // Dados mockados
        List<Product> recentProducts = new ArrayList<>();
        recentProducts.add(new Product("Notebook Dell", 1, 2500.00, 3200.00));
        recentProducts.add(new Product("Mouse Gamer", 5, 89.90, 129.90));
        
        List<Sale> recentSales = new ArrayList<>();
        recentSales.add(new Sale("Notebook Dell", 1, 3200.00, "2024-01-15"));
        recentSales.add(new Sale("Mouse Gamer", 2, 259.80, "2024-01-14"));
        
        productAdapter.updateList(recentProducts);
        saleAdapter.updateList(recentSales);
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/fragments/DashboardFragment.java', $content);
    }
    
    private function createProductsFragment() {
        $content = <<<'JAVA'
package com.coisaboa.app.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.coisaboa.app.R;
import com.coisaboa.app.adapters.ProductAdapter;
import com.coisaboa.app.models.Product;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class ProductsFragment extends Fragment {
    private RecyclerView recyclerProducts;
    private ProductAdapter productAdapter;
    private FloatingActionButton fabAddProduct;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_products, container, false);
        
        initializeViews(view);
        setupRecyclerView();
        setupFab();
        loadProducts();
        
        return view;
    }

    private void initializeViews(View view) {
        recyclerProducts = view.findViewById(R.id.recyclerProducts);
        fabAddProduct = view.findViewById(R.id.fabAddProduct);
    }

    private void setupRecyclerView() {
        recyclerProducts.setLayoutManager(new LinearLayoutManager(getContext()));
        productAdapter = new ProductAdapter(new ArrayList<>());
        recyclerProducts.setAdapter(productAdapter);
    }

    private void setupFab() {
        fabAddProduct.setOnClickListener(v -> {
            // TODO: Abrir tela de adicionar produto
        });
    }

    private void loadProducts() {
        // TODO: Buscar produtos da API PHP
        List<Product> products = new ArrayList<>();
        products.add(new Product("Notebook Dell Inspiron", 2, 2500.00, 3200.00));
        products.add(new Product("Mouse Gamer RGB", 15, 89.90, 129.90));
        products.add(new Product("Teclado Mecânico", 8, 199.90, 299.90));
        products.add(new Product("Monitor 24\"", 3, 899.00, 1299.00));
        
        productAdapter.updateList(products);
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/fragments/ProductsFragment.java', $content);
    }
    
    private function createUserModel() {
        $content = <<<'JAVA'
package com.coisaboa.app.models;

public class User {
    private int id;
    private String nome;
    private String email;
    private String dataCriacao;

    public User() {}

    public User(int id, String nome, String email, String dataCriacao) {
        this.id = id;
        this.nome = nome;
        this.email = email;
        this.dataCriacao = dataCriacao;
    }

    // Getters e Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getNome() { return nome; }
    public void setNome(String nome) { this.nome = nome; }
    
    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }
    
    public String getDataCriacao() { return dataCriacao; }
    public void setDataCriacao(String dataCriacao) { this.dataCriacao = dataCriacao; }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/models/User.java', $content);
    }
    
    private function createProductModel() {
        $content = <<<'JAVA'
package com.coisaboa.app.models;

public class Product {
    private int id;
    private String nome;
    private int quantidade;
    private double precoCompra;
    private double precoVenda;
    private String imagem;
    private String dataEntrada;
    private String dataAtualizacao;

    public Product() {}

    public Product(String nome, int quantidade, double precoCompra, double precoVenda) {
        this.nome = nome;
        this.quantidade = quantidade;
        this.precoCompra = precoCompra;
        this.precoVenda = precoVenda;
    }

    // Getters e Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getNome() { return nome; }
    public void setNome(String nome) { this.nome = nome; }
    
    public int getQuantidade() { return quantidade; }
    public void setQuantidade(int quantidade) { this.quantidade = quantidade; }
    
    public double getPrecoCompra() { return precoCompra; }
    public void setPrecoCompra(double precoCompra) { this.precoCompra = precoCompra; }
    
    public double getPrecoVenda() { return precoVenda; }
    public void setPrecoVenda(double precoVenda) { this.precoVenda = precoVenda; }
    
    public String getImagem() { return imagem; }
    public void setImagem(String imagem) { this.imagem = imagem; }
    
    public String getDataEntrada() { return dataEntrada; }
    public void setDataEntrada(String dataEntrada) { this.dataEntrada = dataEntrada; }
    
    public String getDataAtualizacao() { return dataAtualizacao; }
    public void setDataAtualizacao(String dataAtualizacao) { this.dataAtualizacao = dataAtualizacao; }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/models/Product.java', $content);
    }
    
    private function createProductAdapter() {
        $content = <<<'JAVA'
package com.coisaboa.app.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.coisaboa.app.R;
import com.coisaboa.app.models.Product;
import java.util.List;
import java.util.Locale;

public class ProductAdapter extends RecyclerView.Adapter<ProductAdapter.ProductViewHolder> {
    private List<Product> products;

    public ProductAdapter(List<Product> products) {
        this.products = products;
    }

    @NonNull
    @Override
    public ProductViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_product, parent, false);
        return new ProductViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ProductViewHolder holder, int position) {
        Product product = products.get(position);
        holder.bind(product);
    }

    @Override
    public int getItemCount() {
        return products.size();
    }

    public void updateList(List<Product> newProducts) {
        this.products = newProducts;
        notifyDataSetChanged();
    }

    static class ProductViewHolder extends RecyclerView.ViewHolder {
        private TextView txtNome, txtQuantidade, txtPrecoCompra, txtPrecoVenda;

        public ProductViewHolder(@NonNull View itemView) {
            super(itemView);
            txtNome = itemView.findViewById(R.id.txtNome);
            txtQuantidade = itemView.findViewById(R.id.txtQuantidade);
            txtPrecoCompra = itemView.findViewById(R.id.txtPrecoCompra);
            txtPrecoVenda = itemView.findViewById(R.id.txtPrecoVenda);
        }

        public void bind(Product product) {
            txtNome.setText(product.getNome());
            txtQuantidade.setText(String.format(Locale.getDefault(), "%d un", product.getQuantidade()));
            txtPrecoCompra.setText(String.format(Locale.getDefault(), "Compra: R$ %.2f", product.getPrecoCompra()));
            txtPrecoVenda.setText(String.format(Locale.getDefault(), "Venda: R$ %.2f", product.getPrecoVenda()));
        }
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/adapters/ProductAdapter.java', $content);
    }
    
    private function createApiService() {
        $content = <<<'JAVA'
package com.coisaboa.app.services;

import android.content.Context;
import com.coisaboa.app.utils.NetworkUtil;
import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;
import java.util.concurrent.TimeUnit;

public class ApiService {
    private static final String BASE_URL = "http://seu-servidor-php/coisaboa/";
    private static Retrofit retrofit = null;
    
    public static Retrofit getClient(Context context) {
        if (retrofit == null) {
            HttpLoggingInterceptor logging = new HttpLoggingInterceptor();
            logging.setLevel(HttpLoggingInterceptor.Level.BODY);
            
            OkHttpClient.Builder httpClient = new OkHttpClient.Builder();
            httpClient.addInterceptor(logging);
            httpClient.connectTimeout(30, TimeUnit.SECONDS);
            httpClient.readTimeout(30, TimeUnit.SECONDS);
            httpClient.writeTimeout(30, TimeUnit.SECONDS);
            
            // Adicionar interceptor para verificar conexão
            httpClient.addInterceptor(chain -> {
                if (!NetworkUtil.isNetworkAvailable(context)) {
                    throw new NoConnectivityException();
                }
                return chain.proceed(chain.request());
            });
            
            retrofit = new Retrofit.Builder()
                    .baseUrl(BASE_URL)
                    .addConverterFactory(GsonConverterFactory.create())
                    .client(httpClient.build())
                    .build();
        }
        return retrofit;
    }
    
    public static class NoConnectivityException extends RuntimeException {
        public NoConnectivityException() {
            super("Sem conexão com a internet");
        }
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/services/ApiService.java', $content);
    }
    
    private function createSessionManager() {
        $content = <<<'JAVA'
package com.coisaboa.app.utils;

import android.content.Context;
import android.content.SharedPreferences;

public class SessionManager {
    private static final String PREF_NAME = "COISABOA_SESSION";
    private static final String KEY_USER_ID = "user_id";
    private static final String KEY_USER_NAME = "user_name";
    private static final String KEY_USER_EMAIL = "user_email";
    private static final String KEY_IS_LOGGED_IN = "is_logged_in";
    
    private SharedPreferences pref;
    private SharedPreferences.Editor editor;
    private Context context;
    
    public SessionManager(Context context) {
        this.context = context;
        pref = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
        editor = pref.edit();
    }
    
    public void saveUserSession(String userId, String userName, String userEmail) {
        editor.putString(KEY_USER_ID, userId);
        editor.putString(KEY_USER_NAME, userName);
        editor.putString(KEY_USER_EMAIL, userEmail);
        editor.putBoolean(KEY_IS_LOGGED_IN, true);
        editor.apply();
    }
    
    public boolean isLoggedIn() {
        return pref.getBoolean(KEY_IS_LOGGED_IN, false);
    }
    
    public String getUserId() {
        return pref.getString(KEY_USER_ID, null);
    }
    
    public String getUserName() {
        return pref.getString(KEY_USER_NAME, null);
    }
    
    public String getUserEmail() {
        return pref.getString(KEY_USER_EMAIL, null);
    }
    
    public void logout() {
        editor.clear();
        editor.apply();
    }
}
JAVA;
        $this->createFile('app/src/main/java/com/coisaboa/app/utils/SessionManager.java', $content);
    }
    
    // ... continuaria com os outros métodos para criar SalesFragment, PurchasesFragment, etc.
    
    private function createLayoutFiles() {
        // activity_login.xml
        $loginLayout = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<ScrollView xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:app="http://schemas.android.com/apk/res-auto"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    android:padding="20dp"
    android:background="@drawable/bg_gradient">

    <LinearLayout
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:orientation="vertical"
        android:gravity="center">

        <TextView
            android:layout_width="wrap_content"
            android:layout_height="wrap_content"
            android:text="📦"
            android:textSize="64sp"
            android:layout_marginBottom="16dp" />

        <TextView
            android:layout_width="wrap_content"
            android:layout_height="wrap_content"
            android:text="COISABOA"
            android:textSize="32sp"
            android:textStyle="bold"
            android:textColor="@color/white"
            android:layout_marginBottom="8dp" />

        <TextView
            android:layout_width="wrap_content"
            android:layout_height="wrap_content"
            android:text="Sistema de Gestão Pessoal"
            android:textSize="16sp"
            android:textColor="@color/white"
            android:layout_marginBottom="40dp" />

        <com.google.android.material.card.MaterialCardView
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:layout_marginBottom="20dp"
            app:cardCornerRadius="16dp"
            app:cardElevation="8dp">

            <LinearLayout
                android:layout_width="match_parent"
                android:layout_height="wrap_content"
                android:orientation="vertical"
                android:padding="24dp">

                <com.google.android.material.textfield.TextInputLayout
                    android:layout_width="match_parent"
                    android:layout_height="wrap_content"
                    android:layout_marginBottom="16dp"
                    app:boxCornerRadiusBottomEnd="12dp"
                    app:boxCornerRadiusBottomStart="12dp"
                    app:boxCornerRadiusTopEnd="12dp"
                    app:boxCornerRadiusTopStart="12dp">

                    <com.google.android.material.textfield.TextInputEditText
                        android:id="@+id/editEmail"
                        android:layout_width="match_parent"
                        android:layout_height="wrap_content"
                        android:hint="Email"
                        android:inputType="textEmailAddress"
                        android:text="admin@coisaboa.com" />

                </com.google.android.material.textfield.TextInputLayout>

                <com.google.android.material.textfield.TextInputLayout
                    android:layout_width="match_parent"
                    android:layout_height="wrap_content"
                    android:layout_marginBottom="24dp"
                    app:boxCornerRadiusBottomEnd="12dp"
                    app:boxCornerRadiusBottomStart="12dp"
                    app:boxCornerRadiusTopEnd="12dp"
                    app:boxCornerRadiusTopStart="12dp">

                    <com.google.android.material.textfield.TextInputEditText
                        android:id="@+id/editSenha"
                        android:layout_width="match_parent"
                        android:layout_height="wrap_content"
                        android:hint="Senha"
                        android:inputType="textPassword"
                        android:text="123456" />

                </com.google.android.material.textfield.TextInputLayout>

                <Button
                    android:id="@+id/btnLogin"
                    android:layout_width="match_parent"
                    android:layout_height="wrap_content"
                    android:text="🚀 Entrar"
                    android:textSize="16sp"
                    android:textStyle="bold"
                    android:backgroundTint="@color/primary"
                    android:layout_marginBottom="12dp" />

                <Button
                    android:id="@+id/btnRegister"
                    android:layout_width="match_parent"
                    android:layout_height="wrap_content"
                    android:text="📝 Cadastrar"
                    android:textSize="16sp"
                    android:backgroundTint="@color/secondary" />

            </LinearLayout>

        </com.google.android.material.card.MaterialCardView>

        <TextView
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:text="Dados para teste:\nEmail: admin@coisaboa.com\nSenha: 123456"
            android:textSize="12sp"
            android:textColor="@color/white"
            android:gravity="center"
            android:background="@color/transparent_white"
            android:padding="12dp"
            android:lineSpacingExtra="4dp" />

    </LinearLayout>

</ScrollView>
XML;
        $this->createFile('app/src/main/res/layout/activity_login.xml', $loginLayout);

        // activity_main.xml
        $mainLayout = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<LinearLayout xmlns:android="http://schemas.android.com/apk/res/android"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    android:orientation="vertical">

    <FrameLayout
        android:id="@+id/fragmentContainer"
        android:layout_width="match_parent"
        android:layout_height="0dp"
        android:layout_weight="1" />

    <com.google.android.material.bottomnavigation.BottomNavigationView
        android:id="@+id/bottomNav"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        app:menu="@menu/bottom_navigation" />

</LinearLayout>
XML;
        $this->createFile('app/src/main/res/layout/activity_main.xml', $mainLayout);
    }
    
    private function createResourceFiles() {
        // strings.xml
        $strings = <<<'XML'
<resources>
    <string name="app_name">COISABOA</string>
    <string name="login">Login</string>
    <string name="email">Email</string>
    <string name="senha">Senha</string>
    <string name="entrar">Entrar</string>
    <string name="cadastrar">Cadastrar</string>
    <string name="dashboard">Dashboard</string>
    <string name="products">Produtos</string>
    <string name="sales">Vendas</string>
    <string name="purchases">Compras</string>
    <string name="reports">Relatórios</string>
    <string name="settings">Configurações</string>
    <string name="welcome">Bem-vindo</string>
    <string name="total_products">Total Produtos</string>
    <string name="total_sales">Total Vendas</string>
    <string name="total_profit">Lucro Total</string>
</resources>
XML;
        $this->createFile('app/src/main/res/values/strings.xml', $strings);

        // colors.xml
        $colors = <<<'XML'
<resources>
    <color name="primary">#667eea</color>
    <color name="primary_dark">#5a6fd8</color>
    <color name="secondary">#764ba2</color>
    <color name="accent">#f093fb</color>
    <color name="white">#FFFFFF</color>
    <color name="black">#000000</color>
    <color name="transparent_white">#22FFFFFF</color>
    <color name="background">#f8f9fa</color>
    <color name="text_primary">#2c3e50</color>
    <color name="text_secondary">#7f8c8d</color>
    <color name="success">#27ae60</color>
    <color name="warning">#f39c12</color>
    <color name="error">#e74c3c</color>
</resources>
XML;
        $this->createFile('app/src/main/res/values/colors.xml', $colors);
    }
    
    private function createMenuFiles() {
        // bottom_navigation.xml
        $bottomNav = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<menu xmlns:android="http://schemas.android.com/apk/res/android">
    <item
        android:id="@+id/nav_dashboard"
        android:icon="@drawable/ic_dashboard"
        android:title="@string/dashboard" />
    <item
        android:id="@+id/nav_products"
        android:icon="@drawable/ic_products"
        android:title="@string/products" />
    <item
        android:id="@+id/nav_sales"
        android:icon="@drawable/ic_sales"
        android:title="@string/sales" />
    <item
        android:id="@+id/nav_purchases"
        android:icon="@drawable/ic_purchases"
        android:title="@string/purchases" />
</menu>
XML;
        $this->createFile('app/src/main/res/menu/bottom_navigation.xml', $bottomNav);
    }
    
    private function createConfigFiles() {
        // proguard-rules.pro
        $proguard = <<<'PROGUARD'
# Add project specific ProGuard rules here.
# You can control the set of applied configuration files using the
# proguardFiles setting in build.gradle.
#
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
#-renamesourcefileattribute SourceFile
PROGUARD;
        $this->createFile('app/proguard-rules.pro', $proguard);

        // .gitignore
        $gitignore = <<<'GITIGNORE'
*.iml
.gradle
/local.properties
/.idea/caches
/.idea/libraries
/.idea/modules.xml
/.idea/workspace.xml
/.idea/navEditor.xml
/.idea/assetWizardSettings.xml
.DS_Store
/build
/captures
.externalNativeBuild
.cxx
local.properties
GITIGNORE;
        $this->createFile('.gitignore', $gitignore);
    }
    
    private function createFile($path, $content) {
        $fullPath = $this->projectDir . '/' . $path;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (file_put_contents($fullPath, $content) !== false) {
            echo "📄 Criado: $path\n";
            return true;
        } else {
            echo "❌ Erro ao criar: $path\n";
            return false;
        }
    }
    
    private function countFiles() {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $count = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        return $count;
    }
}

// ==================== EXECUÇÃO ====================

// Criar e executar o gerador
$generator = new AndroidProjectGenerator();
$generator->generateCompleteProject();

echo "\n🎉 PROJETO ANDROID CRIADO COM SUCESSO!\n";
echo "📋 PRÓXIMOS PASSOS:\n";
echo "1. Abra o Android Studio\n";
echo "2. Selecione 'Open an Existing Project'\n";
echo "3. Navegue até a pasta: " . $generator->projectDir . "\n";
echo "4. Aguarde o Gradle sincronizar\n";
echo "5. Conecte um dispositivo Android ou use um emulador\n";
echo "6. Execute o app (Shift + F10)\n";
echo "\n🔗 CONFIGURAÇÃO DO BACKEND:\n";
echo "- Mantenha seu XAMPP com o COISABOA PHP rodando\n";
echo "- Atualize a BASE_URL no ApiService.java\n";
echo "- Crie APIs REST no PHP para comunicação\n";
?>