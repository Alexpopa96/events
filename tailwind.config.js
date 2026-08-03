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
            sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            serif: ['Fraunces', 'Georgia', 'Iowan Old Style', 'Times New Roman', ...defaultTheme.fontFamily.serif],
        },
        extend: {
            colors: {
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
                'paper': '#FAF8FB',
                'ink': '#211C27',
                'ink-soft': '#6B6373',
                'line': '#E6DFE7',
                'brand': {
                    50: '#EDF5F2',
                    100: '#D2E7DF',
                    400: '#2E7D6B',
                    500: '#1F5C4E',
                    600: '#16453A',
                    700: '#0F332B',
                },
                'gold': {
                    300: '#DDBA7A',
                    400: '#C79A4B',
                    500: '#A9782E',
                },
            },
            boxShadow: {
                'glow-brand': '0 8px 30px -8px rgba(31,92,78,0.35)',
                'glow-gold': '0 8px 30px -8px rgba(169,120,46,0.35)',
            },
        }
    },
    darkMode: 'false',
    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
