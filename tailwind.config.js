import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                bia: {
                    primary: '#C77F2C',
                    'primary-dk': '#8B5618',
                    'primary-lt': '#E5A965',
                    cream: '#F5EDDC',
                    'cream-dk': '#E8DDC5',
                    accent: '#B23A48',
                    ink: '#1A1410',
                    'ink-soft': '#4A3F35',
                    'ink-mute': '#8B7E72',
                },
            },
            fontFamily: {
                serif: ['Lora', 'Recoleta', 'Georgia', 'serif'],
                sans: ['"Inter Tight"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                hero: ['2.5rem', { lineHeight: '1.15', letterSpacing: '-0.02em' }],
                h1: ['2rem', { lineHeight: '1.2' }],
                h2: ['1.5rem', { lineHeight: '1.3' }],
                h3: ['1.25rem', { lineHeight: '1.4' }],
                body: ['1rem', { lineHeight: '1.7' }],
                caption: ['0.875rem', { lineHeight: '1.5' }],
            },
            spacing: {
                editorial: '4rem',
                reading: '1.5rem',
            },
            maxWidth: {
                reading: '65ch',
            },
            borderRadius: {
                card: '0.75rem',
                pill: '9999px',
            },
            boxShadow: {
                editorial: '0 1px 2px rgba(26, 20, 16, 0.04), 0 4px 16px rgba(26, 20, 16, 0.06)',
            },
        },
    },
    plugins: [forms, typography],
};
