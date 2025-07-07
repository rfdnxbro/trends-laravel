import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
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
