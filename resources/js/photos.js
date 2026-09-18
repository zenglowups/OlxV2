for (const workspace of document.querySelectorAll('[data-photos]')) {
    const upload = workspace.querySelector('[data-photo-upload]');
    const input = upload?.querySelector('input[type=file]');
    let leaving = false;
    if (upload) {
        const error = upload.querySelector('[data-upload-error]');
        upload.addEventListener('submit', (event) => {
            const files = [...input.files];
            if (
                files.length > Number(upload.dataset.limit) ||
                files.some((file) => file.size > 10 * 1024 * 1024)
            ) {
                event.preventDefault();
                error.textContent = upload.dataset.invalid;
                error.hidden = false;
                return;
            }
            leaving = true;
            const button = upload.querySelector('button[type=submit]');
            button.textContent = button.dataset.uploadLabel;
            button.disabled = true;
        });
        window.addEventListener('beforeunload', (event) => {
            if (!leaving && input.files.length) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }
    if (workspace.dataset.photoStatus) {
        let checks = 0;
        const poll = async () => {
            if (leaving || ++checks > 60) return;
            try {
                const response = await fetch(workspace.dataset.photoStatus, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) return;
                const data = await response.json();
                if (!data.pending) {
                    if (!input?.files.length) window.location.reload();
                    return;
                }
            } catch {
                return;
            }
            setTimeout(poll, document.hidden ? 5000 : 2000);
        };
        setTimeout(poll, 2000);
    }
}
