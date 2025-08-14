<?php
// admin/configuraciones.php
if (session_status() === PHP_SESSION_NONE) session_start();

/* ====== GUARD OPCIONAL ======
   Reemplaza esta verificación por la tuya si tenés login admin.
*/
// if (empty($_SESSION['es_admin'])) { header('Location: /login.php'); exit; }

require_once __DIR__ . '/../conexion.php';

// Leer valores actuales
$res = $conexion->query("SELECT * FROM site_settings WHERE id=1");
$data = $res && $res->num_rows ? $res->fetch_assoc() : [];

// Guardar (POST)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $conexion->prepare("
    INSERT INTO site_settings
    (id, color_principal, color_secundario, fondo_img, logo_img, texto_banner, youtube, instagram, facebook, google_maps)
    VALUES (1,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      color_principal=VALUES(color_principal),
      color_secundario=VALUES(color_secundario),
      fondo_img=VALUES(fondo_img),
      logo_img=VALUES(logo_img),
      texto_banner=VALUES(texto_banner),
      youtube=VALUES(youtube),
      instagram=VALUES(instagram),
      facebook=VALUES(facebook),
      google_maps=VALUES(google_maps)
  ");
  $stmt->bind_param(
    "ssssssssss",
    $_POST['color_principal'],
    $_POST['color_secundario'],
    $_POST['fondo_img'],
    $_POST['logo_img'],
    $_POST['texto_banner'],
    $_POST['youtube'],
    $_POST['instagram'],
    $_POST['facebook'],
    $_POST['google_maps']
  );
  $stmt->execute();
  $stmt->close();
  $msg = '✅ Configuración guardada';
  // refrescar datos
  $res = $conexion->query("SELECT * FROM site_settings WHERE id=1");
  $data = $res && $res->num_rows ? $res->fetch_assoc() : [];
}

function val($k, $d='') {
  global $data;
  return htmlspecialchars($data[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Configuraciones del sitio</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#0b1220;color:#fff}
    .wrap{max-width:900px;margin:32px auto;padding:0 16px}
    .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:16px}
    h1{margin:0 0 12px 0}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .grid-1{grid-template-columns:1fr}
    label{font-weight:600;font-size:.95rem}
    input[type="text"],input[type="url"],textarea,input[type="color"]{
      width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.3);color:#fff
    }
    textarea{min-height:90px;resize:vertical}
    .row{margin-bottom:12px}
    .btn{background:#22c55e;border:0;color:#000;padding:12px 16px;border-radius:10px;font-weight:700;cursor:pointer}
    .msg{margin:12px 0;padding:10px;border-radius:8px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.35)}
    a.link{color:#22c55e;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Configuraciones del sitio</h1>
    <p><a class="link" href="/">← Volver al sitio</a></p>
    <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

    <form method="post" class="card">
      <h2>Colores & Fondo</h2>
      <div class="grid">
        <div class="row">
          <label>Color principal</label>
          <input type="color" name="color_principal" value="<?= val('color_principal', '#22c55e') ?>">
        </div>
        <div class="row">
          <label>Color secundario</label>
          <input type="color" name="color_secundario" value="<?= val('color_secundario', '#14b8a6') ?>">
        </div>
        <div class="row grid-1">
          <label>Fondo (URL de imagen)</label>
          <input type="url" name="fondo_img" placeholder="https://..." value="<?= val('fondo_img') ?>">
        </div>
        <div class="row grid-1">
          <label>Logo (URL de imagen)</label>
          <input type="url" name="logo_img" placeholder="https://..." value="<?= val('logo_img') ?>">
        </div>
      </div>

      <h2 style="margin-top:18px">Textos</h2>
      <div class="grid-1">
        <div class="row">
          <label>Texto del banner (franja promo)</label>
          <input type="text" name="texto_banner" value="<?= val('texto_banner','Promo de bienvenida: 50% OFF en matrícula + clase de prueba sin cargo.') ?>">
        </div>
      </div>

      <h2 style="margin-top:18px">Enlaces</h2>
      <div class="grid">
        <div class="row">
          <label>YouTube (video/canal)</label>
          <input type="url" name="youtube" placeholder="https://youtube.com/..." value="<?= val('youtube') ?>">
        </div>
        <div class="row">
          <label>Instagram</label>
          <input type="url" name="instagram" placeholder="https://instagram.com/..." value="<?= val('instagram') ?>">
        </div>
        <div class="row">
          <label>Facebook</label>
          <input type="url" name="facebook" placeholder="https://facebook.com/..." value="<?= val('facebook') ?>">
        </div>
        <div class="row grid-1">
          <label>Google Maps (URL de embed)</label>
          <textarea name="google_maps" placeholder="https://www.google.com/maps/embed?..."><?= val('google_maps') ?></textarea>
        </div>
      </div>

      <div style="margin-top:16px">
        <button class="btn" type="submit">Guardar cambios</button>
      </div>
    </form>
  </div>
</body>
</html>
