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

    <!-- Critical CSS inline - LCP element rysuje sie bez czekania na tailwind.css -->
    <style>
        *,::before,::after{box-sizing:border-box;border:0 solid #e5e7eb}
        html{line-height:1.5;-webkit-text-size-adjust:100%;text-size-adjust:100%;scroll-behavior:smooth}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;font-family:Inter,system-ui,sans-serif;-webkit-font-smoothing:antialiased;background:#FFF8F0;color:#1A1A2E}
        html.dark body{background:#0F1419;color:#F0F4F8}
        main{flex:1 1 0%}
        h1,h2,h3,h4{font-family:"Bricolage Grotesque",system-ui,sans-serif;font-weight:700;margin:0}
        a{color:inherit;text-decoration:inherit}
        img,svg{display:block;max-width:100%;height:auto}
        button{cursor:pointer;font-family:inherit;background:none;border:0;padding:0;color:inherit}
        /* --- Critical Tailwind subset dla hero (above the fold) --- */
        .relative{position:relative}.absolute{position:absolute}.inset-0{inset:0}.-z-10{z-index:-10}.overflow-hidden{overflow:hidden}
        .mx-auto{margin-left:auto;margin-right:auto}.max-w-7xl{max-width:80rem}.max-w-xl{max-width:36rem}.max-w-md{max-width:28rem}
        .px-4{padding-left:1rem;padding-right:1rem}.pt-10{padding-top:2.5rem}.pb-20{padding-bottom:5rem}
        .grid{display:grid}.gap-10{gap:2.5rem}.gap-6{gap:1.5rem}.gap-3{gap:.75rem}.gap-2{gap:.5rem}
        .flex{display:flex}.flex-col{flex-direction:column}.flex-wrap{flex-wrap:wrap}.inline-flex{display:inline-flex}
        .items-center{align-items:center}.self-start{align-self:flex-start}
        .text-center{text-align:center}.text-left{text-align:left}
        .rounded-full{border-radius:9999px}.rounded-2xl{border-radius:1rem}
        .px-3{padding-left:.75rem;padding-right:.75rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}
        .py-1\.5{padding-top:.375rem;padding-bottom:.375rem}.py-3{padding-top:.75rem;padding-bottom:.75rem}.mt-2{margin-top:.5rem}
        .text-xs{font-size:.75rem;line-height:1rem}.text-sm{font-size:.875rem;line-height:1.25rem}.text-base{font-size:1rem;line-height:1.5rem}
        .text-lg{font-size:1.125rem;line-height:1.75rem}.text-4xl{font-size:2.25rem;line-height:2.5rem}
        .font-medium{font-weight:500}.font-semibold{font-weight:600}.font-bold{font-weight:700}
        .font-display{font-family:"Bricolage Grotesque",system-ui,sans-serif}
        .tracking-tight{letter-spacing:-.025em}.leading-relaxed{line-height:1.625}
        .text-primary{color:#FF6B35}.text-primary-deep{color:#C2410C}.text-white{color:#fff}.text-mist{color:#6B7280}.text-ink{color:#1A1A2E}
        .dark .dark\:text-pale{color:#F0F4F8}.dark .dark\:text-primary{color:#FF6B35}
        .bg-primary{background-color:#FF6B35}.bg-primary-deep{background-color:#C2410C}.bg-primary\/10{background-color:rgb(255 107 53 / .1)}.bg-primary\/15{background-color:rgb(255 107 53 / .15)}
        .inline-block{display:inline-block}.w-5{width:1.25rem}.h-5{height:1.25rem}.w-4{width:1rem}.h-4{height:1rem}
        .leading-\[1\.05\]{line-height:1.05}
        @media (min-width:640px){.sm\:text-sm{font-size:.875rem;line-height:1.25rem}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:text-5xl{font-size:3rem;line-height:1}}
        @media (min-width:768px){.md\:pt-16{padding-top:4rem}.md\:pb-28{padding-bottom:7rem}.md\:text-6xl{font-size:3.75rem;line-height:1}.md\:text-xl{font-size:1.25rem;line-height:1.75rem}.md\:text-lg{font-size:1.125rem;line-height:1.75rem}}
        @media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}.lg\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.lg\:gap-16{gap:4rem}.lg\:mx-0{margin-left:0;margin-right:0}}
        @media (min-width:1920px){.\33xl\:pt-24{padding-top:6rem}.\33xl\:pb-36{padding-bottom:9rem}.\33xl\:max-w-2xl{max-width:42rem}.\33xl\:max-w-\[1600px\]{max-width:1600px}.\33xl\:text-2xl{font-size:1.5rem;line-height:2rem}.\33xl\:text-7xl{font-size:4.5rem;line-height:1}}
    </style>

    <!-- Tailwind - production build async (preload + media-print swap) -->
    <link rel="preload" as="style" href="<?= e(asset('assets/css/tailwind.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>"></noscript>

    <!-- Google Fonts - subset (tylko uzywane wagi) + display=swap + async load -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700&family=Inter:wght@400;600;700&display=swap">
    <link rel="stylesheet" media="print" onload="this.media='all'"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700&family=Inter:wght@400;600;700&display=swap">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700&family=Inter:wght@400;600;700&display=swap">
    </noscript>

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

    <!-- Twemoji - asynchronicznie, parse po idle (nie blokuje FCP/LCP) -->
    <style>
        img.emoji { height: 1em; width: 1em; margin: 0 .05em 0 .1em; vertical-align: -0.1em; display: inline-block; }
    </style>
    <script>
        (function () {
            const run = () => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js';
                s.crossOrigin = 'anonymous';
                s.async = true;
                s.onload = () => window.twemoji && twemoji.parse(document.body, {
                    folder: 'svg', ext: '.svg',
                    base: 'https://cdn.jsdelivr.net/gh/jdecked/twemoji@latest/assets/'
                });
                document.head.appendChild(s);
            };
            // Parse dopiero gdy przegladarka jest bezczynna (lub po pelnym load)
            if (document.readyState === 'complete') {
                ('requestIdleCallback' in window) ? requestIdleCallback(run, { timeout: 2000 }) : setTimeout(run, 1);
            } else {
                window.addEventListener('load', () => {
                    ('requestIdleCallback' in window) ? requestIdleCallback(run, { timeout: 2000 }) : setTimeout(run, 1);
                }, { once: true });
            }
        })();
    </script>
</body>
</html>
