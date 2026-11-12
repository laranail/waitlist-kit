// The kit's Alpine.js components, on Alpine's CSP build: no eval, so a strict Content-Security-Policy needs no
// 'unsafe-eval'. Everything works without this script; it only makes the forms answer in place and the countdown tick.
import Alpine from '@alpinejs/csp';

// Live validation, through Laravel Precognition: the form's own endpoint validates one field (Precognition-Validate-Only)
// and answers 204 or 422 without storing anything. The rules and messages are the server's, so nothing is duplicated
// here. A field is checked when it loses focus after being edited, and again as it is corrected once it showed an
// error. Answers that arrive out of order are dropped.
const LIVE_DELAY = 350;

// `settings[primary]` → `settings.primary`, the key Laravel validates and reports errors under.
const dotted = (name) => name.replace(/\[([^\]]*)\]/g, '.$1').replace(/\.$/, '');

const liveField = (form, input) => {
    const name = input.getAttribute('name') ?? '';
    if (name === '' || name.startsWith('_') || input.type === 'hidden' || input.closest('[aria-hidden="true"]') || !form.matches('form[method="post" i]')) {
        return null;
    }
    const key = dotted(name);

    return {
        key,
        input,
        error: form.querySelector(`[data-part="error"][data-for="${CSS.escape(key)}"]`),
        wrapper: input.closest('[data-part="field"]'),
    };
};

// Shows one field's error, or clears it (message null), on both the public and the backend markup.
const showFieldError = (field, message) => {
    const { input, error, wrapper } = field;
    if (error) {
        error.textContent = message ?? '';
        error.hidden = message === null;
        error.classList.toggle('d-block', message !== null);
    }
    wrapper?.toggleAttribute('data-state', false);
    if (message !== null) { wrapper?.setAttribute('data-state', 'invalid'); }
    input.classList.toggle('is-invalid', message !== null);
    describe(input, message === null ? null : error);
};

// Marks an input invalid and points it at its error (or, with no error, clears both).
const describe = (input, error) => {
    const ids = (input.getAttribute('aria-describedby') ?? '').split(' ').filter((id) => id !== '' && !id.endsWith('-error'));
    if (error) {
        input.setAttribute('aria-invalid', 'true');
        if (error.id) { ids.push(error.id); }
    } else {
        input.removeAttribute('aria-invalid');
    }
    if (ids.length > 0) { input.setAttribute('aria-describedby', ids.join(' ')); } else { input.removeAttribute('aria-describedby'); }
};

const liveState = new WeakMap(); // input → { dirty, seq, timer }

const validateField = async (form, field) => {
    const state = liveState.get(field.input) ?? { dirty: false, seq: 0, timer: 0 };
    const seq = ++state.seq;
    liveState.set(field.input, state);

    const body = new FormData(form);
    // A form sending another method (the backend's PUT) says so in _method; FormData already carries it.
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                Precognition: 'true',
                'Precognition-Validate-Only': field.key,
            },
            body,
            credentials: 'same-origin',
        });
        if (seq !== state.seq) {
            return; // a newer check for this field is on its way
        }
        if (response.status === 204) {
            showFieldError(field, null);
        } else if (response.status === 422) {
            const errors = (await response.json().catch(() => ({}))).errors ?? {};
            showFieldError(field, errors[field.key]?.[0] ?? null);
        }
        // Anything else (a throttle, an expired session) leaves the field as it was: the submit still validates.
    } catch {
        // Offline: say nothing now; the submit reports.
    }
};

// The three events a live form listens to, shared by wkForm and wkLive.
const live = {
    edited(event) {
        const input = event.target;
        if (!(input instanceof HTMLElement) || !('form' in input) || input.form === null) {
            return;
        }
        const field = liveField(input.form, input);
        if (field === null) {
            return;
        }
        const state = liveState.get(input) ?? { dirty: false, seq: 0, timer: 0 };
        state.dirty = true;
        liveState.set(input, state);
        // Once a field shows an error, re-check it as it is corrected, so the error goes as soon as it is fixed.
        if (input.getAttribute('aria-invalid') === 'true') {
            window.clearTimeout(state.timer);
            state.timer = window.setTimeout(() => validateField(input.form, field), LIVE_DELAY);
        }
    },

    left(event) {
        const input = event.target;
        if (!(input instanceof HTMLElement) || !('form' in input) || input.form === null) {
            return;
        }
        const field = liveField(input.form, input);
        // Only a field the visitor changed: tabbing through an empty form raises no errors.
        if (field !== null && liveState.get(input)?.dirty) {
            window.clearTimeout(liveState.get(input).timer);
            validateField(input.form, field);
        }
    },
};

// Posts a sign-up or contact form as JSON and shows the answer in the form's own status and error elements.
Alpine.data('wkForm', () => ({
    busy: false,

    edited: live.edited,
    left: live.left,

    async submit(event) {
        event.preventDefault();
        if (this.busy) {
            return;
        }
        this.busy = true;

        const form = this.$el;
        form.setAttribute('aria-busy', 'true');
        const status = form.querySelector('[data-part="status"]');
        form.querySelectorAll('[data-part="error"][data-for]').forEach((el) => { el.hidden = true; el.textContent = ''; });
        form.querySelectorAll('[data-state="invalid"]').forEach((el) => el.removeAttribute('data-state'));
        form.querySelectorAll('[data-part="input"][aria-invalid]').forEach((input) => describe(input, null));

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const body = await response.json().catch(() => ({}));

            if (response.status === 201 || (response.ok && typeof body.redirect === 'string')) {
                if (typeof body.redirect === 'string') {
                    window.location.assign(body.redirect); // early access: on to the product
                    return;
                }
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
                    if (input) { describe(input, error); first ??= input; }
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
            form.removeAttribute('aria-busy');
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
}));

// The backend: every form inside validates its fields as they change, through the same live checks. Saving stays an
// ordinary post, so a change is never lost to a script error.
Alpine.data('wkLive', () => ({
    edited: live.edited,
    left: live.left,
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

// After a reload with errors the server marks one element `autofocus` (the backend's error summary, or a public form's
// first rejected field). Browsers skip autofocus in some cases (an unfocused window, a non-form element), and a
// screen reader then hears nothing about the errors, so focus it here too when nothing else has focus.
const autofocus = document.querySelector('[autofocus]');
if (autofocus instanceof HTMLElement && (document.activeElement === null || document.activeElement === document.body)) {
    autofocus.focus();
}

window.Alpine = Alpine;
Alpine.start();
