/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/*.blade.php',
        './resources/js/**/*.vue',
        './resources/css/**/*.css',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                display: ['Space Grotesk', 'system-ui', 'sans-serif'],
                body: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                tech: {
                    cyan: '#06b6d4',
                    purple: '#a855f7',
                    green: '#22c55e',
                    orange: '#f97316',
                    dark: '#0f172a',
                    surface: '#1e293b',
                }
            },
            boxShadow: {
                'soft': '0 4px 20px rgba(0, 0, 0, 0.06)',
                'card': '0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04)',
                'hover': '0 10px 25px rgba(0, 0, 0, 0.1)',
                'glow-cyan': '0 4px 14px rgba(6, 182, 212, 0.35)',
                'glow-orange': '0 2px 8px rgba(249, 115, 22, 0.35)',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
        },
    },
    plugins: [],
};
