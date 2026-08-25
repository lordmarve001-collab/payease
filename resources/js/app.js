import './bootstrap';

// ────────────────────────────────────────────────────────────
// Scroll-reveal: adds `.is-visible` to `.reveal` elements
// as they enter the viewport. Respects prefers-reduced-motion.
// Re-initialises after Livewire SPA navigations.
// ────────────────────────────────────────────────────────────
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
);

function initReveals(root = document) {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    root.querySelectorAll('.reveal:not(.is-visible)').forEach((el) => {
        if (reduced) {
            el.classList.add('is-visible');
            return;
        }
        revealObserver.observe(el);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initReveals());
} else {
    initReveals();
}

// Re-run reveals after Livewire SPA page swaps.
document.addEventListener('livewire:navigated', () => initReveals());
document.addEventListener('livewire:init', () => initReveals());
