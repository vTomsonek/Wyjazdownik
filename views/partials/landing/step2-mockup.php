<?php /** Mockup kroku 2 - wizard uczestnika */ ?>
<svg viewBox="0 0 320 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto"
     role="img" aria-label="Mockup wizarda uczestnika">
    <!-- Telefon -->
    <rect x="80" y="0" width="160" height="200" rx="20" fill="#1A1A2E"/>
    <rect x="86" y="8" width="148" height="184" rx="14" fill="#FFFFFF" class="dark:[fill:#1A2332]"/>

    <!-- Progress bar -->
    <text x="92" y="22" font-family="Inter, sans-serif" font-size="7" fill="#6B7280">Krok 5 z 12</text>
    <rect x="92"  y="26" width="136" height="4" rx="2" fill="#E5E7EB" class="dark:[fill:#374151]"/>
    <rect x="92"  y="26" width="56"  height="4" rx="2" fill="#FF6B35"/>

    <!-- Pytanie -->
    <text x="92" y="46" font-family="'Bricolage Grotesque', sans-serif" font-size="11"
          font-weight="700" fill="#1A1A2E" class="dark:[fill:#F0F4F8]">Jaki budżet</text>
    <text x="92" y="58" font-family="'Bricolage Grotesque', sans-serif" font-size="11"
          font-weight="700" fill="#1A1A2E" class="dark:[fill:#F0F4F8]">planujesz?</text>

    <!-- Opcje -->
    <g transform="translate(92, 70)">
        <rect width="136" height="20" rx="10" fill="#FFF8F0" class="dark:[fill:#0F1419]" stroke="#E5E7EB"/>
        <text x="10" y="13" font-family="Inter, sans-serif" font-size="9"
              fill="#1A1A2E" class="dark:[fill:#F0F4F8]">Do 1500 zł</text>
    </g>
    <g transform="translate(92, 96)">
        <rect width="136" height="20" rx="10" fill="#FF6B35"/>
        <text x="10" y="13" font-family="Inter, sans-serif" font-size="9" font-weight="600"
              fill="#FFFFFF">1500 - 3000 zł ✓</text>
    </g>
    <g transform="translate(92, 122)">
        <rect width="136" height="20" rx="10" fill="#FFF8F0" class="dark:[fill:#0F1419]" stroke="#E5E7EB"/>
        <text x="10" y="13" font-family="Inter, sans-serif" font-size="9"
              fill="#1A1A2E" class="dark:[fill:#F0F4F8]">3000 - 5000 zł</text>
    </g>
    <g transform="translate(92, 148)">
        <rect width="136" height="20" rx="10" fill="#FFF8F0" class="dark:[fill:#0F1419]" stroke="#E5E7EB"/>
        <text x="10" y="13" font-family="Inter, sans-serif" font-size="9"
              fill="#1A1A2E" class="dark:[fill:#F0F4F8]">5000+ zł</text>
    </g>

    <!-- Przycisk Dalej -->
    <rect x="180" y="174" width="48" height="14" rx="7" fill="#FF6B35"/>
    <text x="204" y="184" font-family="Inter, sans-serif" font-size="8" font-weight="600"
          fill="#FFFFFF" text-anchor="middle">Dalej</text>

    <!-- Floating ikonki -->
    <text x="32" y="60"  font-size="22">💰</text>
    <text x="270" y="100" font-size="22">📝</text>
</svg>
