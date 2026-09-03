/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#fef6f0',
          500: '#e07a3f',
          600: '#c8632c',
          700: '#a34f24',
        },
      },
    },
  },
  plugins: [],
}
