// Loaded as a classic, blocking script in the backend's <head>, before the stylesheet: for the `system` colour scheme
// it sets Tabler's data-bs-theme from the operating system before anything is painted, so a dark system never sees a
// light flash. `light` and `dark` are rendered by the server and need nothing here. No imports, so it is not a module.
(() => {
    const root = document.documentElement;
    if (root.dataset.wkAppearance !== 'system' || typeof window.matchMedia !== 'function') {
        return;
    }
    const dark = window.matchMedia('(prefers-color-scheme: dark)');
    const apply = () => root.setAttribute('data-bs-theme', dark.matches ? 'dark' : 'light');
    apply();
    dark.addEventListener('change', apply);
})();
