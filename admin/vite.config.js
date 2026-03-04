import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd())
    const port = Number(env.VITE_APP_PORT ?? 5174)
    const target = env.VITE_API_TARGET ?? 'http://localhost:8000'
    const allowedHosts = env.VITE_APP_ALLOWED_HOSTS
        ? env.VITE_APP_ALLOWED_HOSTS.split(',').map(h => h.trim())
        : ['localhost']

    return {
        plugins: [vue()],
        resolve: {
            alias: {
                "@": fileURLToPath(new URL("./src", import.meta.url)),
            },
        },
        build: {
            rollupOptions: {
                output: {
                    manualChunks: {
                        "vendor-vue": ["vue", "vue-router", "pinia"],
                        "vendor-charts": ["vue3-apexcharts", "apexcharts"],
                        "vendor-calendar": ["v-calendar"],
                        "vendor-qr": ["html5-qrcode"],
                    },
                },
            },
        },
        server: {
            port,
            proxy: {
                "/api": {
                    target,
                    changeOrigin: true,
                },
            },
            watch: {
                usePolling: true,
            },
            allowedHosts,
        },
    };
});
