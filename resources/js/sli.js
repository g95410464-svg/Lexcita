import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// CodingNepal sidebar interaction, adapted for SLI roles, keyboard access and persistence.
const sidebar = document.getElementById('sli-sidebar');
if (sidebar) {
    const desktopToggle = sidebar.querySelector('[data-sidebar-toggle]');
    const menuToggle = sidebar.querySelector('[data-sidebar-menu-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const mobile = window.matchMedia('(max-width: 1024px)');
    let compact = document.documentElement.dataset.sliSidebar === 'compact';
    let menuOpen = false;

    const renderSidebar = () => {
        document.documentElement.dataset.sliSidebar = compact ? 'compact' : 'expanded';
        sidebar.classList.toggle('menu-active', mobile.matches && menuOpen);
        desktopToggle.setAttribute('aria-expanded', String(!compact));
        desktopToggle.setAttribute('aria-label', compact ? 'Expandir barra lateral' : 'Contraer barra lateral');
        menuToggle.setAttribute('aria-expanded', String(mobile.matches && menuOpen));
        menuToggle.setAttribute('aria-label', menuOpen ? 'Ocultar menú' : 'Mostrar menú');
        menuToggle.querySelector('span').textContent = menuOpen ? 'close' : 'menu';
        backdrop.hidden = !mobile.matches || !menuOpen;
    };

    const closeMenu = (restoreFocus = false) => {
        menuOpen = false;
        renderSidebar();
        if (restoreFocus) menuToggle.focus();
    };

    desktopToggle.addEventListener('click', () => {
        compact = !compact;
        try { localStorage.setItem('sli.sidebar.compact', String(compact)); } catch (_) { /* Optional preference. */ }
        renderSidebar();
    });
    menuToggle.addEventListener('click', () => {
        menuOpen = !menuOpen;
        renderSidebar();
    });
    backdrop.addEventListener('click', () => closeMenu(true));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuOpen) closeMenu(true);
    });
    document.addEventListener('focusin', (event) => {
        if (menuOpen && !sidebar.contains(event.target)) closeMenu();
    });
    mobile.addEventListener('change', () => {
        const focusWasInNavigation = sidebar.querySelector('.sli-sidebar-navigation').contains(document.activeElement);
        closeMenu();
        if (mobile.matches && focusWasInNavigation) menuToggle.focus();
    });
    window.addEventListener('pageshow', () => closeMenu());
    renderSidebar();
}

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.querySelector('span').textContent = visible ? 'visibility_off' : 'visibility';
    });
});

document.querySelectorAll('[data-submit-once]').forEach((form) => {
    const submit = form.querySelector('button[type="submit"]');
    if (submit && !submit.dataset.label) submit.dataset.label = submit.textContent.trim();
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (button && form.checkValidity()) {
            button.disabled = true;
            button.textContent = 'Un momento…';
        }
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-submit-once] button[type="submit"]').forEach((button) => {
        button.disabled = false;
        if (button.dataset.label) button.textContent = button.dataset.label;
    });
});
