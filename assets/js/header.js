// assets/js/header.js
(() => {
  const CANDIDATES = ['/partials/header.html', '/header.html'];
  const cacheBust = 'v=' + Date.now();

  const $ = (s, el=document) => el.querySelector(s);

  function htmlToEl(html){
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }

  function mountHeader(html){
    const newNav = htmlToEl(html);

    // 1) Si hay contenedor #site-header, úsalo
    let mount = $('#site-header');
    if (mount) {
      mount.innerHTML = '';
      mount.appendChild(newNav);
    } else {
      // 2) Si existe un <nav.site-nav> viejo, reemplazarlo
      const old = document.querySelector('nav.site-nav');
      if (old) {
        old.replaceWith(newNav);
      } else {
        // 3) Si no hay nada, lo creo al inicio del body
        mount = document.createElement('div');
        mount.id = 'site-header';
        document.body.prepend(mount);
        mount.appendChild(newNav);
      }
    }

    initBehavior();
  }

  function initBehavior(){
    // Ajustar link de Configuraciones según entorno
    const cfg = $('#ing-config');
    if (cfg) {
      const host = (location.hostname || '').toLowerCase();
      const isProd = host.includes('onrender.com');
      const prodUrl = cfg.getAttribute('data-prod') || 'https://fight-academy-scorpons.onrender.com/admin/configuraciones.php';
      cfg.href = isProd ? prodUrl : '/admin/configuraciones.php';
    }

    // Marcar link activo
    const current = location.pathname.replace(/\/+$/,'') || '/';
    document.querySelectorAll('.nav-links .nav-link').forEach(a => {
      try {
        const p = new URL(a.getAttribute('href'), location.origin).pathname.replace(/\/+$/,'') || '/';
        if (p === current) a.classList.add('active');
      } catch {}
    });

    // Menú hamburguesa
    const btn = $('.nav-toggle');
    const panel = $('#nav-links');
    if (btn && panel) {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const open = panel.classList.toggle('open');
        btn.setAttribute('aria-expanded', String(open));
      });
      // Cerrar al tocar link en mobile
      panel.addEventListener('click', (e) => {
        const a = e.target.closest('a');
        if (a && window.innerWidth < 768) {
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

    // Submenú Ingresos
    const wrap = $('#menu-ingresos');
    if (wrap) {
      const toggle = wrap.querySelector('.toggle');
      const submenu = wrap.querySelector('.submenu');
      const setOpen = (open) => {
        wrap.classList.toggle('open', open);
        if (toggle) toggle.setAttribute('aria-expanded', String(open));
        if (submenu) submenu.hidden = !open;
      };
      if (toggle) {
        toggle.addEventListener('click', (e) => {
          e.preventDefault();
          setOpen(!wrap.classList.contains('open'));
        });
      }
      // Cerrar al click fuera (solo desktop)
      document.addEventListener('click', (e) => {
        if (window.innerWidth < 768) return;
        if (!wrap.contains(e.target)) setOpen(false);
      });
      // ESC
      document.addEventListener('keydown', (e) => { if (e.key==='Escape') setOpen(false); });
    }
  }

  // Intentar cargar el header desde varias rutas
  (function loadHeader(i=0){
    if (i >= CANDIDATES.length) {
      console.error('No se pudo cargar header.html');
      // fallback mínimo para no dejar la página sin nav
      mountHeader('<nav class="site-nav"><a class="brand" href="/index.html">FIGHT ACADEMY SCORPIONS</a></nav>');
      return;
    }
    const url = CANDIDATES[i] + (CANDIDATES[i].includes('?')?'&':'?') + cacheBust;
    fetch(url, {cache: 'no-store'})
      .then(r => r.ok ? r.text() : Promise.reject(r.status))
      .then(mountHeader)
      .catch(() => loadHeader(i+1));
  })();
})();
