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

    const progress = document.querySelector('[data-scroll-progress]');
    const updateScrollProgress = () => {
        if (!progress) return;
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0;
        progress.style.transform = `scaleX(${ratio})`;
    };
    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    updateScrollProgress();

    const networkDetails = {
        provider: ['01', 'Provider connections', 'Approved APIs and reusable adapters create dependable routes without repeating integration work.'],
        parent: ['02', 'Parent business', 'Controls plans, acquisition pricing, routes and settlement across its affiliate network.'],
        affiliate: ['03', 'Affiliate websites', 'Each affiliate operates a branded customer business while inheriting the parent’s eligible catalogue.'],
        customer: ['04', 'Affiliate customers', 'Customers fund wallets, purchase services and track activity through their chosen storefront.'],
    };
    const networkMap = document.querySelector('[data-network-map]');
    const networkNodes = [...(networkMap?.querySelectorAll('[data-network-node]') ?? [])];
    const showNetworkDetail = (node) => {
        const detail = networkDetails[node.dataset.networkNode];
        if (!detail || !networkMap) return;
        networkMap.querySelector('[data-network-step]').textContent = detail[0];
        networkMap.querySelector('[data-network-title]').textContent = detail[1];
        networkMap.querySelector('[data-network-description]').textContent = detail[2];
        networkNodes.forEach((item) => item.classList.toggle('is-active', item === node));
    };
    networkNodes.forEach((node) => {
        node.addEventListener('mouseenter', () => showNetworkDetail(node));
        node.addEventListener('focus', () => showNetworkDetail(node));
        node.addEventListener('click', () => showNetworkDetail(node));
    });

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const parallax = document.querySelector('[data-parallax]');
    if (parallax && !reducedMotion) {
        parallax.addEventListener('pointermove', (event) => {
            const bounds = parallax.getBoundingClientRect();
            const x = ((event.clientX - bounds.left) / bounds.width - 0.5) * 8;
            const y = ((event.clientY - bounds.top) / bounds.height - 0.5) * 8;
            parallax.style.transform = `perspective(900px) rotateY(${x * 0.35}deg) rotateX(${-y * 0.25}deg)`;
        });
        parallax.addEventListener('pointerleave', () => { parallax.style.transform = ''; });
    }
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
