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
                secondary: {
                    DEFAULT: 'var(--color-secondary)',
                    dark: 'var(--color-secondary-dark)',
                },
                accent: 'var(--color-accent)',
                danger: 'var(--color-danger)',
                success: 'var(--color-success)',
                warning: 'var(--color-warning)',
                surface: {
                    DEFAULT: 'var(--color-surface)',
                    2: 'var(--color-surface-2)',
                },
                background: 'var(--color-background)',
                'text-primary': 'var(--color-text-primary)',
                'text-secondary': 'var(--color-text-secondary)',
                border: 'var(--color-border)',
            },
            borderRadius: {
                'btn': '14px',
                'card': '20px',
                'sheet': '28px',
            },
            boxShadow: {
                'elevation-1': '0 1px 3px rgba(0,0,0,0.45)',
                'elevation-2': '0 4px 14px rgba(0,0,0,0.5)',
                'elevation-3': '0 10px 30px rgba(0,0,0,0.55)',
                'elevation-4': '0 20px 50px rgba(0,0,0,0.65)',
                'glow-primary': '0 0 40px rgba(245,158,11,0.30)',
                'glow-secondary': '0 0 40px rgba(139,92,246,0.30)',
                'glow-accent': '0 0 40px rgba(167,139,250,0.30)',
                'glow-success': '0 0 40px rgba(52,211,153,0.28)',
                'glow-danger': '0 0 40px rgba(248,113,113,0.28)',
            },
            transitionTimingFunction: {
                'material': 'cubic-bezier(0.4, 0, 0.2, 1)',
                'spring': 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
            keyframes: {
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(24px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in-down': {
                    '0%': { opacity: '0', transform: 'translateY(-24px)' },
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
                'float-slow': {
                    '0%, 100%': { transform: 'translateY(0px) translateX(0px)' },
                    '50%': { transform: 'translateY(-20px) translateX(8px)' },
                },
                'pulse-soft': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.55' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.92)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'pop-in': {
                    '0%': { opacity: '0', transform: 'scale(0.8)' },
                    '60%': { opacity: '1', transform: 'scale(1.04)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'counter': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'shimmer': {
                    '100%': { transform: 'translateX(100%)' },
                },
                'gradient-shift': {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                },
                'glow-pulse': {
                    '0%, 100%': { boxShadow: '0 0 24px rgba(245,158,11,0.22)' },
                    '50%': { boxShadow: '0 0 52px rgba(245,158,11,0.48)' },
                },
                'spin-slow': {
                    '100%': { transform: 'rotate(360deg)' },
                },
                'blob': {
                    '0%, 100%': { transform: 'translate(0px, 0px) scale(1)' },
                    '33%': { transform: 'translate(30px, -40px) scale(1.12)' },
                    '66%': { transform: 'translate(-24px, 24px) scale(0.94)' },
                },
                'bounce-soft': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                'marquee': {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                'wiggle': {
                    '0%, 100%': { transform: 'rotate(-2deg)' },
                    '50%': { transform: 'rotate(2deg)' },
                },
                'ping-slow': {
                    '75%, 100%': { transform: 'scale(2)', opacity: '0' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                'fade-in-down': 'fade-in-down 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                'fade-in': 'fade-in 0.5s ease-out both',
                'slide-in-right': 'slide-in-right 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                'slide-in-left': 'slide-in-left 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                'float': 'float 4s ease-in-out infinite',
                'float-slow': 'float-slow 7s ease-in-out infinite',
                'pulse-soft': 'pulse-soft 2.4s ease-in-out infinite',
                'scale-in': 'scale-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) both',
                'pop-in': 'pop-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) both',
                'counter': 'counter 0.5s ease-out both',
                'shimmer': 'shimmer 1.6s infinite',
                'gradient-shift': 'gradient-shift 8s ease infinite',
                'glow-pulse': 'glow-pulse 2.6s ease-in-out infinite',
                'spin-slow': 'spin-slow 10s linear infinite',
                'blob': 'blob 12s ease-in-out infinite',
                'bounce-soft': 'bounce-soft 1.6s ease-in-out infinite',
                'marquee': 'marquee 30s linear infinite',
                'wiggle': 'wiggle 0.4s ease-in-out infinite',
                'ping-slow': 'ping-slow 2.2s cubic-bezier(0, 0, 0.2, 1) infinite',
            },
        },
    },
    plugins: [],
};
