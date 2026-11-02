import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],

  server: {
    port: 5173,

    // Proxy /api/* calls to the XAMPP PHP backend.
    // This avoids CORS issues during development.
    // Example: fetch("/api/locations.php") → http://localhost/traffic_prediction/backend/api/locations.php
    proxy: {
      "/api": {
        target: "http://localhost/traffic_prediction/backend",
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, "/api"),
      },
    },
  },
});
