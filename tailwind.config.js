import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                surface: {
                    50: '#f8fafc',  // Slate-50
                    100: '#f1f5f9', // Slate-100
                    200: '#e2e8f0', // Slate-200
                    300: '#cbd5e1', // Slate-300
                    400: '#94a3b8', // Slate-400
                    500: '#64748b', // Slate-500
                    600: '#475569', // Slate-600
                    700: '#334155', // Slate-700
                    800: '#1e293b', // Slate-800
                    900: '#0f172a', // Slate-900
                    950: '#020617', // Slate-950
                },
                primary: {
                    50: '#f5f3ff',  // Violet-50
                    100: '#ede9fe', // Violet-100
                    200: '#ddd6fe', // Violet-200
                    300: '#c4b5fd', // Violet-300
                    400: '#a78bfa', // Violet-400
                    500: '#8b5cf6', // Violet-500
                    600: '#7c3aed', // Violet-600
                    700: '#6d28d9', // Violet-700
                    800: '#5b21b6', // Violet-800
                    900: '#4c1d95', // Violet-900
                    950: '#2e1065', // Violet-950
                }
            },
        },
    },

    plugins: [forms],
};
