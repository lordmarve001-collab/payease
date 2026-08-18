/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans', 'system-ui', 'sans-serif'],
                display: ['DM Sans', 'system-ui', 'sans-serif'],
            },
            colors: {
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    dark: 'var(--color-primary-dark)',
                    light: 'var(--color-primary-light)',
                },
                secondary: 'var(--color-secondary)',
                accent: 'var(--color-accent)',
                danger: 'var(--color-danger)',
                surface: 'var(--color-surface)',
                background: 'var(--color-background)',
                'text-primary': 'var(--color-text-primary)',
                'text-secondary': 'var(--color-text-secondary)',
                border: 'var(--color-border)',
            },
            borderRadius: {
                'btn': '12px',
                'card': '16px',
                'sheet': '24px',
            },
            boxShadow: {
                'elevation-1': '0 1px 3px rgba(0,0,0,0.06)',
                'elevation-2': '0 4px 12px rgba(0,0,0,0.08)',
                'elevation-3': '0 8px 24px rgba(0,0,0,0.12)',
                'elevation-4': '0 16px 40px rgba(0,0,0,0.16)',
                'glow-primary': '0 0 40px rgba(217,119,6,0.3)',
                'glow-secondary': '0 0 40px rgba(124,58,237,0.3)',
            },
            transitionTimingFunction: {
                'material': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
            keyframes: {
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(30px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'slide-in-right': {
                    '0%': { opacity: '0', transform: 'translateX(40px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                'slide-in-left': {
                    '0%': { opacity: '0', transform: 'translateX(-40px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                'float': {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-12px)' },
                },
                'pulse-soft': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.9)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'counter': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up 0.6s ease-out forwards',
                'fade-in': 'fade-in 0.5s ease-out forwards',
                'slide-in-right': 'slide-in-right 0.7s ease-out forwards',
                'slide-in-left': 'slide-in-left 0.7s ease-out forwards',
                'float': 'float 3s ease-in-out infinite',
                'pulse-soft': 'pulse-soft 2s ease-in-out infinite',
                'scale-in': 'scale-in 0.5s ease-out forwards',
                'counter': 'counter 0.5s ease-out forwards',
            },
        },
    },
    plugins: [],
};
