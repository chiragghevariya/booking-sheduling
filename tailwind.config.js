import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    // Class-based dark mode — a `dark` class on <html> flips the CSS variables.
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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Brand stays constant across themes (indigo works on light + dark).
                brand: {
                    50: '#EEF2FF',
                    100: '#E0E7FF',
                    200: '#C7D2FE',
                    300: '#A5B4FC',
                    400: '#818CF8',
                    500: '#6366F1',
                    600: '#4F46E5',
                    700: '#4338CA',
                    DEFAULT: '#6366F1',
                },
                // Neutral tokens read CSS variables (light + dark defined in app.css),
                // so every bg-surface / text-ink / border-line auto-adapts to the theme.
                ink: {
                    DEFAULT: 'rgb(var(--color-ink) / <alpha-value>)',
                    muted: 'rgb(var(--color-ink-muted) / <alpha-value>)',
                    soft: 'rgb(var(--color-ink-soft) / <alpha-value>)',
                },
                surface: {
                    DEFAULT: 'rgb(var(--color-surface) / <alpha-value>)',
                    raised: 'rgb(var(--color-surface-raised) / <alpha-value>)',
                    sunken: 'rgb(var(--color-surface-sunken) / <alpha-value>)',
                },
                line: 'rgb(var(--color-line) / <alpha-value>)',
            },
            borderColor: {
                DEFAULT: 'rgb(var(--color-line) / <alpha-value>)',
            },
            boxShadow: {
                soft: '0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px rgba(15, 23, 42, 0.06)',
                glow: '0 0 0 4px rgba(99, 102, 241, 0.18)',
            },
            borderRadius: {
                xl: '0.875rem',
                '2xl': '1.125rem',
            },
            spacing: {
                // 8px base scale already covered by Tailwind defaults; alias for headers
                'topbar': '64px',
                'sidebar': '256px',
                'sidebar-collapsed': '72px',
            },
        },
    },
    plugins: [],
};
