import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

// Builds into resources/dist (published to public/vendor/waitlist-kit), every file under assets/<kind>/ with a content
// hash, plus manifest.json, which Laravel's Vite helper reads to write the tags.
export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: [
                'resources/assets/css/themes/default.css',
                'resources/assets/css/themes/plain.css',
                'resources/assets/css/admin.css',
                'resources/assets/js/kit.js',
            ],
            output: {
                entryFileNames: 'assets/js/[name]-[hash].js',
                chunkFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: (asset) => (asset.names?.[0] ?? '').endsWith('.css')
                    ? 'assets/css/[name]-[hash][extname]'
                    : 'assets/media/[name]-[hash][extname]',
            },
        },
    },
});
