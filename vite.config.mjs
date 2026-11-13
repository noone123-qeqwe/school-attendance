import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { readFileSync, writeFileSync } from 'node:fs';

function versionedServiceWorker() {
    const release = JSON.parse(readFileSync(new URL('./version.json', import.meta.url), 'utf8'));
    const appVersion = String(release.version || '');
    const buildDigits = String(release.build || '').replace(/\D/g, '');
    if (!/^\d+\.\d+\.\d+$/.test(appVersion) || !buildDigits) {
        throw new Error('version.json must provide a semantic version and numeric build before building the service worker.');
    }

    return {
        name: 'versioned-service-worker',
        apply: 'build',
        enforce: 'post',
        closeBundle() {
            const swPath = new URL('./public/sw.js', import.meta.url);
            let worker = readFileSync(swPath, 'utf8');
            if (!/const\s+APP_VERSION\s*=\s*['"][^'"]+['"];/.test(worker)
                || !/const\s+CACHE_VERSION\s*=\s*['"]v?\d+['"];/.test(worker)) {
                throw new Error('public/sw.js is missing APP_VERSION or CACHE_VERSION markers.');
            }
            worker = worker.replace(/const\s+APP_VERSION\s*=\s*['"][^'"]+['"];/, `const APP_VERSION = '${appVersion}';`);
            worker = worker.replace(/const\s+CACHE_VERSION\s*=\s*['"][^'"]+['"];/, `const CACHE_VERSION = 'v${buildDigits}';`);
            worker = worker.replace(/\/\*\s*BUMP_TIMESTAMP:[\s\S]*?\*\/\s*/g, '');
            writeFileSync(swPath, worker);
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/attendance-face-guidance.js'],
            refresh: true,
        }),
        tailwindcss(),
        versionedServiceWorker(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        // The QR view imports the camera entry on demand and uses its exports.
        rollupOptions: { preserveEntrySignatures: 'exports-only' },
    },
});
