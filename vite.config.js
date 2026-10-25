import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { cpSync } from 'node:fs';
import { resolve } from 'node:path';

let pdfOutputDirectory;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/sli.css', 'resources/js/sli.js'],
            refresh: true,
        }),
        tailwindcss(),
        {
            name: 'sli-pdf-reader-assets',
            apply: 'build',
            configResolved(config) { pdfOutputDirectory = resolve(config.root, config.build.outDir); },
            closeBundle() {
                for (const folder of ['cmaps', 'standard_fonts', 'wasm']) {
                    cpSync(resolve('node_modules/pdfjs-dist', folder), resolve(pdfOutputDirectory, 'pdfjs', folder), { recursive: true });
                }
            },
        },
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
