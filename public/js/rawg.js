(() => {
    const gameForm = document.querySelector('[data-game-form]');
    if (!gameForm) return;
    const selection = gameForm.querySelector('[data-rawg-selected]');
    const rawgId = gameForm.elements.rawg_id;
    let selectionRequest;

    async function request(params, signal) {
        const response = await fetch(`rawg.php?${new URLSearchParams(params)}`, { signal });
        const data = await response.json();

        if (!response.ok) throw new Error(data.error || 'Game lookup failed. Please try again.');
        return data;
    }

    gameForm.querySelector('[data-rawg-clear]').addEventListener('click', () => {
        selectionRequest?.abort();
        rawgId.value = '';
        selection.hidden = true;
    });

    document.querySelectorAll('[data-rawg-lookup]').forEach(lookup => {
        lookup.hidden = false;
        const form = lookup.querySelector('form');
        const results = lookup.querySelector('[data-rawg-results]');
        const status = lookup.querySelector('[data-rawg-status]');
        const searchButton = form.querySelector('button[type="submit"]');
        let searchRequest;

        async function selectGame(id) {
            selectionRequest?.abort();
            const controller = new AbortController();
            selectionRequest = controller;
            status.textContent = 'Loading game details…';
            results.setAttribute('aria-busy', 'true');
            try {
                const game = await request({ id }, controller.signal);
                if (controller.signal.aborted) return;
                gameForm.elements.GameName.value = game.name;
                gameForm.elements.GameDescription.value = game.description;
                gameForm.elements.DateReleased.value = game.released || '';
                gameForm.elements.GameRating.value = game.rating;
                rawgId.value = game.id;
                selection.hidden = false;
                selection.querySelector('[data-rawg-selected-name]').textContent =
                    `Selected ${game.name} from RAWG. Images and extra details will be included.`;
                status.textContent = 'Details filled in. Review them and choose Add game to save.';
                const addDialog = document.getElementById('add-game-dialog');
                const searchDialog = document.getElementById('search-game-dialog');
                if (searchDialog?.open) searchDialog.close();
                if (addDialog && !addDialog.open) addDialog.showModal();
                gameForm.elements.GameName.focus();
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = error.message;
            } finally {
                if (selectionRequest === controller) results.removeAttribute('aria-busy');
            }
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            searchRequest?.abort();
            selectionRequest?.abort();
            const controller = new AbortController();
            searchRequest = controller;
            results.replaceChildren();
            status.textContent = 'Searching RAWG…';
            searchButton.disabled = true;
            try {
                const data = await request({ search: form.elements.search.value.trim() }, controller.signal);
                if (controller.signal.aborted) return;
                data.results.forEach(game => {
                    const item = document.createElement('li');
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rawg-result';
                    if (game.cover) {
                        const image = document.createElement('img');
                        image.src = game.cover;
                        image.alt = '';
                        image.width = 80;
                        image.height = 48;
                        image.loading = 'lazy';
                        button.append(image);
                    }
                    const text = document.createElement('span');
                    const title = document.createElement('strong');
                    title.textContent = game.name;
                    const detail = document.createElement('small');
                    detail.textContent = [game.released || 'Release date unannounced', game.platforms.join(', ')].join(' · ');
                    text.append(title, detail);
                    button.append(text);
                    button.addEventListener('click', () => selectGame(game.id));
                    item.append(button);
                    results.append(item);
                });
                status.textContent = data.results.length
                    ? 'Choose a result to review and add it to your catalogue.'
                    : 'No games found. Try another name or add a game manually.';
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = error.message;
            } finally {
                if (searchRequest === controller) searchButton.disabled = false;
            }
        });
    });
})();
