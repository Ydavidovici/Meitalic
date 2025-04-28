// tailwind.config.js
module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
    ],
    theme: {
        extend: {
            colors: {
                primary:   'var(--color-primary)',   // light rose
                secondary: 'var(--color-secondary)', // cream
                accent:    'var(--color-accent)',    // soft pink
                text:      'var(--color-text)',      // dark gray
            },
        },
    },
    plugins: [],
}
