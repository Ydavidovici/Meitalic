// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import analyzer from 'rollup-plugin-analyzer';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // 1. foundation
                'resources/css/globals.css',

                // 2. layout‑level
                //'resources/css/layouts/app.css',

                // 3. per‑page styles
                'resources/css/pages/admin/dashboard.css',
                'resources/css/pages/cart/index.css',
                'resources/css/pages/checkout/index.css',
                'resources/css/pages/checkout/success.css',
                'resources/css/pages/dashboard/index.css',
                'resources/css/pages/home.css',
                //'resources/css/pages/orders/show.css',
                'resources/css/pages/product.css',
                'resources/css/pages/products.css',

                // 4. shared partials
                'resources/css/partials/product-grid.css',
                'resources/css/partials/admin_product-grid.css',

                // 5. your JS
                'resources/js/globals.js',
                'resources/js/user-dashboard.js',
                'resources/js/admin-dashboard.js'
            ],


            refresh: true,
        }),

        analyzer({
            summaryOnly: true,  // single total + top N files
            limit: 5,           // show your 5 biggest assets
            hideDeps: false,    // set to true if you only care about your own code
        }),
    ],
});
