<?php
/**
 * Mascotka - słoneczko w okularach przeciwsłonecznych.
 * Inline SVG, dziedziczy currentColor; rozmiar przez wrapper (np. w-16 h-16).
 */
?>
<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" class="w-full h-full" role="img"
     aria-label="Słoneczko - mascotka Wyjazdownik">
    <!-- Promienie -->
    <g stroke="#FF6B35" stroke-width="2.5" stroke-linecap="round">
        <line x1="32" y1="4"  x2="32" y2="11"/>
        <line x1="32" y1="53" x2="32" y2="60"/>
        <line x1="4"  y1="32" x2="11" y2="32"/>
        <line x1="53" y1="32" x2="60" y2="32"/>
        <line x1="11.5" y1="11.5" x2="16.5" y2="16.5"/>
        <line x1="47.5" y1="47.5" x2="52.5" y2="52.5"/>
        <line x1="11.5" y1="52.5" x2="16.5" y2="47.5"/>
        <line x1="47.5" y1="16.5" x2="52.5" y2="11.5"/>
    </g>

    <!-- Słońce -->
    <circle cx="32" cy="32" r="18" fill="#FFD23F"/>
    <circle cx="32" cy="32" r="18" fill="none" stroke="#FF6B35" stroke-width="2"/>

    <!-- Okulary przeciwsłoneczne -->
    <g fill="#1A1A2E">
        <ellipse cx="25" cy="29" rx="5.5" ry="4.5"/>
        <ellipse cx="39" cy="29" rx="5.5" ry="4.5"/>
        <rect x="29.5" y="28.5" width="5" height="1.4" rx="0.7"/>
        <!-- Mostek między okularami -->
        <path d="M19 27 L17 25.5" stroke="#1A1A2E" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M45 27 L47 25.5" stroke="#1A1A2E" stroke-width="1.5" stroke-linecap="round"/>
    </g>
    <!-- Refleks na okularach -->
    <g fill="#FFFFFF" opacity="0.5">
        <ellipse cx="23" cy="27.5" rx="1.4" ry="1.0"/>
        <ellipse cx="37" cy="27.5" rx="1.4" ry="1.0"/>
    </g>

    <!-- Uśmiech -->
    <path d="M24 38 Q32 45 40 38" stroke="#1A1A2E" stroke-width="2.2" fill="none"
          stroke-linecap="round"/>
</svg>
