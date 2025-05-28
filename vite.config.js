import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import analyzer from 'rollup-plugin-analyzer';

export default defineConfig(({ command }) => {
    const isProd = command === 'build';

    return {
        build: isProd
            ? {
                outDir: '/home/u161705717/domains/meitalic.com/public_html/build',
                emptyOutDir: true,
            }
            : {},

        plugins: [
            laravel({
                input: [
                    // 1. foundation
                    'resources/css/globals.css',

                    // 3. per‑page styles
                    'resources/css/pages/admin/dashboard.css',
                    'resources/css/pages/cart/index.css',
                    'resources/css/pages/checkout/index.css',
                    'resources/css/pages/checkout/success.css',
                    'resources/css/pages/dashboard/index.css',
                    'resources/css/pages/home.css',
                    'resources/css/pages/product.css',
                    'resources/css/pages/products.css',
                    'resources/css/auth/auth.css',

                    // 4. shared partials
                    'resources/css/partials/product-grid.css',
                    'resources/css/partials/admin_product-grid.css',

                    // 5. your JS
                    'resources/js/globals.js',
                    'resources/js/user-dashboard.js',
                    'resources/js/admin-dashboard.js',
                ],
                refresh: true,
            }),

            isProd &&
            analyzer({
                summaryOnly: true,
                limit: 5,
                hideDeps: false,
            }),
        ].filter(Boolean),
    };
});
