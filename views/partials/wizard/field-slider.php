<?php
/** Slider numeryczny - $key, $meta, $current */
$min  = (int) ($meta['min']  ?? 0);
$max  = (int) ($meta['max']  ?? 100);
$step = (int) ($meta['step'] ?? 1);
$unit = (string) ($meta['unit'] ?? '');
$value = is_numeric($current) ? (int) $current : (int) round(($min + $max) / 2);
$fmt = static function (int $n): string {
    return number_format($n, 0, ',', ' ');
};
?>
<div class="p-4 rounded-2xl bg-paper dark:bg-deep border border-mist/15">
    <div class="flex items-baseline gap-2 mb-3">
        <span class="font-display font-bold text-3xl md:text-4xl text-primary"
              data-slider-value="<?= e($key) ?>"
              data-slider-format="thousands"><?= e($fmt($value)) ?></span>
        <?php if ($unit): ?>
            <span class="text-mist text-lg"><?= e($unit) ?></span>
        <?php endif; ?>
    </div>
    <input type="range"
           name="<?= e($key) ?>"
           min="<?= $min ?>"
           max="<?= $max ?>"
           step="<?= $step ?>"
           value="<?= $value ?>"
           data-autosave
           data-slider-input="<?= e($key) ?>"
           class="w-full accent-primary">
    <div class="flex justify-between text-xs text-mist mt-2">
        <span><?= e($fmt($min)) ?><?= $unit ? ' ' . e($unit) : '' ?></span>
        <span><?= e($fmt($max)) ?><?= $unit ? ' ' . e($unit) : '' ?></span>
    </div>
</div>
