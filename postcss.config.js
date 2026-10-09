import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

// Multiplies every absolute font-size (and px/rem line-height) by --fs-scale, so
// app.css can shrink all text on phones in one place — Tailwind's scale, arbitrary
// text-[13px] values and component styles alike. em/% sizes are relative already
// and are left alone, as is the iOS no-zoom rule for inputs (max(16px, 1em)).
const scaleText = () => ({
    postcssPlugin: 'scale-text',
    Declaration(decl) {
        if (decl.prop !== 'font-size' && decl.prop !== 'line-height') return;
        if (!/^-?\d*\.?\d+(px|rem)$/.test(decl.value.trim())) return;
        decl.value = `calc(${decl.value.trim()} * var(--fs-scale, 1))`;
    },
});
scaleText.postcss = true;

export default {
    plugins: [
        tailwindcss,
        scaleText,
        autoprefixer,
    ],
};
