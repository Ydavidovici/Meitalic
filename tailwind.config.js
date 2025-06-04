// tailwind.config.js
const defaultTheme = require('tailwindcss/defaultTheme')

module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
    ],
    theme: {
        container: { /* your container settings… */ },
        extend: {
            colors: {
                primary:    'var(--color-primary)',
                secondary:  'var(--color-secondary)',
                accent:     'var(--color-accent)',
                text:       'var(--color-text)',
                neutral: {
                    100: 'var(--color-neutral-100)',
                    200: 'var(--color-neutral-200)',
                    600: 'var(--color-neutral-600)',
                    800: 'var(--color-neutral-800)',
                },
                dark:       'var(--color-dark)',
                aqua: {
                    100: '#E0FFFF', // Light Aqua
                    200: '#B2FFFF', // Soft Aqua
                    500: '#00FFFF', // True Aqua
                    600: '#00E5EE', // Deep Aqua
                    800: '#008B8B', // Dark Aqua
                },
            },
            fontFamily: {
                sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),],
}
