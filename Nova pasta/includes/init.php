<?php
/**
 * COISABOA - Inicialização do Sistema
 * Arquivo principal que inclui todas as dependências
 */

// Incluir configurações
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Incluir funções do sistema
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/validations.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database_helpers.php';

// Inicializar sistema
logDebug('Sistema COISABOA inicializado', 'INFO');

?>
