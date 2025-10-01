<?php
/**
 * Componente de Botão Reutilizável
 */
$text = $text ?? 'Botão';
$type = $type ?? 'button';
$style = $style ?? 'primary';
$size = $size ?? 'normal';
$icon = $icon ?? '';
$href = $href ?? '';
$class = $class ?? '';
$attributes = $attributes ?? '';
?>

<?php if ($href): ?>
    <a href="<?= $href ?>" class="btn btn-<?= $style ?> btn-<?= $size ?> <?= $class ?>" <?= $attributes ?>>
        <?php if ($icon): ?>
            <span class="btn-icon"><?= $icon ?></span>
        <?php endif; ?>
        <?= $text ?>
    </a>
<?php else: ?>
    <button type="<?= $type ?>" class="btn btn-<?= $style ?> btn-<?= $size ?> <?= $class ?>" <?= $attributes ?>>
        <?php if ($icon): ?>
            <span class="btn-icon"><?= $icon ?></span>
        <?php endif; ?>
        <?= $text ?>
    </button>
<?php endif; ?>