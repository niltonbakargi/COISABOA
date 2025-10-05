<?php
/**
 * COISABOA - Analisador de Estrutura do Projeto
 * Gera documentação completa da arquitetura
 */

class ProjectAnalyzer {
    private $projectPath;
    private $structure;
    
    public function __construct($path) {
        $this->projectPath = realpath($path);
        $this->structure = [
            'info_geral' => [],
            'arquivos_php' => [],
            'telas' => [],
            'entidades' => [],
            'banco_dados' => [],
            'fluxos' => [],
            'dependencias' => []
        ];
    }
    
    public function analisar() {
        echo "🔍 Analisando projeto: " . $this->projectPath . "\n\n";
        
        $this->analisarInfoGeral();
        $this->analisarArquivosPHP();
        $this->analisarTelas();
        $this->analisarBancoDados();
        $this->analisarFluxos();
        $this->gerarRelatorio();
        
        return $this->structure;
    }
    
    private function analisarInfoGeral() {
        $this->structure['info_geral'] = [
            'nome_projeto' => basename($this->projectPath),
            'data_analise' => date('Y-m-d H:i:s'),
            'total_arquivos_php' => 0,
            'total_linhas_codigo' => 0,
            'pastas_principais' => $this->scanPastas($this->projectPath)
        ];
    }
    
    private function analisarArquivosPHP() {
        $arquivos = $this->scanRecursivo($this->projectPath, 'php');
        
        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo);
            $linhas = count(file($arquivo));
            $tamanho = filesize($arquivo);
            
            $analise = [
                'arquivo' => $arquivo,
                'caminho_relativo' => str_replace($this->projectPath . '/', '', $arquivo),
                'linhas' => $linhas,
                'tamanho_bytes' => $tamanho,
                'funcoes' => $this->extrairFuncoes($conteudo),
                'classes' => $this->extrairClasses($conteudo),
                'includes' => $this->extrairIncludes($conteudo),
                'formularios' => $this->extrairFormularios($conteudo)
            ];
            
