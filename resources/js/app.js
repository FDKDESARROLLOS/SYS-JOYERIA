const themeStorageKey = 'aurea-theme';
const themeToggle = document.getElementById('theme-toggle');

const getPreferredTheme = () => {
    const savedTheme = localStorage.getItem(themeStorageKey);

    if (savedTheme === 'dark' || savedTheme === 'light') {
        return savedTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const applyTheme = (theme) => {
    const isDark = theme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = theme;

    if (themeToggle) {
        themeToggle.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        themeToggle.setAttribute('title', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        themeToggle.setAttribute('aria-pressed', String(isDark));
    }
};

applyTheme(getPreferredTheme());

themeToggle?.addEventListener('click', () => {
    const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';

    localStorage.setItem(themeStorageKey, nextTheme);
    applyTheme(nextTheme);
});
