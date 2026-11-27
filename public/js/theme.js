(() => {
    const root = document.documentElement;
    const meta = document.querySelector('meta[name="color-scheme"]');
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    const key = 'color-scheme';
    const valid = value => value === 'light' || value === 'dark';
    let preference;
    try {
        preference = localStorage.getItem(key);
    } catch {}

    function apply() {
        const theme = valid(preference) ? preference : system.matches ? 'dark' : 'light';
        root.dataset.bsTheme = theme;
        meta.content = valid(preference) ? preference : 'light dark';

        const button = document.getElementById('theme-toggle');
        if (button) {
            button.hidden = false;
            button.setAttribute('aria-pressed', String(theme === 'dark'));
            const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
            button.setAttribute('aria-label', label);
            button.title = label;
            button.querySelector('i').className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    apply();
    system.addEventListener('change', apply);
    window.addEventListener('storage', event => {
        if (event.key === key || event.key === null) {
            preference = event.newValue;
            apply();
        }
    });
    document.addEventListener('DOMContentLoaded', () => {
        apply();
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            const target = root.dataset.bsTheme === 'dark' ? 'light' : 'dark';
            preference = target === (system.matches ? 'dark' : 'light') ? null : target;
            apply();
            try {
                if (preference) localStorage.setItem(key, preference);
                else localStorage.removeItem(key);
            } catch {}
        });
    });
})();
