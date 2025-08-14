document.addEventListener('DOMContentLoaded', () => {
  const phrases = [
    "Entrená sin excusas",
    "Fuerza, técnica y foco",
    "Superá tus límites",
    "Hoy es el día",
    "Constancia = Resultados",
    "Respeto y disciplina"
  ];
  const el = document.getElementById('mot-phrase');
  let i = 0;
  function tick() {
    if (!el) return;
    el.classList.remove('show');
    el.classList.add('hide');
    setTimeout(() => {
      i = (i + 1) % phrases.length;
      el.textContent = phrases[i];
      el.classList.remove('hide');
      el.classList.add('show');
    }, 350);
  }
  if (el) { el.textContent = phrases[0]; el.classList.add('show'); }
  setInterval(tick, 2600);
});
// Toggle del menú "Ingresos ▾" por click
(function(){
  const menu = document.querySelector('nav .menu');
  const toggle = menu?.querySelector('.toggle');
  if (!menu || !toggle) return;
  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    menu.classList.toggle('open');
  });
  // cerrar si clic afuera
  document.addEventListener('click', () => menu.classList.remove('open'));
})();
// assets/js/motivation.js
(function(){
  const el = document.getElementById('mot-phrase');
  if (!el) return;
  const frases = [
    'Entrená sin excusas',
    'Disciplina y respeto',
    'Foco. Técnica. Potencia.',
    'Siempre un round más'
  ];
  let i = 0;
  function tick(){
    el.textContent = frases[i % frases.length];
    i++;
  }
  tick();
  setInterval(tick, 3000);
})();
