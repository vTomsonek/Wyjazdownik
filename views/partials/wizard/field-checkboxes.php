<?php
/** Pole checkboxów (multi-choice) - $key, $meta, $current */
$opts    = $meta['options'] ?? [];
$max     = (int) ($meta['max_selections'] ?? 0);
$current = is_array($current) ? array_map('strval', $current) : [];
?>
<div class="grid sm:grid-cols-2 gap-2"
     role="group"
     <?= $max > 0 ? 'data-max-selections="' . (int) $max . '"' : '' ?>>
    <?php foreach ($opts as $val => $label): ?>
        <label class="flex items-center gap-3 p-3 rounded-xl bg-paper dark:bg-deep border-2 border-mist/15 hover:border-primary/40 cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
            <input type="checkbox"
                   name="<?= e($key) ?>[]"
                   value="<?= e((string) $val) ?>"
                   <?= in_array((string) $val, $current, true) ? 'checked' : '' ?>
                   data-autosave-multi="<?= e($key) ?>"
                   class="w-5 h-5 accent-primary shrink-0">
            <span class="text-ink dark:text-pale text-sm md:text-base"><?= e($label) ?></span>
        </label>
    <?php endforeach; ?>
</div>
<?php if ($max > 0): ?>
<p class="mt-2 text-xs text-mist">
    Wybrano: <span data-multi-count="<?= e($key) ?>"><?= count($current) ?></span> z <?= (int) $max ?>
</p>
<?php endif; ?>
