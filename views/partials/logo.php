<?php
/**
 * Logo "wyjazdownik.pl" - kanoniczna forma marki.
 *
 * Logo NIE ustawia własnego rozmiaru fontu - dziedziczy od rodzica. Dzięki temu
 * ten sam komponent można renderować w headerze (text-xl) i w hero (text-6xl)
 * bez duplikowania kodu.
 *
 * Triki:
 * - "ı" (U+0131, dotless i) zamiast "i", żeby usunąć natywną kropkę i postawić własną.
 * - Pomarańczowa kropka jest pozycjonowana w em - skaluje się proporcjonalnie do font-size.
 * - "j" zostawiamy zwykłe - jego natywna kropka jest częścią glifu i pasuje do reszty.
 */
?>
<span class="font-display font-bold tracking-tight text-ink dark:text-pale leading-none">
    wyjazdown<span class="relative inline-block">ı<span
        aria-hidden="true"
        class="absolute left-1/2 -translate-x-1/2 top-[-0.08em]
               w-[0.22em] h-[0.22em] rounded-full bg-primary"
    ></span></span>k<span class="text-primary">.pl</span>
</span>
