import {defineConfig} from 'vite';
import nette from '@nette/vite-plugin';

export default defineConfig({
    plugins: [
        nette({
            entry: 'main.js',
        }),
    ],

    build: {
        // Frontend tooling (this config, package.json, node_modules, assets/) lives in the
        // repo root – outside web/, the only directory uploaded to hosting. The build output
        // must still land in web/www/assets. Vite resolves outDir relative to root (./assets),
        // so ../web/www/assets points at web/www/assets. emptyOutDir is required because the
        // output dir is outside the Vite root.
        outDir: '../web/www/assets',
        emptyOutDir: true,
    },

    css: {
        devSourcemap: true,
    },
});
