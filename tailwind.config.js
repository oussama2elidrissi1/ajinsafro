/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["Poppins", "ui-sans-serif", "system-ui", "sans-serif"],
      },
      colors: {
        brand: {
          dark: "#0e3a5a",
          blue: "#0083c4",
          light: "#e6f3fa",
          orange: "#f37a1f",
          yellow: "#ffb300",
          gray: "#f7f9fc",
        },
      },
      boxShadow: {
        custom: "0 4px 20px rgba(0,0,0,0.08)",
        search: "0 10px 40px -10px rgba(0,0,0,0.10)",
      },
      borderRadius: {
        filter: "15px",
      },
    },
  },
  plugins: [],
};

