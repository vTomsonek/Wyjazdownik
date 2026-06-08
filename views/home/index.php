<?php
/**
 * Landing v2 - redesign Wyjazdownik.
 * Sekcje wpinane po kolei w kolejnych ETAPach.
 *
 * @var bool       $isDev
 * @var array|null $devData
 */
require BASE_PATH . '/views/partials/landing/nav.php';
?>

<main id="top">
    <!-- HERO -->
    <?php require BASE_PATH . '/views/partials/landing/hero.php'; ?>

    <!-- PROBLEM -->
    <?php require BASE_PATH . '/views/partials/landing/problem.php'; ?>

    <!-- HOW IT WORKS -->
    <?php require BASE_PATH . '/views/partials/landing/how-it-works.php'; ?>

    <!-- FEATURES -->
    <?php require BASE_PATH . '/views/partials/landing/features.php'; ?>

    <!-- MAP FEATURE -->
    <?php require BASE_PATH . '/views/partials/landing/map-feature.php'; ?>

    <!-- AUDIENCE -->
    <?php require BASE_PATH . '/views/partials/landing/audience.php'; ?>

    <!-- BADGES (dark) -->
    <?php require BASE_PATH . '/views/partials/landing/badges.php'; ?>

    <!-- FAQ -->
    <?php require BASE_PATH . '/views/partials/landing/faq.php'; ?>

    <!-- FINAL CTA -->
    <?php require BASE_PATH . '/views/partials/landing/final-cta.php'; ?>
</main>

<?php require BASE_PATH . '/views/partials/landing/footer.php'; ?>
