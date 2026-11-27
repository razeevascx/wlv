(() => {
    const button = document.getElementById('view-more-games');
    const status = document.getElementById('view-more-status');
    const grid = document.querySelector('.genre-section .game-grid');
    let offset = 0;
    button.hidden = false;
    button.addEventListener('click', async () => {
        const query = new URLSearchParams(location.search);
        query.set('more', '1');
        query.set('offset', offset);
        button.disabled = true;
        grid.setAttribute('aria-busy', 'true');
        status.textContent = 'Finding more games…';
        try {
            const response = await fetch(`filter.php?${query}`);
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Games could not be loaded. Please try again.');
            const template = document.createElement('template');
            template.innerHTML = data.html;
            template.content.querySelectorAll('[data-game-artwork]').forEach(image => {
                image.addEventListener('error', () => {
                    image.classList.add('is-placeholder');
                    image.src = 'public/images/logo.svg';
                }, { once: true });
            });
            if (data.count) grid.querySelector('.game-empty')?.remove();
            grid.append(template.content);
            offset = data.offset;
            button.hidden = !data.more;
            status.textContent = data.count ? `Added ${data.count} more games.` : data.more ? 'No new matches in this batch. View more to continue.' : 'No more matching games.';
            if (!data.more) status.textContent += data.count ? ' All matching games have been loaded.' : '';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
            grid.removeAttribute('aria-busy');
        }
    });
})();
