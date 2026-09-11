import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    navy: '#0A3860',
                    blue: '#0B538C',
                    sky: '#008DD2',
                    cyan: '#29ABE2',
                    orange: '#F37023',
                    amber: '#FF8A00',
                    gold: '#FAA61A',
                },
            },
        },
    },

    plugins: [forms],
};
