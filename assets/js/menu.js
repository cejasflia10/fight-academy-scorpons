// assets/js/menu.js
(() => {
  // Helpers
  const $ = (sel, el=document) => el.querySelector(sel);
  const $$ = (sel, el=document) => Array.from(el.querySelectorAll(sel));
  const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;

  /* 1) Menú hamburguesa (mobile) */
  const btn = $('.nav-toggle');
  const panel = $('#nav-links');
  if (btn && panel) {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const open = panel.classList.toggle('open');
      btn.setAttribute('aria-expanded', String(open));
    });

    // Cerrar al tocar un link en mobile
    panel.addEventListener('click', (e) => {
      const a = e.target.closest('a');
      if (a && isMobile()) {
        panel.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        panel.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* 2) Submenú "Ingresos" (desktop + mobile) */
  const wrapper = $('#menu-ingresos');
  if (wrapper) {
    const tgl = $('.toggle', wrapper);
    const submenu = $('.submenu', wrapper);

    const setOpen = (open) => {
      wrapper.classList.toggle('open', open);
      if (tgl) tgl.setAttribute('aria-expanded', String(open));
      if (submenu) submenu.hidden = !open;
    };

    if (tgl) {
      tgl.addEventListener('click', (e) => {
        e.preventDefault();
        setOpen(!wrapper.classList.contains('open'));
      });
    }

    // Cerrar al click fuera (solo desktop)
    document.addEventListener('click', (e) => {
      if (!isMobile() && wrapper.classList.contains('open') && !wrapper.contains(e.target)) {
        setOpen(false);
      }
    });

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setOpen(false);
    });
  }

  /* 3) Link "Configuraciones": asegurar presencia y URL correcta */
  (function ensureConfigLink(){
    const submenu = $('#menu-ingresos .submenu');
    if (!submenu) return;

    let link = $('#ing-config', submenu);
    if (!link) {
      link = document.createElement('a');
      link.id = 'ing-config';
      link.target = '_blank';
      link.rel = 'noopener';
      link.textContent = 'Configuraciones';
      submenu.appendChild(link);
    }

    const host = (location.hostname || '').toLowerCase();
    const isProd = host.includes('onrender.com');
    const prodUrl = 'https://fight-academy-scorpons.onrender.com/admin/configuraciones.php';
    link.href = isProd ? prodUrl : '/admin/configuraciones.php';
  })();

  /* 4) Marcar link activo en el nav */
  (function highlightActive(){
    const current = location.pathname.replace(/\/+$/, '') || '/';
    $$('.nav-links .nav-link').forEach(a => {
      try {
        const hrefPath = new URL(a.getAttribute('href'), location.origin).pathname
          .replace(/\/+$/, '') || '/';
        if (hrefPath === current) a.classList.add('active');
      } catch {}
    });
  })();
})();
