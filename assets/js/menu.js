// assets/js/menu.js
(function(){
  const menu = document.querySelector('nav .menu');
  const toggle = menu?.querySelector('.toggle');
  if (!menu || !toggle) return;

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    menu.classList.toggle('open');
  });

  document.addEventListener('click', () => menu.classList.remove('open'));
})();
// Garantizar que el ítem "Configuraciones" existe
(function(){
  const menu = document.querySelector('nav .menu');
  if (!menu) return;
  const box = menu.querySelector('.submenu');
  if (!box) return;
  if (!box.querySelector('#ing-config')) {
    const a = document.createElement('a');
    a.id = 'ing-config';
    a.href = '/admin/configuraciones.php';
    a.target = '_blank';
    a.rel = 'noopener';
    a.textContent = 'Configuraciones';
    box.appendChild(a);
  }

  // Toggle por click (móvil)
  const toggle = menu.querySelector('.toggle');
  if (toggle) {
    toggle.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      menu.classList.toggle('open');
    });
    document.addEventListener('click', () => menu.classList.remove('open'));
  }
})();
// Menú hamburguesa
(() => {
  const btn = document.querySelector('.nav-toggle');
  const panel = document.getElementById('nav-links');
  if (!btn || !panel) return;

  btn.addEventListener('click', () => {
    const isOpen = panel.classList.toggle('open');
    btn.setAttribute('aria-expanded', String(isOpen));
  });

  // Cerrar al tocar un link en mobile
  panel.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (a && window.innerWidth < 768) {
      panel.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
})();

// Submenú "Ingresos"
(() => {
  const wrapper = document.getElementById('menu-ingresos');
  if (!wrapper) return;
  const toggle = wrapper.querySelector('.toggle');
  const submenu = wrapper.querySelector('.submenu');

  const setOpen = (open) => {
    wrapper.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', String(open));
    if (submenu) submenu.hidden = !open;
  };

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    setOpen(!wrapper.classList.contains('open'));
  });

  // Cerrar al click fuera en desktop
  document.addEventListener('click', (e) => {
    if (window.innerWidth < 768) return;
    if (!wrapper.contains(e.target)) setOpen(false);
  });
})();
