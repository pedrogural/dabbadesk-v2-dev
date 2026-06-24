import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/View/Components/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                dabba: {
                    bg: '#f8fafc',
                    surface: '#ffffff',
                    border: '#e2e8f0',
                    text: '#334155',
                    muted: '#64748b',
                    primary: '#4f46e5',
                    purple: '#a855f7',
                },
            },
            boxShadow: {
                soft: '0 1px 3px rgba(15, 23, 42, 0.06)',
            },
        },
    },
    plugins: [forms],
}
