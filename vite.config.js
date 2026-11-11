import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

// Tabler is MIT: its notice must travel with the copy compiled into admin.css, and the CSS minifier drops every
// comment, /*! included. This puts the notice back on the built file only, so the themes' output is unchanged.
const TABLER_NOTICE = `/*!
 * Includes Tabler v1.7.1 (https://tabler.io)
 * Copyright 2018-2026 The Tabler Authors
 * Copyright 2018-2026 codecalm.net Paweł Kuna
 * Licensed under MIT (https://github.com/tabler/tabler/blob/master/LICENSE)
 */
`;

const tablerNotice = () => ({
    name: 'laranail-waitlist-kit:tabler-notice',
    generateBundle(_options, bundle) {
        for (const file of Object.values(bundle)) {
            if (file.type === 'asset' && /^assets\/css\/admin-.*\.css$/.test(file.fileName)) {
                // @charset must stay the file's first bytes, so the notice follows it.
                const css = String(file.source);
                const charset = '@charset "UTF-8";';
                file.source = css.startsWith(charset) ? charset + '\n' + TABLER_NOTICE + css.slice(charset.length) : TABLER_NOTICE + css;
            }
        }
    },
});

// Builds into resources/dist (published to public/vendor/waitlist-kit), every file under assets/<kind>/ with a content
// hash, plus manifest.json, which Laravel's Vite helper reads to write the tags.
export default defineConfig({
    plugins: [tailwindcss(), tablerNotice()],
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
                'resources/assets/js/admin-theme.js',
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
