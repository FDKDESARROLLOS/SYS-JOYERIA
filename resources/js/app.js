const themeStorageKey = 'aurea-theme';

const initializeTheme = () => {
    if (window.aureaThemeInitialized) {
        return;
    }

    window.aureaThemeInitialized = true;

    const themeToggle = document.getElementById('theme-toggle');
    const savedTheme = localStorage.getItem(themeStorageKey);
    const theme = savedTheme === 'dark' || savedTheme === 'light'
        ? savedTheme
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    const applyTheme = (nextTheme) => {
        const isDark = nextTheme === 'dark';

        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.style.colorScheme = nextTheme;

        if (themeToggle) {
            themeToggle.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            themeToggle.setAttribute('title', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            themeToggle.setAttribute('aria-pressed', String(isDark));
        }
    };

    applyTheme(theme);

    themeToggle?.addEventListener('click', () => {
        const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';

        localStorage.setItem(themeStorageKey, nextTheme);
        applyTheme(nextTheme);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTheme, { once: true });
} else {
    initializeTheme();
}
