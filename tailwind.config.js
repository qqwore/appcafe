
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./resources/**/*.blade.php",
        "./resources/js/**/*.{vue,js,ts,jsx,tsx}",
        "./Pages/**/*.vue", // Adjust if your Pages directory is elsewhere relative to root
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
