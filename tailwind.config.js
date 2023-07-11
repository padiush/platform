const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    variants: {
        extend: {
            opacity: ['disabled'],
            backgroundColor: ['checked'],
            borderColor: ['checked'],
            display: ['dark'],
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('daisyui'),
    ],

    daisyui: {
        themes: [
            {
                light: {
                    "primary": "#2b5039",
                    "secondary": "#66ad81",
                    "accent": "#c8e7d5",
                    "neutral": "#FFFFFF",
                    "base-100": "#5d665a",
                    "info": "#3ABFF8",
                    "success": "#36D399",
                    "warning": "#FBBD23",
                    "error": "#F87272",
                },
                dark: {
                    "primary": "#46653C",
                    "secondary": "#66ad81",
                    "accent": "#32444E",
                    "neutral": "#5d665a",
                    "base-100": "#3a4038",
                    "info": "#3ABFF8",
                    "success": "#36D399",
                    "warning": "#FBBD23",
                    "error": "#F87272",
                },
            },
        ],
    }
};
