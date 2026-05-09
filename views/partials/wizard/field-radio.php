<?php
/** Pole radio (single choice) - $key, $meta, $current */
$opts    = $meta['options'] ?? [];
$current = is_string($current) ? $current : ($current === true ? 'true' : ($current === false ? 'false' : ''));
?>
<div class="space-y-2" role="radiogroup">
    <?php foreach ($opts as $val => $label): ?>
        <label class="flex items-center gap-3 p-3 md:p-4 rounded-xl bg-paper dark:bg-deep border-2 border-mist/15 hover:border-primary/40 cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
            <input type="radio"
                   name="<?= e($key) ?>"
                   value="<?= e((string) $val) ?>"
                   <?= ((string) $current === (string) $val) ? 'checked' : '' ?>
                   data-autosave
                   class="w-5 h-5 accent-primary shrink-0">
            <span class="text-ink dark:text-pale"><?= e($label) ?></span>
        </label>
    <?php endforeach; ?>
</div>
