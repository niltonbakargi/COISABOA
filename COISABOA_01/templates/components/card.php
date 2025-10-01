<?php
/**
 * Componente de Card Reutilizável
 */
$title = $title ?? 'Título do Card';
$icon = $icon ?? '📄';
$content = $content ?? '';
$actions = $actions ?? '';
$class = $class ?? '';
?>

<div class="card <?= $class ?>">
    <?php if ($title): ?>
        <div class="card-header">
            <?php if ($icon): ?>
                <div class="card-icon"><?= $icon ?></div>
            <?php endif; ?>
            <h3 class="card-title"><?= $title ?></h3>
        </div>
    <?php endif; ?>
    
    <?php if ($content): ?>
        <div class="card-content">
            <?= $content ?>
        </div>
    <?php endif; ?>
    
    <?php if ($actions): ?>
        <div class="card-actions">
            <?= $actions ?>
        </div>
    <?php endif; ?>
</div>