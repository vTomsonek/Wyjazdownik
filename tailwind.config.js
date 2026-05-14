/** @type {import('tailwindcss').Config} */
module.exports = {
    // Scan PHP, JS i HTML dla wszystkich uzywanych klas Tailwind
    content: [
        './views/**/*.php',
        './src/**/*.php',
        './public/**/*.php',
        './public/assets/js/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary:   { DEFAULT: '#FF6B35', dark: '#E55A2B', deep: '#C2410C' },
                secondary: '#2EC4B6',
                accent:    '#FFD23F',
                cream:     '#FFF8F0',
                paper:     '#FFFFFF',
                ink:       '#1A1A2E',
                mist:      '#6B7280',
                night:     '#0F1419',
                deep:      '#1A2332',
                pale:      '#F0F4F8',
            },
            fontFamily: {
                display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
                body:    ['Inter', 'system-ui', 'sans-serif'],
                accent:  ['Caveat', 'cursive'],
            },
            screens: {
                '3xl': '1920px',
                '4xl': '2560px',
            },
            boxShadow: {
                'pop':    '0 8px 24px -8px rgba(255, 107, 53, 0.35)',
                'pop-lg': '0 20px 60px -16px rgba(255, 107, 53, 0.45)',
            },
            animation: {
                'float':      'float 4s ease-in-out infinite',
                'float-slow': 'float 6s ease-in-out infinite',
                'spin-slow':  'spin 12s linear infinite',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%':      { transform: 'translateY(-8px)' },
                },
            },
        },
    },
    plugins: [],
    // Safelist - tylko klasy ktore Tailwind nie wykryje przez content scan
    // (czyli generowane stricte dynamicznie). Reszte wykrywa skanujac PHP.
    safelist: [],
};
