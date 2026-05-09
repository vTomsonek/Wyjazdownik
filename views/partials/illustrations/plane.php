<?php /** Samolot jednym pociągnięciem */ ?>
<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto"
     role="img" aria-label="Samolot">
    <!-- Smuga - pojedyncza linia -->
    <path d="M8 56 Q24 48 40 40 T72 18"
          fill="none" stroke="#FF6B35" stroke-width="3"
          stroke-linecap="round" stroke-dasharray="2 4" opacity="0.5"/>

    <!-- Sylwetka samolotu (jeden ciągły kontur, flat shading) -->
    <g transform="translate(40, 30) rotate(-25)">
        <path d="M0 0 L18 -2 L26 -1 L24 4 L18 5 L4 4 L-4 8 L-8 7 L-2 2 L-12 1 L-14 -1 L-2 -1 Z"
              fill="#2EC4B6" stroke="#1A1A2E" stroke-width="2" stroke-linejoin="round"/>
        <!-- Iluminator -->
        <circle cx="14" cy="1" r="1.5" fill="#FFFFFF" stroke="#1A1A2E" stroke-width="0.8"/>
        <circle cx="8"  cy="1" r="1.5" fill="#FFFFFF" stroke="#1A1A2E" stroke-width="0.8"/>
    </g>
</svg>
