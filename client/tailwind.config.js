/** @type {import('tailwindcss').Config} */
export default {
    content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                primary: "#00e677",
                "primary-dark": "#00cc6a",
                "background-light": "#f5f8f7",
                "background-dark": "#0F1114",
                "surface-dark": "#1E212B",
                "surface-darker": "#15171e",
                "border-dark": "#252B3A",
            },
            fontFamily: {
                display: ["Inter", "sans-serif"],
                body: ["Inter", "sans-serif"],
            },
            boxShadow: {
                neon: "0 0 10px rgba(0, 230, 119, 0.5), 0 0 20px rgba(0, 230, 119, 0.3)",
            },
        },
    },
    plugins: [],
};
