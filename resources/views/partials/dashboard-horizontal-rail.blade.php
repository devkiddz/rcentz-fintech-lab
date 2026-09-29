<style>
    .dashboard-rail-shell{position:relative;margin-top:1rem}
    .dashboard-rail-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.6rem}
    .dashboard-rail-hint{font-size:.625rem;color:hsl(var(--muted-foreground));letter-spacing:.02em}
    .dashboard-rail-controls{display:none;align-items:center;gap:.35rem}
    .dashboard-rail-control{
        display:inline-flex;height:2rem;width:2rem;align-items:center;justify-content:center;
        border:1px solid hsl(var(--border)/.65);border-radius:.65rem;background:hsl(var(--card));
        color:hsl(var(--muted-foreground));transition:.16s ease
    }
    .dashboard-rail-control:hover{background:hsl(var(--muted)/.65);color:hsl(var(--foreground))}
    .dashboard-infinite-rail{
        display:flex;gap:.8rem;overflow-x:auto;overflow-y:hidden;
        scroll-snap-type:x mandatory;scroll-padding-inline:.1rem;
        overscroll-behavior-inline:contain;-webkit-overflow-scrolling:touch;
        scrollbar-width:none;padding:.1rem .1rem .85rem;
    }
    .dashboard-infinite-rail::-webkit-scrollbar{display:none}
    [data-dashboard-flatten]{display:contents!important}
    [data-dashboard-card]{
        flex:0 0 min(82vw,21.5rem);
        max-height:25rem;
        min-height:17rem;
        min-width:0;
        margin-top:0!important;
        margin-bottom:0!important;
        align-self:stretch;
        overflow-x:hidden;
        overflow-y:auto;
        overscroll-behavior:contain;
        scroll-snap-align:start;
        scroll-snap-stop:always;
        scrollbar-width:thin;
    }
    [data-dashboard-card].dashboard-card-wide{
        flex-basis:min(86vw,31rem);
    }
    [data-dashboard-card]::-webkit-scrollbar{width:5px}
    [data-dashboard-card]::-webkit-scrollbar-thumb{background:hsl(var(--border));border-radius:999px}
    .dashboard-infinite-rail > [data-dashboard-card],
    .dashboard-infinite-rail [data-dashboard-flatten] > [data-dashboard-card]{
        height:25rem;
    }
    @media (max-width:639px){
        .dashboard-infinite-rail{padding-right:1.75rem}
        [data-dashboard-card]{
            flex-basis:80vw;
            height:22rem!important;
            min-height:22rem;
            max-height:22rem;
        }
        [data-dashboard-card].dashboard-card-wide{flex-basis:84vw}
    }
    @media (min-width:640px){
        [data-dashboard-card]{flex-basis:20.5rem}
        [data-dashboard-card].dashboard-card-wide{flex-basis:29rem}
        .dashboard-rail-controls{display:flex}
    }
    @media (min-width:1280px){
        [data-dashboard-card]{flex-basis:21.5rem}
        [data-dashboard-card].dashboard-card-wide{flex-basis:31rem}
    }
    @media (prefers-reduced-motion:reduce){
        .dashboard-infinite-rail{scroll-behavior:auto!important}
    }
</style>

<script>
(() => {
    if (window.__rcentzDashboardRailsBound) return;
    window.__rcentzDashboardRailsBound = true;

    const cardLeft = (rail, card) => {
        const railBox = rail.getBoundingClientRect();
        const cardBox = card.getBoundingClientRect();
        return rail.scrollLeft + cardBox.left - railBox.left;
    };

    const setup = (shell) => {
        const rail = shell.querySelector('[data-dashboard-rail]');
        if (!rail) return;

        rail.querySelectorAll('[data-dashboard-card]').forEach((card) => {
            const text = (card.textContent || '').replace(/\s+/g, ' ').trim();
            const hasInteractiveOrVisual = card.querySelector('a,button,input,select,textarea,table,img,svg,canvas,[data-dashboard-content]');
            if (!text && !hasInteractiveOrVisual) card.remove();
        });

        const cards = () => Array.from(rail.querySelectorAll('[data-dashboard-card]'));
        const prev = shell.querySelector('[data-dashboard-prev]');
        const next = shell.querySelector('[data-dashboard-next]');
        let index = 0;

        const go = (target, behavior = 'smooth') => {
            const list = cards();
            if (!list.length) return;
            index = (target + list.length) % list.length;
            rail.scrollTo({ left: cardLeft(rail, list[index]), behavior });
        };

        const nearestIndex = () => {
            const list = cards();
            if (!list.length) return 0;
            const left = rail.getBoundingClientRect().left;
            let winner = 0;
            let distance = Infinity;
            list.forEach((card, i) => {
                const d = Math.abs(card.getBoundingClientRect().left - left);
                if (d < distance) { distance = d; winner = i; }
            });
            return winner;
        };

        prev?.addEventListener('click', () => { index = nearestIndex(); go(index - 1); });
        next?.addEventListener('click', () => { index = nearestIndex(); go(index + 1); });

        let settle;
        rail.addEventListener('scroll', () => {
            window.clearTimeout(settle);
            settle = window.setTimeout(() => { index = nearestIndex(); }, 120);
        }, { passive: true });

        // Manual navigation only: swipe, trackpad, mouse wheel and arrow controls.
        // No automatic movement so users stay in control of the dashboard position.
    };

    const boot = () => document.querySelectorAll('[data-dashboard-rail-shell]').forEach(setup);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
</script>
