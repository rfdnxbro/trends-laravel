/// <reference types="vitest" />
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./resources/js/test/setup.ts'],
    silent: false,
    reporter: ['verbose'],
    testTimeout: 30000,
    exclude: [
      '**/node_modules/**',
      '**/dist/**',
      '**/cypress/**',
      '**/.{idea,git,cache,output,temp}/**',
      '**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*',
      '**/tests/e2e/**',
      '**/playwright-report/**',
      '**/test-results/**',
    ],
  },
})