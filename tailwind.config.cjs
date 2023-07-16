/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/client/**/*.{html,js}"],
  theme: {
    extend: {},
  },
  plugins: [
    '@tailwindcss/forms',
    require("daisyui")
  ],
  daisyui: {
    themes: [],
  },
}