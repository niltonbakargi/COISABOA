<?php
/**
 * COISABOA - Helper de Assets
 * Funções para incluir CSS e JS dinamicamente
 */

function css($arquivo) {
    return '/assets/css/' . $arquivo . '?v=' . filemtime(ASSETS_PATH . '/css/' . $arquivo);
}

function js($arquivo) {
    return '/assets/js/' . $arquivo . '?v=' . filemtime(ASSETS_PATH . '/js/' . $arquivo);
}

function image($arquivo) {
    return '/assets/images/' . $arquivo;
}

function icon($arquivo) {
    return '/assets/icons/' . $arquivo;
}
?>