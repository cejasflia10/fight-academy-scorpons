// assets/js/fotos.js
(function(){
  const cont = document.getElementById('gallery');
  if (!cont) return;

  function card(item){
    const fig = document.createElement('figure');
    fig.className = 'ph-card';
    const a = document.createElement('a');
    a.href = item.src;
    a.target = '_blank';
    a.rel = 'noopener';
    const img = document.createElement('img');
    img.loading = 'lazy';
    img.alt = item.titulo || 'Foto';
    img.src = item.src;
    a.appendChild(img);
    const cap = document.createElement('figcaption');
    cap.textContent = item.titulo || '';
    fig.appendChild(a);
    fig.appendChild(cap);
    return fig;
  }

  fetch('/api/fotos.php', {cache:'no-store'})
    .then(r => r.json())
    .then(data => {
      cont.innerHTML = '';
      if (!data.ok) throw new Error(data.error || 'Error al cargar');
      const items = data.items || [];
      if (!items.length){
        cont.innerHTML = '<p class="muted">No hay fotos activas aún.</p>';
        return;
      }
      const frag = document.createDocumentFragment();
      items.forEach(it => {
        if (it.src) frag.appendChild(card(it));
      });
      cont.appendChild(frag);
    })
    .catch(err => {
      cont.innerHTML = '<p class="err">No se pudieron cargar las fotos.</p>';
      console.error(err);
    });
})();
