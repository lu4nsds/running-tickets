/** @type {import('tailwindcss').Config} */
export default {
    content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                primary: "#00E676",
                "background-dark": "#0F1114",
                "card-bg": "#1A1D23",
                "card-dark": "#1A1D23",
                "border-dark": "#2d333b",
                surface: "#252930",
                "surface-elevated": "#2D3139",
                "input-bg": "#1E2128",
                "input-border": "#373C43",
                "text-primary": "#E1E3E6",
                "text-secondary": "#9CA3AF",
                "text-muted": "#6B7280",
            },
            fontFamily: {
                display: ["Inter", "sans-serif"],
            },
            boxShadow: {
                primary: "0 0 25px rgba(0, 230, 118, 0.2)",
                "primary-strong": "0 4px 14px rgba(0, 230, 118, 0.25)",
            },
        },
    },
    plugins: [require("@tailwindcss/forms")],
};
