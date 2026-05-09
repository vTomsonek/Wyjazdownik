<?php
/**
 * Mockup do hero - laptop/telefon z fragmentem podsumowania.
 * Stylizowany, minimalistyczny - pokazuje co użytkownik dostanie.
 */
?>
<svg viewBox="0 0 480 520" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto"
     role="img" aria-label="Mockup ekranu z podsumowaniem wyjazdu">

    <!-- Tło z subtelnym poświatą -->
    <defs>
        <radialGradient id="heroBgGlow" cx="50%" cy="40%" r="55%">
            <stop offset="0%"   stop-color="#FF6B35" stop-opacity="0.25"/>
            <stop offset="100%" stop-color="#FF6B35" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="heroPhoneGrad" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%"   stop-color="#FFFFFF" class="dark:[stop-color:#1A2332]"/>
            <stop offset="100%" stop-color="#FFF8F0" class="dark:[stop-color:#0F1419]"/>
        </linearGradient>
    </defs>

    <circle cx="240" cy="240" r="220" fill="url(#heroBgGlow)"/>

    <!-- Telefon -->
    <g transform="translate(120, 40)">
        <!-- Cień -->
        <rect x="6" y="14" width="240" height="450" rx="32" fill="#1A1A2E" opacity="0.18"/>
        <!-- Obudowa -->
        <rect x="0" y="0" width="240" height="450" rx="32" fill="#1A1A2E"/>
        <!-- Ekran -->
        <rect x="10" y="14" width="220" height="422" rx="22" fill="url(#heroPhoneGrad)"/>
        <!-- Notch -->
        <rect x="95" y="20" width="50" height="8" rx="4" fill="#1A1A2E"/>

        <!-- Treść ekranu -->
        <g transform="translate(20, 44)">
            <!-- Tytuł sekcji -->
            <text x="0" y="14" font-family="Inter, sans-serif" font-size="9" fill="#6B7280">PODSUMOWANIE</text>
            <text x="0" y="32" font-family="'Bricolage Grotesque', sans-serif"
                  font-size="16" font-weight="700" fill="#1A1A2E"
                  class="dark:[fill:#F0F4F8]">Lato 2026</text>

            <!-- Heatmapa kalendarza (mini) -->
            <text x="0" y="58" font-family="Inter, sans-serif" font-size="8"
                  fill="#6B7280">📅 Najlepsze terminy</text>
            <g transform="translate(0, 64)">
                <?php
                // 7 kolumn × 4 rzędy = mini heatmapa
                $colors = ['#E5E7EB', '#FED7AA', '#FDBA74', '#FB923C', '#F97316', '#EA580C', '#C2410C'];
                for ($r = 0; $r < 4; $r++) {
                    for ($c = 0; $c < 7; $c++) {
                        $intensity = ($r + $c) % 7;
                        $color = $colors[$intensity];
                        $x = $c * 26;
                        $y = $r * 22;
                        echo '<rect x="' . $x . '" y="' . $y . '" width="22" height="18" rx="3" fill="' . $color . '"/>';
                    }
                }
                ?>
            </g>

            <!-- Sekcja z odznakami -->
            <text x="0" y="190" font-family="Inter, sans-serif" font-size="8"
                  fill="#6B7280">🏆 Ranking ekipy</text>

            <!-- Karta odznaki 1 -->
            <g transform="translate(0, 200)">
                <rect width="190" height="36" rx="10" fill="#FFD23F" opacity="0.15"/>
                <circle cx="18" cy="18" r="11" fill="#FFD23F"/>
                <text x="18" y="22" font-size="14" text-anchor="middle">🥙</text>
                <text x="36" y="16" font-family="Inter, sans-serif" font-size="9" font-weight="600"
                      fill="#1A1A2E" class="dark:[fill:#F0F4F8]">Kebab Master</text>
                <text x="36" y="28" font-family="Inter, sans-serif" font-size="8" fill="#6B7280">Bartek</text>
            </g>

            <!-- Karta odznaki 2 -->
            <g transform="translate(0, 244)">
                <rect width="190" height="36" rx="10" fill="#2EC4B6" opacity="0.15"/>
                <circle cx="18" cy="18" r="11" fill="#2EC4B6"/>
                <text x="18" y="22" font-size="14" text-anchor="middle">🥾</text>
                <text x="36" y="16" font-family="Inter, sans-serif" font-size="9" font-weight="600"
                      fill="#1A1A2E" class="dark:[fill:#F0F4F8]">Maszyna</text>
                <text x="36" y="28" font-family="Inter, sans-serif" font-size="8" fill="#6B7280">Ola</text>
            </g>

            <!-- Karta odznaki 3 -->
            <g transform="translate(0, 288)">
                <rect width="190" height="36" rx="10" fill="#FF6B35" opacity="0.15"/>
                <circle cx="18" cy="18" r="11" fill="#FF6B35"/>
                <text x="18" y="22" font-size="14" text-anchor="middle">🏖️</text>
                <text x="36" y="16" font-family="Inter, sans-serif" font-size="9" font-weight="600"
                      fill="#1A1A2E" class="dark:[fill:#F0F4F8]">Plażowicz</text>
                <text x="36" y="28" font-family="Inter, sans-serif" font-size="8" fill="#6B7280">Tomek</text>
            </g>

            <!-- CTA -->
            <g transform="translate(0, 340)">
                <rect width="200" height="32" rx="16" fill="#FF6B35"/>
                <text x="100" y="20" font-family="Inter, sans-serif" font-size="10" font-weight="600"
                      fill="#FFFFFF" text-anchor="middle">Tryb prezentacji</text>
            </g>
        </g>
    </g>

    <!-- Floating ikony dookoła telefonu -->
    <g class="animate-float">
        <circle cx="60"  cy="80"  r="26" fill="#FFD23F"/>
        <text   x="60"   y="89"   font-size="22" text-anchor="middle">🎒</text>
    </g>
    <g class="animate-float-slow">
        <circle cx="420" cy="120" r="24" fill="#2EC4B6"/>
        <text   x="420"  y="128"  font-size="20" text-anchor="middle">✈️</text>
    </g>
    <g class="animate-float">
        <circle cx="40"  cy="320" r="22" fill="#FF6B35"/>
        <text   x="40"   y="328"  font-size="18" text-anchor="middle">🏖️</text>
    </g>
    <g class="animate-float-slow">
        <circle cx="430" cy="380" r="22" fill="#FF6B35" opacity="0.8"/>
        <text   x="430"  y="388"  font-size="18" text-anchor="middle">🗺️</text>
    </g>
</svg>
