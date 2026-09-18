// Progressive enhancement: every step remains a normal server-validated form.
for (const navigation of document.querySelectorAll('.wizard-steps')) {
    const current = navigation.querySelector('[aria-current="step"]');
    if (current)
        navigation.scrollLeft = current.offsetLeft - navigation.offsetLeft - 16;
}
for (const form of document.querySelectorAll('[data-draft-form]')) {
    const status = form.querySelector('[data-save-status]');
    const retry = form.querySelector('[data-retry-save]');
    const reload = form.querySelector('[data-reload-draft]');
    const errors = form.querySelector('[data-save-errors]');
    const version = form.elements.namedItem('version');
    let revision = form.dataset.initialDirty === 'true' ? 1 : 0;
    let savedRevision = 0;
    let pending = null;
    let timer;
    let conflicted = false;
    let submitting = false;
    let leaving = false;

    const show = (state, message) => {
        status.textContent = message;
        status.parentElement.dataset.state = state;
        retry.hidden = state !== 'error';
        reload.hidden = state !== 'conflict';
    };
    const priceFields = form.querySelector('[data-price-fields]');
    const updatePrice = () => {
        if (!priceFields) return;
        const priced = ['fixed', 'negotiable'].includes(
            form.elements.namedItem('pricing_mode').value,
        );
        priceFields.hidden = !priced;
        // Currency stays submitted; unused amounts are excluded by the server.
    };
    updatePrice();

    const save = async () => {
        if (conflicted || submitting) return false;
        if (pending) {
            const success = await pending;
            return success && revision !== savedRevision ? save() : success;
        }
        if (revision === savedRevision) return true;
        const sentRevision = revision;
        const body = new URLSearchParams(new FormData(form));
        body.delete('intent');
        show('saving', form.dataset.saving);
        errors.hidden = true;
        pending = (async () => {
            const abort = new AbortController();
            const timeout = setTimeout(() => abort.abort(), 15000);
            try {
                const response = await fetch(form.action, {
                    method: 'PATCH',
                    body,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: abort.signal,
                });
                const data = await response.json();
                if (response.status === 409 && data.conflict) {
                    conflicted = true;
                    show('conflict', form.dataset.conflict);
                    return false;
                }
                if (!response.ok || !Number.isInteger(data.version)) {
                    show('error', form.dataset.failure);
                    if (data.errors) {
                        errors.replaceChildren();
                        const list = document.createElement('ul');
                        for (const messages of Object.values(data.errors)) {
                            const item = document.createElement('li');
                            item.textContent = messages[0];
                            list.append(item);
                        }
                        errors.append(list);
                        errors.hidden = false;
                    }
                    return false;
                }
                version.value = String(data.version);
                savedRevision = sentRevision;
                show(
                    revision === savedRevision ? 'saved' : 'dirty',
                    revision === savedRevision
                        ? form.dataset.saved
                        : form.dataset.unsaved,
                );
                return true;
            } catch {
                show('error', form.dataset.failure);
                return false;
            } finally {
                clearTimeout(timeout);
            }
        })();
        const success = await pending;
        pending = null;
        return success && revision !== savedRevision ? save() : success;
    };

    const changed = () => {
        revision++;
        updatePrice();
        if (conflicted || submitting) return;
        show('dirty', form.dataset.unsaved);
        clearTimeout(timer);
        timer = setTimeout(save, 900);
    };
    form.addEventListener('input', changed);
    form.addEventListener('change', (event) => {
        // Radios/selects also support browsers that do not emit input.
        if (event.target.matches('select,input[type=radio]')) changed();
    });
    retry.addEventListener('click', () => {
        clearTimeout(timer);
        save();
    });
    if (revision) show('dirty', form.dataset.unsaved);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting || conflicted) return;
        clearTimeout(timer);
        submitting = true;
        const submitter = event.submitter;
        if (pending) await pending;
        if (conflicted) {
            submitting = false;
            return;
        }
        // The current form is posted in full. The version includes any in-flight save.
        const intent = document.createElement('input');
        intent.type = 'hidden';
        intent.name = 'intent';
        intent.value = submitter?.value || 'next';
        form.append(intent);
        leaving = true;
        // Native submit avoids re-entering the browser's ongoing submit event.
        // Initial constraint validation already ran; the server validates again.
        HTMLFormElement.prototype.submit.call(form);
    });
    document.addEventListener('click', async (event) => {
        const link = event.target.closest('a[href]');
        if (
            !link ||
            link === reload ||
            event.defaultPrevented ||
            event.button !== 0 ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey ||
            link.target === '_blank' ||
            link.hash ||
            submitting ||
            leaving ||
            (!pending && revision === savedRevision)
        )
            return;
        event.preventDefault();
        clearTimeout(timer);
        if (await save()) {
            leaving = true;
            window.location.assign(link.href);
        }
    });
    window.addEventListener('beforeunload', (event) => {
        if (!leaving && (pending || revision !== savedRevision)) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}
