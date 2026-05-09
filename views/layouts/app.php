<?php
/**
 * Główny layout aplikacji.
 *
 * @var string      $content      pre-rendered HTML widoku wewnętrznego
 * @var string|null $title        tytuł strony (<title>)
 * @var string|null $description  opis strony (meta + OG)
 * @var string|null $ogImage      ścieżka do obrazka OG (1200x630)
 * @var string|null $bodyClass    dodatkowe klasy do body (np. tryb prezentacji)
 */
$title       = $title       ?? 'Wyjazdownik.pl - ogarnij wakacje ze znajomymi raz na zawsze';
$description = $description ?? 'Polskie narzędzie do uzgadniania wspólnych wakacji w ekipie. Każdy znajomy wypełnia ankietę, a wy wszyscy oglądacie wspólny plan z rekomendacjami i rankingami.';
$ogImage     = $ogImage     ?? asset('assets/img/og-image.png');
$bodyClass   = $bodyClass   ?? '';
$canonical   = (string) url($_SERVER['REQUEST_URI'] ?? '/');
?><!DOCTYPE html>
<html lang="pl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">

    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Wyjazdownik.pl">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <!-- Structured data: WebApplication -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Wyjazdownik.pl",
        "description": <?= json_encode($description, JSON_UNESCAPED_UNICODE) ?>,
        "url": <?= json_encode((string) url('/'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        "inLanguage": "pl-PL",
        "applicationCategory": "TravelApplication",
        "operatingSystem": "Web",
        "offers": { "@type": "Offer", "price": "0", "priceCurrency": "PLN" }
    }
    </script>

    <!-- Dark mode bootstrap (przed CSS żeby nie było mignięcia) -->
    <script>
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) { /* noop */ }
        })();
    </script>

    <!-- Tailwind CDN + konfiguracja brandu -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary:   { DEFAULT: '#FF6B35', dark: '#E55A2B' },
                        secondary: '#2EC4B6',
                        accent:    '#FFD23F',
                        cream:     '#FFF8F0',
                        paper:     '#FFFFFF',
                        ink:       '#1A1A2E',
                        mist:      '#6B7280',
                        night:     '#0F1419',
                        deep:      '#1A2332',
                        pale:      '#F0F4F8',
                    },
                    fontFamily: {
                        display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
                        body:    ['Inter', 'system-ui', 'sans-serif'],
                        accent:  ['Caveat', 'cursive'],
                    },
                    screens: {
                        '3xl': '1920px',
                        '4xl': '2560px',
                    },
                    boxShadow: {
                        'pop': '0 8px 24px -8px rgba(255, 107, 53, 0.35)',
                        'pop-lg': '0 20px 60px -16px rgba(255, 107, 53, 0.45)',
                    },
                    animation: {
                        'float':       'float 4s ease-in-out infinite',
                        'float-slow':  'float 6s ease-in-out infinite',
                        'spin-slow':   'spin 12s linear infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%':      { transform: 'translateY(-8px)' },
                        },
                    },
                },
            },
        };
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Caveat:wght@400..700&family=Inter:wght@400..700&display=swap">

    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
</head>
<body class="font-body bg-cream text-ink dark:bg-night dark:text-pale min-h-screen flex flex-col antialiased <?= e($bodyClass) ?>">

    <?php require BASE_PATH . '/views/partials/header.php'; ?>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <?php require BASE_PATH . '/views/partials/footer.php'; ?>

    <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
