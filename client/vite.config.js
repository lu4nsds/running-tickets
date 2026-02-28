import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());
    const port = Number(env.VITE_APP_PORT ?? 5173);
    const target = env.VITE_API_TARGET ?? "http://localhost:8000";
    const allowedHosts = env.VITE_APP_ALLOWED_HOSTS
        ? env.VITE_APP_ALLOWED_HOSTS.split(",").map((h) => h.trim())
        : ["localhost"];

    return {
        plugins: [vue()],
        resolve: {
            alias: {
                "@": path.resolve(__dirname, "./src"),
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
