// vite.config.js
import { defineConfig } from "file:///C:/Users/Unitey%20Cyber/Desktop/houses/erifax_stylists/mystyle/resources/frontendv3/node_modules/vite/dist/node/index.js";
import vue from "file:///C:/Users/Unitey%20Cyber/Desktop/houses/erifax_stylists/mystyle/resources/frontendv3/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import { fileURLToPath, URL } from "node:url";
var __vite_injected_original_import_meta_url = "file:///C:/Users/Unitey%20Cyber/Desktop/houses/erifax_stylists/mystyle/resources/frontendv3/vite.config.js";
var vite_config_default = defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", __vite_injected_original_import_meta_url))
    }
  },
  server: {
    allowedHosts: [
      ".ngrok-free.app"
    ],
    headers: {
      "Cross-Origin-Opener-Policy": "same-origin-allow-popups"
    },
    port: 5173,
    proxy: {
      "/api": {
        target: "http://localhost:8000",
        changeOrigin: true
      },
      // Fallback for uploaded photos if the backend's APP_URL ever ends up
      // relative — Storage::url() should already return an absolute URL when
      // APP_URL is set correctly, but this proxy means a relative /storage/...
      // path still resolves instead of silently 404ing against the Vite server.
      "/storage": {
        target: "http://localhost:8000",
        changeOrigin: true
      }
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFxVc2Vyc1xcXFxVbml0ZXkgQ3liZXJcXFxcRGVza3RvcFxcXFxob3VzZXNcXFxcZXJpZmF4X3N0eWxpc3RzXFxcXG15c3R5bGVcXFxccmVzb3VyY2VzXFxcXGZyb250ZW5kdjNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkM6XFxcXFVzZXJzXFxcXFVuaXRleSBDeWJlclxcXFxEZXNrdG9wXFxcXGhvdXNlc1xcXFxlcmlmYXhfc3R5bGlzdHNcXFxcbXlzdHlsZVxcXFxyZXNvdXJjZXNcXFxcZnJvbnRlbmR2M1xcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vQzovVXNlcnMvVW5pdGV5JTIwQ3liZXIvRGVza3RvcC9ob3VzZXMvZXJpZmF4X3N0eWxpc3RzL215c3R5bGUvcmVzb3VyY2VzL2Zyb250ZW5kdjMvdml0ZS5jb25maWcuanNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tICd2aXRlJ1xuaW1wb3J0IHZ1ZSBmcm9tICdAdml0ZWpzL3BsdWdpbi12dWUnXG5pbXBvcnQgeyBmaWxlVVJMVG9QYXRoLCBVUkwgfSBmcm9tICdub2RlOnVybCdcblxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcbiAgcGx1Z2luczogW3Z1ZSgpXSxcbiAgcmVzb2x2ZToge1xuICAgIGFsaWFzOiB7XG4gICAgICAnQCc6IGZpbGVVUkxUb1BhdGgobmV3IFVSTCgnLi9zcmMnLCBpbXBvcnQubWV0YS51cmwpKSxcbiAgICB9LFxuICB9LFxuICBzZXJ2ZXI6IHtcbiAgICBhbGxvd2VkSG9zdHM6IFtcbiAgICAgJy5uZ3Jvay1mcmVlLmFwcCcsXG4gICAgICAgICAgICAgICAgXSxcbiAgICBoZWFkZXJzOiB7XG4gICAgICAnQ3Jvc3MtT3JpZ2luLU9wZW5lci1Qb2xpY3knOiAnc2FtZS1vcmlnaW4tYWxsb3ctcG9wdXBzJyxcbiAgICB9LFxuICAgIHBvcnQ6IDUxNzMsXG4gICAgcHJveHk6IHtcbiAgICAgICcvYXBpJzoge1xuICAgICAgICB0YXJnZXQ6ICdodHRwOi8vbG9jYWxob3N0OjgwMDAnLFxuICAgICAgICBjaGFuZ2VPcmlnaW46IHRydWUsXG4gICAgICB9LFxuICAgICAgLy8gRmFsbGJhY2sgZm9yIHVwbG9hZGVkIHBob3RvcyBpZiB0aGUgYmFja2VuZCdzIEFQUF9VUkwgZXZlciBlbmRzIHVwXG4gICAgICAvLyByZWxhdGl2ZSBcdTIwMTQgU3RvcmFnZTo6dXJsKCkgc2hvdWxkIGFscmVhZHkgcmV0dXJuIGFuIGFic29sdXRlIFVSTCB3aGVuXG4gICAgICAvLyBBUFBfVVJMIGlzIHNldCBjb3JyZWN0bHksIGJ1dCB0aGlzIHByb3h5IG1lYW5zIGEgcmVsYXRpdmUgL3N0b3JhZ2UvLi4uXG4gICAgICAvLyBwYXRoIHN0aWxsIHJlc29sdmVzIGluc3RlYWQgb2Ygc2lsZW50bHkgNDA0aW5nIGFnYWluc3QgdGhlIFZpdGUgc2VydmVyLlxuICAgICAgJy9zdG9yYWdlJzoge1xuICAgICAgICB0YXJnZXQ6ICdodHRwOi8vbG9jYWxob3N0OjgwMDAnLFxuICAgICAgICBjaGFuZ2VPcmlnaW46IHRydWUsXG4gICAgICB9LFxuICAgIH0sXG4gIH0sXG59KVxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUF5YixTQUFTLG9CQUFvQjtBQUN0ZCxPQUFPLFNBQVM7QUFDaEIsU0FBUyxlQUFlLFdBQVc7QUFGd1AsSUFBTSwyQ0FBMkM7QUFJNVUsSUFBTyxzQkFBUSxhQUFhO0FBQUEsRUFDMUIsU0FBUyxDQUFDLElBQUksQ0FBQztBQUFBLEVBQ2YsU0FBUztBQUFBLElBQ1AsT0FBTztBQUFBLE1BQ0wsS0FBSyxjQUFjLElBQUksSUFBSSxTQUFTLHdDQUFlLENBQUM7QUFBQSxJQUN0RDtBQUFBLEVBQ0Y7QUFBQSxFQUNBLFFBQVE7QUFBQSxJQUNOLGNBQWM7QUFBQSxNQUNiO0FBQUEsSUFDVztBQUFBLElBQ1osU0FBUztBQUFBLE1BQ1AsOEJBQThCO0FBQUEsSUFDaEM7QUFBQSxJQUNBLE1BQU07QUFBQSxJQUNOLE9BQU87QUFBQSxNQUNMLFFBQVE7QUFBQSxRQUNOLFFBQVE7QUFBQSxRQUNSLGNBQWM7QUFBQSxNQUNoQjtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUEsTUFLQSxZQUFZO0FBQUEsUUFDVixRQUFRO0FBQUEsUUFDUixjQUFjO0FBQUEsTUFDaEI7QUFBQSxJQUNGO0FBQUEsRUFDRjtBQUNGLENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==
