<?php
/**
 * Logowanie admina (magic link) - landing v2 design.
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

<?php require BASE_PATH . '/views/partials/landing/nav.php'; ?>

<main class="auth">
    <div class="auth-card">

        <div class="auth-head">
            <div class="auth-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></div>
            <h1>Zaloguj się</h1>
            <p>Wpisz email, wyślemy ci link do zalogowania.</p>
        </div>

        <?php if ($devMagicLink !== null): ?>
            <div class="auth-flash auth-flash--dev">
                <div class="dev-label">🔧 Dev mode · magic link</div>
                <a href="<?= e($devMagicLink) ?>"><?= e($devMagicLink) ?></a>
                <p>W trybie produkcyjnym ten link byłby tylko w mailu.</p>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess !== null): ?>
            <div class="auth-flash auth-flash--success">
                <span class="iconify" data-icon="ph:check-circle-fill" style="font-size:20px;flex-shrink:0;margin-top:1px"></span>
                <span><?= e($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashError !== null): ?>
            <div class="auth-flash auth-flash--error">
                <span class="iconify" data-icon="ph:warning-circle-fill" style="font-size:20px;flex-shrink:0;margin-top:1px"></span>
                <span><?= e($flashError) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/admin/login')) ?>">
            <?= Csrf::field() ?>

            <label for="email" class="auth-label">Adres email</label>
            <input type="email" id="email" name="email" required
                   value="<?= e((string) $flashEmail) ?>"
                   placeholder="ty@przyklad.pl"
                   autocomplete="email" autofocus
                   class="auth-input<?= $flashError !== null ? ' has-error' : '' ?>">

            <button type="submit" class="btn btn-primary btn-lg auth-submit">
                Wyślij magic link
                <span class="iconify" data-icon="ph:paper-plane-tilt-fill"></span>
            </button>

            <p class="auth-foot">
                Bez hasła. Pierwsze logowanie automatycznie tworzy konto.
                Od razu możesz zakładać wyjazdy i zapraszać znajomych.
            </p>
        </form>
    </div>
</main>

<?php require BASE_PATH . '/views/partials/landing/footer.php'; ?>
