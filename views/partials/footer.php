<footer class="mt-16 border-t border-mist/15 bg-cream dark:bg-night">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-10
                flex flex-col sm:flex-row items-center justify-between gap-6">

        <div class="flex items-center gap-3 text-sm text-mist">
            <div class="w-8 h-8">
                <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
            </div>
            <span>
                Stworzone z miłością do dobrych wyjazdów.
                <span class="hidden md:inline"> &middot; &copy; <?= date('Y') ?> wyjazdownik.pl</span>
            </span>
        </div>

        <nav class="flex items-center gap-5 text-sm text-mist">
            <a href="<?= e(url('/')) ?>" class="hover:text-primary transition">Start</a>
            <a href="<?= e(url('/admin/login')) ?>" class="hover:text-primary transition">Zaloguj</a>
            <a href="mailto:<?= e(env('MAIL_FROM_ADDRESS', 'noreply@wyjazdownik.pl')) ?>"
               class="hover:text-primary transition">Kontakt</a>
        </nav>
    </div>
</footer>
