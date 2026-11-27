const toast = document.getElementById('action-toast');

if (toast) {
    if (typeof toast.showPopover === 'function') {
        toast.showPopover();
    } else {
        toast.classList.add('is-visible');
        toast.querySelector('[popovertargetaction="hide"]')?.addEventListener('click', () => {
            toast.classList.remove('is-visible');
        });
    }

    const message = toast.querySelector('p')?.textContent?.trim();
    if (message && typeof document.ariaNotify === 'function') {
        document.ariaNotify(message, { politeness: 'polite' });
    }

}
