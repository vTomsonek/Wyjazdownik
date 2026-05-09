<?php /** Plecak z wystającą mapą - flat, bold lines */ ?>
<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto"
     role="img" aria-label="Plecak z mapą">
    <!-- Pasek górny plecaka -->
    <path d="M30 14 Q30 8 36 8 L44 8 Q50 8 50 14 L50 22"
          fill="none" stroke="#1A1A2E" stroke-width="3" stroke-linecap="round"/>
    <!-- Korpus plecaka -->
    <rect x="18" y="20" width="44" height="48" rx="8" fill="#FF6B35" stroke="#1A1A2E" stroke-width="3"/>
    <!-- Kieszeń -->
    <rect x="26" y="36" width="28" height="20" rx="4" fill="#FFD23F" stroke="#1A1A2E" stroke-width="2"/>
    <!-- Wystająca mapa -->
    <g transform="translate(38, 4) rotate(15)">
        <rect width="22" height="14" rx="1" fill="#FFFFFF" stroke="#1A1A2E" stroke-width="2"/>
        <path d="M2 5 L8 7 L14 4 L20 6" stroke="#FF6B35" stroke-width="1.5" fill="none"/>
        <circle cx="14" cy="9" r="1.5" fill="#2EC4B6"/>
    </g>
    <!-- Zip -->
    <line x1="40" y1="44" x2="40" y2="52" stroke="#1A1A2E" stroke-width="2" stroke-linecap="round"/>
</svg>
