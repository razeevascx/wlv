const carousel = document.querySelector('.latest-games');
if (carousel) {
    const track = carousel.querySelector('.latest-track');
    const slides = [...track.children];
    const markers = [...carousel.querySelectorAll('[data-latest-index]')];
    const status = carousel.querySelector('[data-latest-status]');
    let target = null;
    let settled = 0;
    let frame;
    let timer;
    const motion = matchMedia('(prefers-reduced-motion: reduce)');
    let paused = motion.matches;
    let hovered = false;
    let focusPaused = false;
    motion.addEventListener('change', event => {
        paused = event.matches;
    });
    carousel.addEventListener('mouseenter', () => { hovered = true; });
    carousel.addEventListener('mouseleave', () => { hovered = false; });
    carousel.addEventListener('focusin', () => { focusPaused = true; });
    carousel.addEventListener('focusout', event => {
        if (!carousel.contains(event.relatedTarget)) focusPaused = false;
    });
    const left = slide => slide.getBoundingClientRect().left - track.getBoundingClientRect().left + track.scrollLeft;
    const nearest = () => slides.reduce((best, slide, index) =>
        Math.abs(left(slide) - track.scrollLeft) < Math.abs(left(slides[best]) - track.scrollLeft) ? index : best, 0);
    const mark = index => markers.forEach((button, i) => {
        if (i === index) button.setAttribute('aria-current', 'true');
        else button.removeAttribute('aria-current');
    });
    const go = index => {
        const current = target ?? nearest();
        target = (index + slides.length) % slides.length;
        mark(target);
        const wraps = index < 0 || index >= slides.length;
        const skipsSlides = Math.abs(target - current) > 1;
        track.scrollTo({
            left: left(slides[target]),
            behavior: motion.matches || wraps || skipsSlides ? 'instant' : 'auto',
        });
    };
    const settle = () => {
        const index = nearest();
        target = null;
        mark(index);
        if (index !== settled && (paused || hovered || focusPaused)) status.textContent = `Slide ${index + 1} of ${slides.length}`;
        settled = index;
    };
    carousel.querySelectorAll('[data-latest-step]').forEach(button => {
        button.addEventListener('click', () => go((target ?? nearest()) + Number(button.dataset.latestStep)));
    });
    markers.forEach((button, index) => button.addEventListener('click', () => go(index)));
    track.addEventListener('scroll', () => {
        if (target === null && !frame) frame = requestAnimationFrame(() => {
            mark(nearest());
            frame = null;
        });
        if (!('onscrollend' in track)) {
            clearTimeout(timer);
            timer = setTimeout(settle, 150);
        }
    }, { passive: true });
    track.addEventListener('scrollend', settle);
    if (slides.length > 1) {
        carousel.querySelector('[data-latest-controls]').hidden = false;
        carousel.querySelector('[data-latest-markers]').hidden = false;
        const autoplay = setInterval(() => {
            if (!paused && !hovered && !focusPaused && !document.hidden) go((target ?? nearest()) + 1);
        }, 4000);
        window.addEventListener('pagehide', () => clearInterval(autoplay), { once: true });
    }
}
