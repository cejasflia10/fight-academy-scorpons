// assets/js/config.js
(function () {
  fetch('/get_config.php', { cache: 'no-store' })
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(applyConfig)
    .catch(() => { /* si falla, no rompe la página */ });

  function applyConfig(cfg) {
    // Colores (si tu CSS usa variables, podés aplicarlas aquí)
    if (cfg.color_principal) document.documentElement.style.setProperty('--c1', cfg.color_principal);
    if (cfg.color_secundario) document.documentElement.style.setProperty('--c2', cfg.color_secundario);

    // Fondo
    if (cfg.fondo_img) {
      document.body.style.backgroundImage = `url('${escapeHtml(cfg.fondo_img)}')`;
      document.body.style.backgroundSize = 'cover';
      document.body.style.backgroundPosition = 'center';
      document.body.style.backgroundAttachment = 'fixed';
    }

    // Logo (si en tu nav tenés un <img>, lo podés añadir sin romper estructura)
    // Ejemplo: insertar después del SVG
    if (cfg.logo_img) {
      const brand = document.querySelector('nav .brand');
      if (brand && !brand.querySelector('img')) {
        const img = document.createElement('img');
        img.src = cfg.logo_img;
        img.alt = 'Logo';
        img.style.height = '28px';
        img.style.marginLeft = '8px';
        brand.appendChild(img);
      }
    }

    // Texto del banner (tu HTML ya tiene .banner)
    if (cfg.texto_banner) {
      const banner = document.querySelector('.banner');
      if (banner) banner.innerHTML = escapeHtml(cfg.texto_banner);
    }

    // Enlaces a redes (si querés usarlos en algún lado)
    // Podés asignarlos a íconos o botones si existen en tu HTML:
    // document.getElementById('yt-link')?.setAttribute('href', cfg.youtube);

    // Google Maps: si tenés un contenedor en otra página, podés embedirlo:
    // const mapBox = document.getElementById('mapBox');
    // if (mapBox && cfg.google_maps) {
    //   mapBox.innerHTML = `<iframe src="${escapeHtml(cfg.google_maps)}" style="border:0;width:100%;height:360px" loading="lazy" allowfullscreen></iframe>`;
    // }
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }
})();
