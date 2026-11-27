document.querySelectorAll('.genre-section').forEach(section => {
    const controls = section.querySelectorAll('[data-section-view]');
    controls.forEach(button => button.addEventListener('click', () => {
        section.querySelector('.game-grid').dataset.view = button.dataset.sectionView;
        controls.forEach(control => control.setAttribute('aria-pressed', String(control === button)));
    }));
});