            $this->structure['arquivos_php'][] = $analise;
            $this->structure['info_geral']['total_linhas_codigo'] += $linhas;
        }
        
        $this->structure['info_geral']['total_arquivos_php'] = count($arquivos);
    }
    
    private function analisarTelas() {
        foreach ($this->structure['arquivos_php'] as $arquivo) {
            if ($this->isTela($arquivo['arquivo'])) {
                $tela = [
                    'nome' => pathinfo($arquivo['arquivo'], PATHINFO_FILENAME),
                    'arquivo' => $arquivo['caminho_relativo'],
                    'tipo' => $this->detectarTipoTela($arquivo['arquivo']),
                    'acoes' => $this->extrairAcoesTela(file_get_contents($arquivo['arquivo'])),
                    'campos' => $arquivo['formularios'],
                    'redirecionamentos' => $this->extrairRedirecionamentos(file_get_contents($arquivo['arquivo']))
                ];
                
                $this->structure['telas'][] = $tela;
            }
        }
    }
    
    private function analisarBancoDados() {
        // Analisar config/database.php
        $dbConfig = $this->projectPath . '/config/database.php';
        if (file_exists($dbConfig)) {
            $conteudo = file_get_contents($dbConfig);
            $this->structure['banco_dados']['config'] = $this->extrairConfigDB($conteudo);
        }
        
        // Extrair entidades das queries
        $this->structure['banco_dados']['entidades'] = $this->extrairEntidades();
    }
    
    private function analisarFluxos() {
        $fluxos = [];
        
        foreach ($this->structure['telas'] as $tela) {
            foreach ($tela['redirecionamentos'] as $redirect) {
                $fluxos[] = [
                    'origem' => $tela['nome'],
                    'destino' => $redirect,
                    'tipo' => 'navegacao'
                ];
            }
            
            foreach ($tela['acoes'] as $acao) {
                $fluxos[] = [
                    'origem' => $tela['nome'],
                    'acao' => $acao,
                    'tipo' => 'processamento'
                ];
            }
        }
        
        $this->structure['fluxos'] = $fluxos;
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    private function scanRecursivo($dir, $extensao = null) {
        $files = [];
        $scan = scandir($dir);
        
        foreach ($scan as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                $files = array_merge($files, $this->scanRecursivo($path, $extensao));
            } elseif (!$extensao || pathinfo($path, PATHINFO_EXTENSION) === $extensao) {
                $files[] = $path;
            }
        }
        
        return $files;
    }
    
    private function scanPastas($dir) {
        $pastas = [];
        $scan = scandir($dir);
        
        foreach ($scan as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $pastas[] = [
                    'nome' => $item,
                    'tipo' => $this->detectarTipoPasta($item),
                    'itens' => count(scandir($path)) - 2
                ];
            }
        }
        
        return $pastas;
    }
    
    private function extrairFuncoes($conteudo) {
        preg_match_all('/function\s+(\w+)\s*\(/', $conteudo, $matches);
        return $matches[1] ?? [];
    }
    
    private function extrairClasses($conteudo) {
        preg_match_all('/class\s+(\w+)/', $conteudo, $matches);
        return $matches[1] ?? [];
    }
    
    private function extrairIncludes($conteudo) {
        preg_match_all('/(require|include)(_once)?\s*[\'\"]([^\'\"]+)[\'\"]/', $conteudo, $matches);
        return $matches[3] ?? [];
    }
    
    private function extrairFormularios($conteudo) {
        preg_match_all('/<form[^>]*>.*?<\/form>/s', $conteudo, $forms);
        $campos = [];
        
        foreach ($forms[0] as $form) {
            preg_match_all('/<input[^>]*name=[\'"]([^\'"]+)[\'"][^>]*>/', $form, $inputs);
            preg_match_all('/<select[^>]*name=[\'"]([^\'"]+)[\'"][^>]*>/', $form, $selects);
            preg_match_all('/<textarea[^>]*name=[\'"]([^\'"]+)[\'"][^>]*>/', $form, $textareas);
            
            $campos = array_merge($campos, $inputs[1], $selects[1], $textareas[1]);
        }
        
        return array_unique($campos);
    }
    
    private function isTela($arquivo) {
        $nome = pathinfo($arquivo, PATHINFO_FILENAME);
        $conteudo = file_get_contents($arquivo);
        
        // Considera como tela se tem HTML ou redirecionamentos
        return strpos($conteudo, '<html') !== false || 
               strpos($conteudo, 'header(') !== false ||
               in_array($nome, ['login', 'dashboard', 'cadastro', 'install']);
    }
    
    private function detectarTipoTela($arquivo) {
        $nome = pathinfo($arquivo, PATHINFO_FILENAME);
        $conteudo = file_get_contents($arquivo);
        
        if (strpos($conteudo, 'session_start()') !== false) {
            return 'autenticada';
        } elseif (strpos($conteudo, '<form') !== false) {
            return 'formulario';
        } elseif (strpos($conteudo, 'install') !== false) {
            return 'configuracao';
        } else {
            return 'informacao';
        }
    }
    
    private function extrairAcoesTela($conteudo) {
        preg_match_all('/\$_POST\\[\'action\'\\]\\s*===\\s*[\'"]([^\'"]+)[\'"]/', $conteudo, $matches);
        return $matches[1] ?? [];
    }
    
    private function extrairRedirecionamentos($conteudo) {
        preg_match_all('/header\\([^)]*Location:[^)]*([\\w.-]+\\.php)/', $conteudo, $matches);
        return array_map(function($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, $matches[1] ?? []);
    }
    
    private function extrairConfigDB($conteudo) {
        preg_match_all('/define\\(\\s*[\'"]([^\'"]+)[\'"]\\s*,\\s*[\'"]([^\'"]*)[\'"]\\s*\\)/', $conteudo, $matches);
        $config = [];
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $config[$matches[1][$i]] = $matches[2][$i];
        }
        
        return $config;
    }
    
    private function extrairEntidades() {
        $entidades = [];
        
        foreach ($this->structure['arquivos_php'] as $arquivo) {
            $conteudo = file_get_contents($arquivo['arquivo']);
            
            // Extrair nomes de tabelas das queries
            preg_match_all('/FROM\\s+(\\w+)/i', $conteudo, $fromTables);
            preg_match_all('/INSERT\\s+INTO\\s+(\\w+)/i', $conteudo, $insertTables);
            preg_match_all('/UPDATE\\s+(\\w+)/i', $conteudo, $updateTables);
            preg_match_all('/CREATE\\s+TABLE\\s+(\\w+)/i', $conteudo, $createTables);
            
            $tables = array_merge(
                $fromTables[1] ?? [],
                $insertTables[1] ?? [],
                $updateTables[1] ?? [],
                $createTables[1] ?? []
            );
            
            foreach ($tables as $table) {
                if (!in_array($table, $entidades)) {
                    $entidades[] = $table;
                }
            }
        }
        
        return array_unique($entidades);
    }
    
    private function detectarTipoPasta($pasta) {
        $tipos = [
            'config' => 'configuracao',
            'uploads' => 'arquivos',
            'logs' => 'logs',
            'includes' => 'bibliotecas',
            'assets' => 'recursos',
            'css' => 'estilos',
            'js' => 'scripts'
        ];
        
        return $tipos[strtolower($pasta)] ?? 'dados';
    }
    
    private function gerarRelatorio() {
        echo "📊 RELATÓRIO DA ESTRUTURA DO PROJETO\n";
        echo "=====================================\n\n";
        
        echo "📁 INFORMAÇÕES GERAIS:\n";
        echo "----------------------\n";
        echo "Projeto: " . $this->structure['info_geral']['nome_projeto'] . "\n";
        echo "Data: " . $this->structure['info_geral']['data_analise'] . "\n";
        echo "Arquivos PHP: " . $this->structure['info_geral']['total_arquivos_php'] . "\n";
        echo "Linhas de código: " . $this->structure['info_geral']['total_linhas_codigo'] . "\n";
        
        echo "\n📂 ESTRUTURA DE PASTAS:\n";
        echo "---------------------\n";
        foreach ($this->structure['info_geral']['pastas_principais'] as $pasta) {
            echo "• {$pasta['nome']} ({$pasta['tipo']}) - {$pasta['itens']} itens\n";
        }
        
        echo "\n🖥️ TELAS IDENTIFICADAS:\n";
        echo "---------------------\n";
        foreach ($this->structure['telas'] as $tela) {
            echo "• {$tela['nome']} ({$tela['tipo']})\n";
            if (!empty($tela['acoes'])) {
                echo "  Ações: " . implode(', ', $tela['acoes']) . "\n";
            }
            if (!empty($tela['redirecionamentos'])) {
                echo "  Navega para: " . implode(', ', $tela['redirecionamentos']) . "\n";
            }
        }
        
        echo "\n🗃️ ENTIDADES DO BANCO:\n";
        echo "--------------------\n";
        if (!empty($this->structure['banco_dados']['entidades'])) {
            foreach ($this->structure['banco_dados']['entidades'] as $entidade) {
                echo "• $entidade\n";
            }
        }
        
        echo "\n🔄 FLUXOS DO SISTEMA:\n";
        echo "--------------------\n";
        foreach ($this->structure['fluxos'] as $fluxo) {
            if ($fluxo['tipo'] === 'navegacao') {
                echo "• {$fluxo['origem']} → {$fluxo['destino']}\n";
            }
        }
        
        // Gerar arquivo JSON com estrutura completa
        file_put_contents(
            'estrutura_projeto.json', 
            json_encode($this->structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        echo "\n💾 Estrutura salva em: estrutura_projeto.json\n";
    }
}

// USO DO ANALISADOR
if (php_sapi_name() === 'cli') {
    $path = isset($argv[1]) ? $argv[1] : '.';
    $analyzer = new ProjectAnalyzer($path);
    $estrutura = $analyzer->analisar();
} else {
    echo "<pre>";
    $analyzer = new ProjectAnalyzer(__DIR__);
    $estrutura = $analyzer->analisar();
    echo "</pre>";
}