const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
    ],
    theme: {
        container: {
            center: true,
            padding: {
                DEFAULT: '1rem',
                sm: '1.5rem',
                lg: '2rem',
                xl: '4rem',
            },
        },
        extend: {
            colors: {
                primary: '#f5e4e1',    // light rose
                secondary: '#fffaf7',  // cream
                accent: '#e3bfc3',     // soft pink
                text: '#333333',       // dark gray
                neutral: {
                    100: '#FFFAF7',
                    200: '#F9F6F4',
                    600: '#666666',
                    800: '#333333',
                },
                dark: '#1F1F1F',
            },
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
