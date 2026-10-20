/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './partials/**/*.php',
    './assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        ink: '#111111',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        serif: ['"Cormorant Garamond"', 'Georgia', '"Times New Roman"', 'serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
};
