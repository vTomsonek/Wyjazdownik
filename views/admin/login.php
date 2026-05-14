<?php
/**
 * Ekran logowania admina (magic link).
 *
 * @var string|null $flashSuccess
 * @var string|null $flashError
 * @var string|null $flashEmail
 * @var string|null $devMagicLink
 */
use App\Helpers\Csrf;

$flashSuccess = $flashSuccess ?? null;
$flashError   = $flashError   ?? null;
$flashEmail   = $flashEmail   ?? '';
$devMagicLink = $devMagicLink ?? null;
?>
<section class="min-h-[calc(100vh-72px)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 animate-float-slow">
                <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
            </div>
            <h1 class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale mb-2">
                Zaloguj się
            </h1>
            <p class="text-mist">
                Wpisz email - wyślemy ci link do zalogowania.
            </p>
        </div>

        <?php if ($devMagicLink !== null): ?>
            <!-- Dev-only: ostatni magic link, żeby nie skakać do skrzynki -->
            <div class="mb-4 p-4 rounded-2xl bg-accent/15 border border-accent/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-accent mb-2">
                    🔧 Dev mode - magic link
                </p>
                <a href="<?= e($devMagicLink) ?>"
                   class="block break-all text-sm font-mono text-ink dark:text-pale underline hover:text-primary">
                    <?= e($devMagicLink) ?>
                </a>
                <p class="text-xs text-mist mt-2">
                    W trybie produkcyjnym ten link byłby tylko w mailu.
                </p>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess !== null): ?>
            <div class="mb-4 p-4 rounded-2xl bg-secondary/10 border border-secondary/30 text-sm text-ink dark:text-pale">
                ✅ <?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError !== null): ?>
            <div class="mb-4 p-4 rounded-2xl bg-red-100 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-sm text-red-700 dark:text-red-300">
                ⚠️ <?= e($flashError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/admin/login')) ?>"
              class="bg-paper dark:bg-deep rounded-3xl border border-mist/15 p-6 md:p-8 shadow-pop">

            <?= Csrf::field() ?>

            <label for="email" class="block text-sm font-medium text-ink dark:text-pale mb-2">
                Adres email
            </label>
            <input type="email" id="email" name="email" required
                   value="<?= e((string) $flashEmail) ?>"
                   placeholder="ty@przyklad.pl"
                   autocomplete="email" autofocus
                   class="w-full px-4 py-3 rounded-xl bg-cream dark:bg-night
                          border-2 border-mist/20 focus:border-primary
                          text-ink dark:text-pale placeholder-mist/60
                          transition outline-none">

            <button type="submit"
                    class="w-full mt-4 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold
                           hover:bg-primary hover:scale-[1.01] transition shadow-pop">
                Wyślij magic link
            </button>

            <p class="mt-4 text-xs text-mist text-center leading-relaxed">
                Bez hasła. Pierwsze logowanie automatycznie tworzy konto - od razu możesz zakładać wyjazdy i zapraszać znajomych.
            </p>
        </form>
    </div>
</section>
