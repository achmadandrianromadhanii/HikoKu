import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
        "./resources/js/**/*.ts",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },

            colors: {
                primary: {
                    50: "#eef8fb",
                    100: "#d7eef5",
                    200: "#b7e0ec",
                    300: "#85c9dc",
                    400: "#49a9c5",
                    500: "#178ca8",
                    600: "#0f6f9d",
                    700: "#0d5f88",
                    800: "#104a67",
                    900: "#0c3653",
                    950: "#071f36",
                },

                brand: {
                    50: "#eef8fb",
                    100: "#d7eef5",
                    200: "#b7e0ec",
                    300: "#85c9dc",
                    400: "#49a9c5",
                    500: "#178ca8",
                    600: "#0f6f9d",
                    700: "#0d5f88",
                    800: "#104a67",
                    900: "#0c3653",
                    950: "#071f36",
                },

                surface: {
                    50: "#f8fbfc",
                    100: "#eef4f7",
                    200: "#dce7ee",
                    300: "#c4d4de",
                    400: "#97aebb",
                    500: "#6b8797",
                    600: "#536d7a",
                    700: "#445864",
                    800: "#394954",
                    900: "#24323d",
                    950: "#121c24",
                },
            },

            boxShadow: {
                soft: "0 10px 30px rgba(8, 27, 57, 0.08)",
                card: "0 14px 36px rgba(8, 27, 57, 0.10)",
                nav: "0 8px 30px rgba(8, 27, 57, 0.10)",
            },

            borderRadius: {
                "2.5xl": "1.35rem",
                "3xl": "1.6rem",
            },
        },
    },

    plugins: [forms],
};