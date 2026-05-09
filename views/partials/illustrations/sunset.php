<?php /** Słońce nad morzem - stylizowany horyzont */ ?>
<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto"
     role="img" aria-label="Słońce nad morzem">
    <!-- Słońce -->
    <circle cx="40" cy="38" r="14" fill="#FFD23F"/>
    <circle cx="40" cy="38" r="14" fill="none" stroke="#FF6B35" stroke-width="2"/>
    <!-- Promienie -->
    <g stroke="#FF6B35" stroke-width="2" stroke-linecap="round">
        <line x1="40" y1="14" x2="40" y2="20"/>
        <line x1="22" y1="22" x2="26" y2="26"/>
        <line x1="58" y1="22" x2="54" y2="26"/>
    </g>
    <!-- Linia horyzontu -->
    <line x1="6" y1="56" x2="74" y2="56" stroke="#1A1A2E" stroke-width="2" stroke-linecap="round"/>
    <!-- Fale -->
    <path d="M8 62 Q14 60 20 62 T32 62 T44 62 T56 62 T68 62 T74 62"
          fill="none" stroke="#2EC4B6" stroke-width="2" stroke-linecap="round"/>
    <path d="M10 70 Q16 68 22 70 T34 70 T46 70 T58 70 T70 70"
          fill="none" stroke="#2EC4B6" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
</svg>
