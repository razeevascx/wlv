for (const link of document.querySelectorAll('[data-account-dialog]')) {
    const dialog = document.getElementById(link.dataset.accountDialog);
    if (!dialog?.showModal) continue;
    link.setAttribute('aria-haspopup', 'dialog');
    link.addEventListener('click', (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        dialog.showModal();
    });
}

for (const form of document.querySelectorAll('dialog form[action="login.php"], dialog form[action="signup.php"]')) {
    const dialog = form.closest('dialog');
    const message = dialog.querySelector('[data-account-error]');
    dialog.addEventListener('close', () => {
        form.reset();
        message.hidden = true;
    });
    const refreshCaptcha = () => {
        const image = form.querySelector('[data-captcha]');
        const url = new URL(image.src);
        url.searchParams.set('refresh', Date.now());
        image.src = url.href;
        form.elements.captcha.value = '';
    };
    form.querySelector('[data-refresh-captcha]').addEventListener('click', refreshCaptcha);
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"], button:not([type])');
        if (submit.disabled) return;
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });
            const result = await response.json();
            if (response.ok && result.redirect) {
                window.location.assign(result.redirect);
                return;
            }
            refreshCaptcha();
            message.textContent = result.error || 'Please try again.';
        } catch {
            refreshCaptcha();
            message.textContent = 'Unable to complete your request. Please try again.';
        } finally {
            message.hidden = !message.textContent;
            submit.disabled = false;
        }
    });
}

const accountMode = new URLSearchParams(window.location.search).get('auth');
if (accountMode === 'login' || accountMode === 'signup') {
    document.getElementById(accountMode + '-dialog')?.showModal();
}
