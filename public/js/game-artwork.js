document.querySelectorAll('[data-game-artwork]').forEach(image => {
    const showPlaceholder = () => {
        image.classList.add('is-placeholder');
        image.alt = '';
        image.src = 'public/images/logo.svg';
    };
    image.addEventListener('error', showPlaceholder, { once: true });
    if (image.complete && image.naturalWidth === 0) showPlaceholder();
});

