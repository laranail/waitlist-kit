// The kit's Alpine.js components, on Alpine's CSP build: no eval, so a strict Content-Security-Policy needs no
// 'unsafe-eval'. Everything works without this script; it only makes the forms answer in place and the countdown tick.
import Alpine from '@alpinejs/csp';

// Posts a sign-up or contact form as JSON and shows the answer in the form's own status and error elements.
Alpine.data('wkForm', () => ({
    busy: false,

    async submit(event) {
        event.preventDefault();
        if (this.busy) {
            return;
        }
        this.busy = true;

        const form = this.$el;
        const status = form.querySelector('[data-part="status"]');
        form.querySelectorAll('[data-part="error"][data-for]').forEach((el) => { el.hidden = true; el.textContent = ''; });
        form.querySelectorAll('[data-state="invalid"]').forEach((el) => el.removeAttribute('data-state'));
        form.querySelectorAll('[data-part="input"][aria-invalid]').forEach((input) => this.describe(input, null));

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const body = await response.json().catch(() => ({}));

            if (response.status === 201) {
                form.reset();
                form.querySelectorAll('[data-part="field"], [data-part="submit"]').forEach((el) => { el.hidden = true; });
                this.show(status, body.message ?? '');
                form.setAttribute('data-state', 'sent');
                // The button that had focus is gone: hand focus to the answer rather than dropping it on the page.
                status?.focus();
            } else if (response.status === 422) {
                let first = null;
                for (const [field, messages] of Object.entries(body.errors ?? {})) {
                    const error = form.querySelector(`[data-part="error"][data-for="${CSS.escape(field)}"]`);
                    const wrapper = form.querySelector(`[data-part="field"][data-field="${CSS.escape(field)}"]`);
                    const input = wrapper?.querySelector('[data-part="input"]');
                    if (error) { error.textContent = messages[0] ?? ''; error.hidden = false; }
                    if (wrapper) { wrapper.setAttribute('data-state', 'invalid'); }
                    if (input) { this.describe(input, error); first ??= input; }
                }
                // Nothing else announces a rejected field: focus it, and the screen reader reads its error with it.
                first?.focus();
            } else {
                this.show(status, body.message ?? '');
            }
        } catch {
            form.submit(); // the network failed: fall back to a normal post
        } finally {
            this.busy = false;
        }
    },

    // A live region that appears and changes in one step is often not announced: reveal it, then fill it.
    show(element, text) {
        if (element) {
            element.hidden = false;
            element.textContent = '';
            window.setTimeout(() => { element.textContent = text; }, 100);
        }
    },

    // Marks an input invalid and points it at its error (or, with no error, clears both).
    describe(input, error) {
        const ids = (input.getAttribute('aria-describedby') ?? '').split(' ').filter((id) => id !== '' && !id.endsWith('-error'));
        if (error) {
            input.setAttribute('aria-invalid', 'true');
            if (error.id) { ids.push(error.id); }
        } else {
            input.removeAttribute('aria-invalid');
        }
        if (ids.length > 0) { input.setAttribute('aria-describedby', ids.join(' ')); } else { input.removeAttribute('aria-describedby'); }
    },
}));

// Days, hours and minutes until data-target, updated every minute.
Alpine.data('wkCountdown', () => ({
    label: '',

    // Once the visual label shows, the launch date stays only for assistive technology.
    get dateClass() {
        return this.label === '' ? '' : 'wk-sr';
    },

    init() {
        const target = Date.parse(this.$el.dataset.target ?? '');
        if (Number.isNaN(target)) {
            return;
        }
        const tick = () => {
            const left = Math.max(0, target - Date.now());
            const days = Math.floor(left / 86_400_000);
            const hours = Math.floor((left % 86_400_000) / 3_600_000);
            const minutes = Math.floor((left % 3_600_000) / 60_000);
            this.label = `${days}d ${hours}h ${minutes}m`;
        };
        tick();
        setInterval(tick, 60_000);
    },
}));

// The backend's sidebar on small screens: a disclosure button that shows and hides the navigation (Tabler's own
// toggle needs Bootstrap's script, which the kit does not ship).
Alpine.data('wkNav', () => ({
    open: false,

    toggle() {
        this.open = !this.open;
    },

    get expanded() {
        return this.open ? 'true' : 'false';
    },

    get menuClass() {
        return this.open ? 'show' : '';
    },
}));

// Asks before a destructive form is sent: the form's data-message is the question.
Alpine.data('wkConfirm', () => ({
    check(event) {
        if (!window.confirm(this.$el.dataset.message ?? '')) {
            event.preventDefault();
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();
