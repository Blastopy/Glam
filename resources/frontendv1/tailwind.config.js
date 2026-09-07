/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        // Warm, market-inspired palette — grooming tools & East African
        // marketplace color, deliberately not the cream+terracotta default.
        ink: {
          DEFAULT: '#16241D', // deep bottle-green near-black — primary text
          soft: '#4B5A50',    // secondary text on paper
        },
        paper: '#FBF6EC', // warm ivory background
        clay: {
          50: '#FBF1EC',
          400: '#C97246',
          500: '#B54B2C', // primary CTA color — enough contrast for white text
          600: '#9C3F24',
          700: '#7C331E',
        },
        amber: {
          50: '#FDF5E6',
          300: '#F0C878',
          400: '#E8B75C',
          500: '#E8A33D', // decorative accent — badges, highlights
          600: '#CC8A2A',
        },
        sage: {
          50: '#F1F4EF',
          300: '#B7C6B0',
          400: '#9DB295',
          500: '#7C9473', // secondary accent — dividers, muted states
          600: '#5F7358',
        },
      },
      fontFamily: {
        heading: ['"Bricolage Grotesque"', 'sans-serif'],
        sans: ['"Work Sans"', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
