import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['CenturyGothic', ...defaultTheme.fontFamily.sans],
                outfit: ['Outfit'],
            },
            backgroundImage: {
                'home-hero' : "url('assets/img/simona-toma.jpg')",
            },
            screens: {
                '4xl': '1900px'
            },
            colors: {
                'primary-one': '#EE5D32',
                'primary-two': '#6F1310',
                'secondary-one': '#FFE5D3',
                'secondary-two': '#F2F2F2',
                'secondary-three': '#FFFFFF',
                'secondary-four': '#F9CB0B',
                'secondary-five': '#30D1D1',
                'secondary-six': '#00D666',
                'primary-three': '#F8F7F0'
            },
        },
    },
};
