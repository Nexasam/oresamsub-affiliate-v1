(() => {
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');
    const closeMenu = () => {
        if (!menuToggle || !menu) return;
        menuToggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('open');
    };

    menuToggle?.addEventListener('click', () => {
        const opening = menuToggle.getAttribute('aria-expanded') !== 'true';
        menuToggle.setAttribute('aria-expanded', String(opening));
        menu?.classList.toggle('open', opening);
    });
    menu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && closeMenu());

    const tabs = [...document.querySelectorAll('[data-interface-tab]')];
    const panels = [...document.querySelectorAll('[data-interface-panel]')];
    const activateTab = (tab) => {
        const key = tab.dataset.interfaceTab;
        tabs.forEach((item) => {
            const active = item === tab;
            item.setAttribute('aria-selected', String(active));
            item.tabIndex = active ? 0 : -1;
        });
        panels.forEach((panel) => {
            const active = panel.dataset.interfacePanel === key;
            panel.hidden = !active;
            panel.classList.toggle('active', active);
        });
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            const offset = event.key === 'ArrowRight' ? 1 : -1;
            const next = tabs[(index + offset + tabs.length) % tabs.length];
            activateTab(next);
            next.focus();
        });
    });

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const reveals = document.querySelectorAll('[data-reveal]');
    if (reducedMotion || !('IntersectionObserver' in window)) {
        reveals.forEach((element) => element.classList.add('revealed'));
    } else {
        const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        }), { threshold: 0.12 });
        reveals.forEach((element) => observer.observe(element));
    }
})();
