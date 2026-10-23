import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// API requests are proxied to the PHP backend during development.
const apiTarget = process.env.VITE_API_PROXY_TARGET ?? 'http://localhost:8080'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  css: {
    preprocessorOptions: {
      // Bootstrap 5.3 still relies on @import and global Sass functions.
      scss: { silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'] },
    },
  },
  server: {
    host: true,
    port: 5173,
    // Bind mounts on Windows/macOS do not propagate file events into containers.
    watch: { usePolling: process.env.VITE_USE_POLLING === 'true', interval: 300 },
    proxy: {
      '/api': { target: apiTarget, changeOrigin: true },
    },
  },
  test: {
    environment: 'jsdom',
    include: ['tests/unit/**/*.spec.js'],
  },
})
