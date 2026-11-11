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
            } else if (response.status === 422) {
                for (const [field, messages] of Object.entries(body.errors ?? {})) {
                    const error = form.querySelector(`[data-part="error"][data-for="${CSS.escape(field)}"]`);
                    const wrapper = form.querySelector(`[data-part="field"][data-field="${CSS.escape(field)}"]`);
                    if (error) { error.textContent = messages[0] ?? ''; error.hidden = false; }
                    if (wrapper) { wrapper.setAttribute('data-state', 'invalid'); }
                }
            } else {
                this.show(status, body.message ?? '');
            }
        } catch {
            form.submit(); // the network failed: fall back to a normal post
        } finally {
            this.busy = false;
        }
    },

    show(element, text) {
        if (element) {
            element.textContent = text;
            element.hidden = false;
        }
    },
}));

// Days, hours and minutes until data-target, updated every minute.
Alpine.data('wkCountdown', () => ({
    label: '',

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

window.Alpine = Alpine;
Alpine.start();
