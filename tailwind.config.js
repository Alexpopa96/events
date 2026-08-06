const defaultTheme = require('tailwindcss/defaultTheme')

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
            sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            serif: ['Fraunces', 'Georgia', 'Iowan Old Style', 'Times New Roman', ...defaultTheme.fontFamily.serif],
            invita: ['Inter', ...defaultTheme.fontFamily.sans],
        },
        extend: {
            colors: {
                // "Invita" mockup palette — scoped to the public homepage
                // (SiteHeader/SiteFooter/Home) via the `ivt-*` namespace so it
                // never touches the existing brand/ink/paper tokens elsewhere.
                ivt: {
                    paper: '#FFFFFF',
                    'paper-2': '#F6F3EB',
                    'paper-3': '#EFEADB',
                    ink: '#16281F',
                    'ink-2': '#1F3A2C',
                    'ink-soft': '#4B5C4F',
                    'ink-faint': '#8A9186',
                    gold: '#A87F2E',
                    'gold-bright': '#C9A24F',
                    wine: '#7C2E3B',
                    'wine-bright': '#96323F',
                    sage: '#6F8465',
                    line: 'rgba(22,40,31,0.1)',
                    'on-dark': '#F3EEDD',
                    'on-dark-dim': '#C7CDBE',
                },
                'primaryColor': '#54ACE4',
                'hoverColor': '#349de0', //blue-50
                'layoutDark' : '#1F263C',
                'slotDark' : '#15192A',
                'inputDark' : '#1F263C',
                'tableDark' : '#1F263C',
                'textTableDark' : '#f3f4f6',
                'textInputDark' : '#f3f4f6',
                'borderInputDark' : '#4b5563',
                // Marketplace brand palette (public-facing pages: auth, listings, provider profiles)
                'paper': '#FFFFFF',
                'ink': '#211C27',
                'ink-soft': '#6B6373',
                'line': '#E6DFE7',
                'brand': {
                    50: '#ECFDF5',
                    100: '#D1FAE5',
                    400: '#10B981',
                    500: '#059669',
                    600: '#047857',
                    700: '#065F46',
                },
                'gold': {
                    300: '#FCD34D',
                    400: '#FBBF24',
                    500: '#F59E0B',
                },
            },
            boxShadow: {
                'glow-brand': '0 8px 30px -8px rgba(16,185,129,0.35)',
                'glow-gold': '0 8px 30px -8px rgba(245,158,11,0.35)',
                'ivt-soft': '0 24px 48px -30px rgba(22,40,31,0.28)',
                'ivt-deep': '0 30px 60px -25px rgba(22,40,31,0.35)',
            },
        }
    },
    darkMode: 'false',
    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
