<?php
/** Grid języków - $key, $meta, $current */
$languages = $meta['languages'] ?? [];
$levels    = $meta['levels']    ?? [];
$popular   = $meta['popular']   ?? array_keys($languages);
$current   = is_array($current) ? $current : [];
?>
<div class="space-y-2 p-4 rounded-2xl bg-paper dark:bg-deep border border-mist/15"
     data-language-grid="<?= e($key) ?>">

    <div class="flex flex-wrap gap-2 mb-3">
        <button type="button" data-lang-quick="none"
                class="px-3 py-1 rounded-full bg-mist/15 text-ink dark:text-pale text-xs font-medium hover:bg-mist/25 transition">
            Nie znam żadnego
        </button>
        <button type="button" data-lang-toggle-more
                class="px-3 py-1 rounded-full bg-mist/15 text-ink dark:text-pale text-xs font-medium hover:bg-mist/25 transition">
            Pokaż więcej
        </button>
    </div>

    <?php foreach ($languages as $lang => $label): ?>
        <?php $isPopular = in_array($lang, $popular, true); ?>
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 py-2 border-t border-mist/10 first:border-t-0"
             data-lang-row
             <?= $isPopular ? '' : 'data-lang-extra hidden' ?>>
            <span class="font-medium text-ink dark:text-pale w-full sm:w-32 shrink-0"><?= e($label) ?></span>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($levels as $lvl => $lvlLabel): $checked = ($current[$lang] ?? 'none') === $lvl; ?>
                    <label class="cursor-pointer">
                        <input type="radio"
                               name="languages[<?= e($lang) ?>]"
                               value="<?= e($lvl) ?>"
                               <?= $checked ? 'checked' : '' ?>
                               data-autosave-lang
                               class="sr-only peer">
                        <span class="block px-3 py-1.5 rounded-full bg-mist/10 text-ink dark:text-pale text-xs font-medium peer-checked:bg-primary peer-checked:text-white transition">
                            <?= e($lvlLabel) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
