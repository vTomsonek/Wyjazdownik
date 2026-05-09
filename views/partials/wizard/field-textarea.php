<?php
/** Pole tekstowe - $key, $meta, $current */
$value = is_string($current) ? $current : '';
?>
<textarea name="<?= e($key) ?>"
          rows="4"
          maxlength="2000"
          data-autosave
          placeholder="Pisz swobodnie..."
          class="w-full px-4 py-3 rounded-xl bg-paper dark:bg-deep border-2 border-mist/15 focus:border-primary text-ink dark:text-pale outline-none transition resize-none"><?= e($value) ?></textarea>
