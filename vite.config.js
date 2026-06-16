import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
             input: [
                'resources/js/app.js',
                'resources/js/superadmin.js',
                'resources/js/multivendor.js',
                'resources/js/vendoradmin.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
     resolve: {
        alias: {
            '@@': path.resolve(__dirname, 'resources'),
        },
    },
});
