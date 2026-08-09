const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }
    callback();
};

onReady(() => {
    document.querySelectorAll('[data-grade-cell]').forEach((cell) => {
        if (cell.dataset.initialized === 'true') return;
        cell.dataset.initialized = 'true';
        const status = cell.querySelector('[data-grade-status]');
        const points = cell.querySelector('[data-grade-points]');
        if (!status || !points) return;
        const sync = () => {
            const graded = status.value === 'graded';
            points.disabled = status.disabled || !graded;
            points.required = graded && !status.disabled;
            if (!graded) points.value = '';
        };
        status.addEventListener('change', sync);
        sync();
    });
});
