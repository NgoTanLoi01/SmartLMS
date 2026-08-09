const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }
    callback();
};

onReady(() => {
    const page = document.querySelector('[data-gradebook-setup]');
    if (!page || page.dataset.initialized === 'true') return;
    page.dataset.initialized = 'true';

    const list = page.querySelector('[data-category-list]');
    const template = page.querySelector('[data-category-template]');
    let categorySequence = [...page.querySelectorAll('[data-category-code]')].reduce((maximum, input) => {
        const match = input.name.match(/^categories\[(\d+)]/);
        return match ? Math.max(maximum, Number(match[1]) + 1) : maximum;
    }, 0);

    const syncCategoryOptions = () => {
        const categories = [...page.querySelectorAll('[data-category-row]')].map((row) => ({
            code: row.querySelector('[data-category-code]')?.value.trim() || '',
            name: row.querySelector('[data-category-name]')?.value.trim() || '',
        })).filter((category) => category.code !== '');

        page.querySelectorAll('[data-category-select]').forEach((select) => {
            const selected = select.value || select.dataset.selected || '';
            select.replaceChildren(...categories.map((category) => {
                const option = document.createElement('option');
                option.value = category.code;
                option.textContent = category.name || category.code;
                option.selected = category.code === selected;
                return option;
            }));
        });
    };

    page.querySelector('[data-add-category]')?.addEventListener('click', () => {
        if (!list || !template || list.children.length >= 10) return;
        const index = categorySequence++;
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[data-name]').forEach((input) => {
            input.name = input.dataset.name.replace('__INDEX__', index);
            input.removeAttribute('data-name');
        });
        list.append(fragment);
        syncCategoryOptions();
    });

    page.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-category]');
        if (!button || !list || list.children.length <= 1) return;
        button.closest('[data-category-row]')?.remove();
        syncCategoryOptions();
    });
    page.addEventListener('input', (event) => {
        if (event.target.matches('[data-category-code], [data-category-name]')) syncCategoryOptions();
    });

    syncCategoryOptions();
});
