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
