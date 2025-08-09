import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import preset from "./vendor/filament/support/tailwind.config.preset.js";

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: "#eff6ff",
                    100: "#dbeafe",
                    200: "#bfdbfe",
                    300: "#93c5fd",
                    400: "#60a5fa",
                    500: "#0B2E58",
                    600: "#0B2E58",
                    700: "#0B2E58",
                    800: "#0B2E58",
                    900: "#0B2E58",
                    950: "#0B2E58",
                },
                secondary: {
                    50: "#fff7ed",
                    100: "#ffedd5",
                    200: "#fed7aa",
                    300: "#fdba74",
                    400: "#fb923c",
                    500: "#F76704",
                    600: "#F76704",
                    700: "#F76704",
                    800: "#F76704",
                    900: "#F76704",
                    950: "#F76704",
                },
            },
        },
    },

    plugins: [forms],
};
