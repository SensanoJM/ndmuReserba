/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
     // Include your Livewire component path here
    './app/Http/Livewire/**/*.php',    
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

