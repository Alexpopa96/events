const defaultTheme = require('tailwindcss/defaultTheme')

import { danger, ivt, primary, success, warning } from './resources/js/palette.js';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],
    theme: {
        fontFamily: {
            sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            // Headings, prices, big numbers — an expressive grotesque.
            display: ['"Bricolage Grotesque"', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            invita: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
        },
        extend: {
            // Every colour utility comes from resources/js/palette.js — the app's only palette.
            // Tailwind's own hues (gray, blue, red, emerald…) are deliberately not used in components.
            colors: {
                primary,
                ivt,
                success,
                warning,
                danger,
            },
            boxShadow: {
                'glow-primary': '0 10px 30px -10px rgba(225,29,99,0.55)',
                'glow-violet': '0 10px 30px -10px rgba(124,58,237,0.55)',
                'glow-success': '0 8px 30px -8px rgba(16,185,129,0.35)',
                'glow-accent': '0 10px 30px -10px rgba(251,191,36,0.55)',
                'ivt-soft': '0 24px 48px -30px rgba(26,20,51,0.30)',
                'ivt-deep': '0 30px 60px -25px rgba(26,20,51,0.40)',
            },
            backgroundImage: {
                brand: `linear-gradient(120deg, ${primary.DEFAULT} 0%, ${ivt.violet} 100%)`,
                'brand-soft': `linear-gradient(120deg, ${ivt.sand} 0%, ${ivt['violet-soft']} 100%)`,
            },
            keyframes: {
                marquee: {
                    from: { transform: 'translateX(0)' },
                    to: { transform: 'translateX(-50%)' },
                },
            },
            animation: {
                marquee: 'marquee 40s linear infinite',
            },
        }
    },
    darkMode: 'false',
    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
