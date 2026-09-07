import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    allowedHosts: [
        '.ngrok-free.app',
    ],
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      // Fallback for uploaded photos if the backend's APP_URL ever ends up
      // relative — Storage::url() should already return an absolute URL when
      // APP_URL is set correctly, but this proxy means a relative /storage/...
      // path still resolves instead of silently 404ing against the Vite server.
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
