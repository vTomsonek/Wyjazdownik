<?php
/**
 * Renderuje pojedyncze pole pytania na podstawie metadanych z QuestionLabels.
 *
 * Wymagane zmienne ustawione przed include:
 *   string             $key      klucz pytania
 *   array              $meta     wynik QuestionLabels::get($key)
 *   mixed              $current  aktualna wartość z DB (string|array|null)
 */
$key     = $key     ?? '';
$meta    = $meta    ?? [];
$current = $current ?? null;
$type    = $meta['type'] ?? 'choice';
$multi   = (bool) ($meta['multi'] ?? false);
?>

<div class="mb-8" data-question="<?= e($key) ?>">
    <label class="block font-display font-bold text-lg md:text-xl text-ink dark:text-pale mb-1">
        <?= e($meta['question'] ?? $key) ?>
    </label>
    <?php if (!empty($meta['helper'])): ?>
        <p class="text-sm text-mist mb-3"><?= e($meta['helper']) ?></p>
    <?php else: ?>
        <div class="mb-3"></div>
    <?php endif; ?>

    <?php if ($type === 'slider'): ?>
        <?php require BASE_PATH . '/views/partials/wizard/field-slider.php'; ?>

    <?php elseif ($type === 'textarea'): ?>
        <?php require BASE_PATH . '/views/partials/wizard/field-textarea.php'; ?>

    <?php elseif ($type === 'language_grid'): ?>
        <?php require BASE_PATH . '/views/partials/wizard/field-languages.php'; ?>

    <?php elseif ($multi): ?>
        <?php require BASE_PATH . '/views/partials/wizard/field-checkboxes.php'; ?>

    <?php else: ?>
        <?php require BASE_PATH . '/views/partials/wizard/field-radio.php'; ?>
    <?php endif; ?>
</div>
