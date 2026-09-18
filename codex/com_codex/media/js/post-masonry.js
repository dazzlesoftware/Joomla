/** Pack Bootstrap columns across the page without changing DOM/keyboard order. */
(() => {
    const initialise = () => document.querySelectorAll('[data-post-masonry]').forEach(grid => {
        if (grid.dataset.masonryReady) return;
        grid.dataset.masonryReady = 'true';
        const items = Array.from(grid.children);
        let frame;
        const layout = () => {
            frame = null;
            if (!items.length || !grid.clientWidth) return;
            const width = items[0].getBoundingClientRect().width;
            const columns = Math.max(1, Math.round(grid.getBoundingClientRect().width / width));
            grid.classList.toggle('post-masonry-active', columns > 1);
            if (columns === 1) {
                grid.style.removeProperty('height');
                items.forEach(item => {
                    item.style.removeProperty('left');
                    item.style.removeProperty('top');
                });
                return;
            }
            const heights = Array(columns).fill(0);
            const rtl = getComputedStyle(grid).direction === 'rtl';
            items.forEach(item => {
                const column = heights.indexOf(Math.min(...heights));
                item.style.left = `${(rtl ? columns - 1 - column : column) * width}px`;
                item.style.top = `${heights[column]}px`;
                heights[column] += item.getBoundingClientRect().height + parseFloat(getComputedStyle(item).marginTop || 0);
            });
            grid.style.height = `${Math.max(...heights)}px`;
        };
        const schedule = () => { if (!frame) frame = requestAnimationFrame(layout); };
        // Images, fonts, text wrapping and viewport changes can all change card heights.
        const observer = new ResizeObserver(schedule);
        items.forEach(item => observer.observe(item));
        window.addEventListener('resize', schedule, { passive: true });
        schedule();
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialise);
    else initialise();
})();
