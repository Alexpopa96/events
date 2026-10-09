// The app's one colour palette. Tailwind (tailwind.config.js) builds every colour
// utility from this file, and code that needs a raw value (SVG fills, charts,
// inline styles) imports it from here — never hard-code a hex in a component.
//
//   primary  raspberry — actions, links, active states, focus rings
//   ivt      neutrals  — midnight-indigo ink on soft lavender paper, plus
//                        violet (gradients), amber accent (ratings, highlights)
//                        and teal (secondary series)
//   success / warning / danger — status only (badges, alerts, validation)
//
// The signature look is the `primary → violet` gradient (see .bg-brand /
// .text-gradient in app.css).

export const primary = {
    DEFAULT: '#E11D63',
    bright: '#F0306F',
    deep: '#4C0D2E', // dark end of raspberry gradients
    night: '#14091F', // near-black for dark hero surfaces
};

export const ivt = {
    paper: '#FFFFFF',
    'paper-2': '#F7F5FC',
    'paper-3': '#EEEAF8',
    sand: '#FDE7EF', // blush tint
    ink: '#1A1433',
    'ink-2': '#2A2150',
    'ink-deep': '#0E0A1F',
    'ink-soft': '#585370',
    'ink-faint': '#8F8AA6',
    violet: '#7C3AED',
    'violet-bright': '#A78BFA',
    'violet-soft': '#F1ECFF',
    accent: '#B45309', // amber, dark enough for text on white
    'accent-bright': '#FBBF24', // stars and highlights on dark surfaces
    'accent-light': '#FCD38A',
    'accent-soft': '#FFF4DE', // tinted surface behind accent notices
    'accent-ink': '#2A1600', // text on accent fills
    teal: '#0D9488',
    'teal-light': '#5EEAD4',
    line: 'rgba(26,20,51,0.09)',
    'on-dark': '#F7F4FF',
    'on-dark-dim': '#BDB6DA',
};

export const success = {
    50: '#ECFDF5',
    100: '#D1FAE5',
    200: '#A7F3D0',
    300: '#6EE7B7',
    400: '#34D399',
    500: '#10B981',
    600: '#059669',
    700: '#047857',
    800: '#065F46',
    900: '#064E3B',
};

export const warning = {
    50: '#FFFBEB',
    100: '#FEF3C7',
    200: '#FDE68A',
    300: '#FCD34D',
    400: '#FBBF24',
    500: '#F59E0B',
    600: '#D97706',
    700: '#B45309',
    800: '#92400E',
    900: '#78350F',
};

export const danger = {
    50: '#FFF1F2',
    100: '#FFE4E6',
    200: '#FECDD3',
    300: '#FDA4AF',
    400: '#FB7185',
    500: '#F43F5E',
    600: '#E11D48',
    700: '#BE123C',
    800: '#9F1239',
    900: '#881337',
};

// Categorical series for charts, in the order they should be assigned.
export const chart = [primary.DEFAULT, ivt.violet, ivt.teal, ivt['accent-bright'], ivt['ink-2'], ivt['violet-bright']];

// Fixed meaning for statuses drawn in charts.
export const statusColors = {
    positive: success[600],
    pending: warning[500],
    neutral: ivt['ink-faint'],
    negative: danger[500],
};

export default { primary, ivt, success, warning, danger, chart, statusColors };
