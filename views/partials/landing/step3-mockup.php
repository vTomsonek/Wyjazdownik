<?php /** Mockup kroku 3 - ekran TV z podsumowaniem */ ?>
<svg viewBox="0 0 320 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto"
     role="img" aria-label="Mockup ekranu telewizora z podsumowaniem">
    <!-- Stojak telewizora -->
    <rect x="120" y="178" width="80" height="6" rx="3" fill="#1A1A2E"/>
    <path d="M150 168 L130 178 L190 178 L170 168 Z" fill="#1A1A2E"/>

    <!-- Telewizor - obudowa -->
    <rect x="6" y="6" width="308" height="168" rx="8" fill="#1A1A2E"/>
    <rect x="14" y="14" width="292" height="152" rx="4" fill="#0F1419"/>

    <!-- Ekran zawartość -->
    <text x="28" y="36" font-family="'Bricolage Grotesque', sans-serif" font-size="14"
          font-weight="700" fill="#F0F4F8">Lato 2026 z ekipą</text>
    <text x="28" y="50" font-family="Inter, sans-serif" font-size="8"
          fill="#FFD23F">📅 Najlepszy termin: 15-22 lipca</text>

    <!-- Heatmapa -->
    <g transform="translate(28, 60)">
        <?php
        $colors = ['#374151', '#7F1D1D', '#C2410C', '#F97316', '#FB923C', '#FED7AA', '#10B981'];
        for ($r = 0; $r < 3; $r++) {
            for ($c = 0; $c < 14; $c++) {
                $intensity = ($r * 2 + $c) % 7;
                $color = $colors[$intensity];
                $x = $c * 11;
                $y = $r * 11;
                echo '<rect x="' . $x . '" y="' . $y . '" width="9" height="9" rx="1.5" fill="' . $color . '"/>';
            }
        }
        ?>
    </g>

    <!-- Sekcja odznak -->
    <g transform="translate(28, 110)">
        <rect width="76" height="46" rx="8" fill="#FFD23F" opacity="0.2"/>
        <text x="10" y="20" font-size="20">🥙</text>
        <text x="10" y="36" font-family="Inter, sans-serif" font-size="7" font-weight="600" fill="#FFD23F">Kebab Master</text>
        <text x="10" y="44" font-family="Inter, sans-serif" font-size="6" fill="#9CA3AF">Bartek</text>
    </g>
    <g transform="translate(112, 110)">
        <rect width="76" height="46" rx="8" fill="#2EC4B6" opacity="0.2"/>
        <text x="10" y="20" font-size="20">🏖️</text>
        <text x="10" y="36" font-family="Inter, sans-serif" font-size="7" font-weight="600" fill="#2EC4B6">Plażowicz</text>
        <text x="10" y="44" font-family="Inter, sans-serif" font-size="6" fill="#9CA3AF">Tomek</text>
    </g>
    <g transform="translate(196, 110)">
        <rect width="76" height="46" rx="8" fill="#FF6B35" opacity="0.2"/>
        <text x="10" y="20" font-size="20">🥾</text>
        <text x="10" y="36" font-family="Inter, sans-serif" font-size="7" font-weight="600" fill="#FF6B35">Maszyna</text>
        <text x="10" y="44" font-family="Inter, sans-serif" font-size="6" fill="#9CA3AF">Ola</text>
    </g>

    <!-- Wskaźnik trybu prezentacji -->
    <g transform="translate(248, 22)">
        <rect width="44" height="14" rx="7" fill="#FF6B35"/>
        <text x="22" y="10" font-family="Inter, sans-serif" font-size="6" font-weight="600"
              fill="#FFFFFF" text-anchor="middle">📺 TRYB TV</text>
    </g>
</svg>
