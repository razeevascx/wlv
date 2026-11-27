const lightbox = document.getElementById('game-image-lightbox');

if (lightbox) {
    const triggers = [...document.querySelectorAll('[data-lightbox-trigger]')];
    const image = document.getElementById('lightbox-image');
    const previous = lightbox.querySelector('.lightbox-previous');
    const next = lightbox.querySelector('.lightbox-next');
    let activeIndex = 0;

    const showImage = (index) => {
        activeIndex = (index + triggers.length) % triggers.length;
        const thumbnail = triggers[activeIndex].querySelector('img');
        image.src = triggers[activeIndex].href;
        image.alt = thumbnail?.alt ?? '';
        const hasMultipleImages = triggers.length > 1;
        previous.hidden = !hasMultipleImages;
        next.hidden = !hasMultipleImages;
    };

    triggers.forEach((trigger, index) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            showImage(index);
            lightbox.showModal();
        });
    });

    previous.addEventListener('click', () => showImage(activeIndex - 1));
    next.addEventListener('click', () => showImage(activeIndex + 1));

    lightbox.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft' && triggers.length > 1) {
            event.preventDefault();
            showImage(activeIndex - 1);
        } else if (event.key === 'ArrowRight' && triggers.length > 1) {
            event.preventDefault();
            showImage(activeIndex + 1);
        }
    });

    lightbox.addEventListener('close', () => {
        image.removeAttribute('src');
    });

    if (!('closedBy' in HTMLDialogElement.prototype)) {
        const isOutside = (event) => {
            const bounds = lightbox.getBoundingClientRect();
            return event.clientX < bounds.left || event.clientX > bounds.right
                || event.clientY < bounds.top || event.clientY > bounds.bottom;
        };
        let pressedOutside = false;
        lightbox.addEventListener('pointerdown', (event) => {
            pressedOutside = isOutside(event);
        });
        lightbox.addEventListener('pointerup', (event) => {
            if (pressedOutside && isOutside(event)) lightbox.close();
            pressedOutside = false;
        });
    }
}
