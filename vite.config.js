import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
        jsxFactory: 'React.createElement',
        jsxFragment: 'React.Fragment',
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
    },
});
