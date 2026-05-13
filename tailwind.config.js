/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ['class', '.theme-dark'],
    content: [
        './src/Templates/**/*.php',
        './src/Plugins/**/templates/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                'titillium': ['"Titillium Web"', 'sans-serif'],
                'open': ['"Open Sans"', 'sans-serif'],
            },
            colors: {
                mate: {
                    bg: '#ffffff',
                    card: '#f5f5f5',
                    text: '#1a1a1a',
                    muted: '#888888',
                    'muted-light': '#cccccc',
                    border: '#e0e0e0',
                    accent: '#2d2d2d',
                },
                'mate-dark': {
                    bg: '#1a1a1a',
                    card: '#222222',
                    text: '#e0e0e0',
                    muted: '#777777',
                    'muted-light': '#333333',
                    border: '#333333',
                    accent: '#999999',
                },
            },
            maxWidth: {
                'content': '940px',
            },
        },
    },
    plugins: [],
}
