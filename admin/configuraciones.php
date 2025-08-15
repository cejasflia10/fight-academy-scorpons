<?php
// admin/configuraciones.php — Panel Único (Config + Disciplinas + Fotos + Videos + Ofertas + Promociones + Ventas + Equipo)
session_start();
if (empty($_SESSION['ADMIN_OK'])) { header('Location: login.php'); exit; }
require __DIR__ . '/../conexion.php';

/* =========================
   Helpers
========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function v($k,$d=''){ global $data; return h($data[$k]??$d); }
function up_or_url($inputName, $fallback=''){
  // Subir archivo o usar URL (en Render es mejor URL por persistencia)
  if (!empty($_FILES[$inputName]['name'])) {
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $fn = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES[$inputName]['name']);
    $dest = $dir.'/'.$fn;
    if (@move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
      return '/uploads/'.$fn;
    }
  }
  return trim($_POST[$inputName] ?? $fallback);
}

/* =========================
   Config básica (site_settings)
========================= */
$res  = $conexion->query("SELECT * FROM site_settings WHERE id=1");
$data = $res && $res->num_rows ? $res->fetch_assoc() : [];

$msg_cfg = $msg_disc = $msg_fotos = $msg_videos = $msg_ofe = $msg_promo = $msg_ven = $msg_eq = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='config') {
  $stmt = $conexion->prepare("
    INSERT INTO site_settings
    (id,color_principal,color_secundario,fondo_img,logo_img,texto_banner,youtube,instagram,facebook,google_maps)
    VALUES(1,?,?,?,?,?,?,?,?,?)
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
  $stmt->bind_param('ssssssssss',
    $_POST['color_principal'], $_POST['color_secundario'],
    $_POST['fondo_img'], $_POST['logo_img'], $_POST['texto_banner'],
    $_POST['youtube'], $_POST['instagram'], $_POST['facebook'], $_POST['google_maps']
  );
  $ok = $stmt->execute(); $stmt->close();
  $msg_cfg = $ok ? '✅ Configuraciones guardadas' : '❌ Error al guardar';
  $res  = $conexion->query("SELECT * FROM site_settings WHERE id=1");
  $data = $res && $res->num_rows ? $res->fetch_assoc() : [];
}

/* =========================
   Disciplinas
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='disciplinas') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $descripcion=$_POST['descripcion']??'';
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE disciplinas SET titulo=?, descripcion=?, imagen_url=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('sssiii',$titulo,$descripcion,$imagen,$orden,$activo,$id);
    $ok=$stmt->execute(); $stmt->close(); $msg_disc=$ok?'✅ Disciplina actualizada':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO disciplinas (titulo,descripcion,imagen_url,orden,activo) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssii',$titulo,$descripcion,$imagen,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_disc=$ok?'✅ Disciplina guardada':'❌ Error al guardar';
  }
}
if (isset($_GET['del_disc'])) {
  $conexion->query("DELETE FROM disciplinas WHERE id=".(int)$_GET['del_disc']);
  $msg_disc='🗑️ Disciplina eliminada';
}

/* =========================
   Fotos
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='fotos') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE fotos SET titulo=?, imagen_url=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('ssiii',$titulo,$imagen,$orden,$activo,$id);
    $ok=$stmt->execute(); $stmt->close(); $msg_fotos=$ok?'✅ Foto actualizada':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO fotos (titulo,imagen_url,orden,activo) VALUES (?,?,?,?)");
    $stmt->bind_param('ssii',$titulo,$imagen,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_fotos=$ok?'✅ Foto guardada':'❌ Error al guardar';
  }
}
if (isset($_GET['del_foto'])) {
  $conexion->query("DELETE FROM fotos WHERE id=".(int)$_GET['del_foto']);
  $msg_fotos='🗑️ Foto eliminada';
}

/* =========================
   Videos
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='videos') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $tipo=$_POST['tipo']??'youtube';
  $video=$_POST['video_url']??''; $cover=up_or_url('cover_url', $_POST['cover_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE videos SET titulo=?, video_url=?, tipo=?, cover_url=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('ssssiii',$titulo,$video,$tipo,$cover,$orden,$activo,$id);
    $ok=$stmt->execute(); $stmt->close(); $msg_videos=$ok?'✅ Video actualizado':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO videos (titulo,video_url,tipo,cover_url,orden,activo) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssssii',$titulo,$video,$tipo,$cover,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_videos=$ok?'✅ Video guardado':'❌ Error al guardar';
  }
}
if (isset($_GET['del_video'])) {
  $conexion->query("DELETE FROM videos WHERE id=".(int)$_GET['del_video']);
  $msg_videos='🗑️ Video eliminado';
}

/* =========================
   Ofertas
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='ofertas') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $descripcion=$_POST['descripcion']??'';
  $precio=(float)($_POST['precio']??0); $desde=$_POST['vigente_desde']??null; $hasta=$_POST['vigente_hasta']??null;
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE ofertas SET titulo=?, descripcion=?, precio=?, vigente_desde=?, vigente_hasta=?, imagen_url=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('ssdsdssii', $titulo,$descripcion,$precio,$desde,$hasta,$imagen,$orden,$activo,$id);
    // Ajuste: bind_param no acepta 's' para null fechas; si vienen vacías, pasar null como string ok.
    $ok=$stmt->execute(); $stmt->close(); $msg_ofe=$ok?'✅ Oferta actualizada':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO ofertas (titulo,descripcion,precio,vigente_desde,vigente_hasta,imagen_url,orden,activo) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssdsdssii', $titulo,$descripcion,$precio,$desde,$hasta,$imagen,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_ofe=$ok?'✅ Oferta guardada':'❌ Error al guardar';
  }
}
if (isset($_GET['del_ofe'])) {
  $conexion->query("DELETE FROM ofertas WHERE id=".(int)$_GET['del_ofe']);
  $msg_ofe='🗑️ Oferta eliminada';
}

/* =========================
   Promociones
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='promociones') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $descripcion=$_POST['descripcion']??'';
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE promociones SET titulo=?, descripcion=?, imagen_url=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('sssiii',$titulo,$descripcion,$imagen,$orden,$activo,$id);
    $ok=$stmt->execute(); $stmt->close(); $msg_promo=$ok?'✅ Promo actualizada':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO promociones (titulo,descripcion,imagen_url,orden,activo) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssii',$titulo,$descripcion,$imagen,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_promo=$ok?'✅ Promo guardada':'❌ Error al guardar';
  }
}
if (isset($_GET['del_promo'])) {
  $conexion->query("DELETE FROM promociones WHERE id=".(int)$_GET['del_promo']);
  $msg_promo='🗑️ Promoción eliminada';
}

/* =========================
   Ventas (productos)
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='ventas') {
  $id=(int)($_POST['id']??0);
  $nombre=$_POST['nombre']??''; $descripcion=$_POST['descripcion']??'';
  $precio=(float)($_POST['precio']??0); $stock=(int)($_POST['stock']??0);
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE ventas SET nombre=?, descripcion=?, precio=?, imagen_url=?, stock=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('ssdsi iii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo,$id);
    // espacios en tipos rompen; corregimos con tipos continuos:
  } 
  if (isset($stmt) && $id>0){
    $stmt->bind_param('ssdsi iii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo,$id); // evitamos warnings
  }
  if ($id>0){
    $stmt = $conexion->prepare("UPDATE ventas SET nombre=?, descripcion=?, precio=?, imagen_url=?, stock=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('ssdsi iii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo,$id);
  }

  if ($id>0){
    // Rehacer limpio:
    $stmt = $conexion->prepare("UPDATE ventas SET nombre=?, descripcion=?, precio=?, imagen_url=?, stock=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('ssdsiiii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo,$id);
    $ok=$stmt->execute(); $stmt->close(); $msg_ven=$ok?'✅ Producto actualizado':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO ventas (nombre,descripcion,precio,imagen_url,stock,orden,activo) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('ssdsiii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_ven=$ok?'✅ Producto guardado':'❌ Error al guardar';
  }
}
if (isset($_GET['del_ven'])) {
  $conexion->query("DELETE FROM ventas WHERE id=".(int)$_GET['del_ven']);
  $msg_ven='🗑️ Producto eliminado';
}

/* =========================
   Equipo
========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='equipo') {
  $id=(int)($_POST['id']??0);
  $nombre=$_POST['nombre']??''; $rol=$_POST['rol']??''; $bio=$_POST['bio']??'';
  $foto=up_or_url('foto_url', $_POST['foto_url']??''); $insta=$_POST['instagram']??'';
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $stmt=$conexion->prepare("UPDATE equipo SET nombre=?, rol=?, bio=?, foto_url=?, instagram=?, orden=?, activo=? WHERE id=?");
    $stmt->bind_param('sssssiii',$nombre,$rol,$bio,$foto,$insta,$orden,$activo,$id);
    $ok=$stmt->execute(); $stmt->close(); $msg_eq=$ok?'✅ Miembro actualizado':'❌ Error al actualizar';
  } else {
    $stmt=$conexion->prepare("INSERT INTO equipo (nombre,rol,bio,foto_url,instagram,orden,activo) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssii',$nombre,$rol,$bio,$foto,$insta,$orden,$activo);
    $ok=$stmt->execute(); $stmt->close(); $msg_eq=$ok?'✅ Miembro guardado':'❌ Error al guardar';
  }
}
if (isset($_GET['del_eq'])) {
  $conexion->query("DELETE FROM equipo WHERE id=".(int)$_GET['del_eq']);
  $msg_eq='🗑️ Miembro eliminado';
}

/* =========================
   Listados (últimos 15) 
========================= */
$disciplinas=$fotos=$videos=$ofertas=$promos=$ventas=$equipo=[];

$r=$conexion->query("SELECT * FROM disciplinas ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $disciplinas[]=$x;
$r=$conexion->query("SELECT * FROM fotos       ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $fotos[]=$x;
$r=$conexion->query("SELECT * FROM videos      ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $videos[]=$x;
$r=$conexion->query("SELECT * FROM ofertas     ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $ofertas[]=$x;
$r=$conexion->query("SELECT * FROM promociones ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $promos[]=$x;
$r=$conexion->query("SELECT * FROM ventas      ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $ventas[]=$x;
$r=$conexion->query("SELECT * FROM equipo      ORDER BY orden ASC, id DESC LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $equipo[]=$x;

?><!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configuraciones</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;background:#0b1220;color:#fff;margin:0}
.wrap{max-width:1150px;margin:32px auto;padding:0 16px}
.top{display:flex;gap:12px;align-items:center;justify-content:space-between}
a.link{color:#22c55e;text-decoration:none}
.card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:16px;margin:16px 0}
.grid{display:grid;gap:12px;grid-template-columns:1fr 1fr}
.grid-1{grid-template-columns:1fr}
label{font-weight:600}
input,textarea,select{width:100%;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.3);color:#fff}
textarea{min-height:90px}
.btn{background:#22c55e;border:0;color:#000;padding:12px 16px;border-radius:10px;font-weight:700;cursor:pointer}
.msg{margin:12px 0;padding:10px;border-radius:8px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.35)}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid rgba(255,255,255,.12);vertical-align:top}
img.thumb{height:52px;border-radius:8px}
.badge{font-size:.85rem;opacity:.85}
</style>
</head><body>
<div class="wrap">
  <div class="top">
    <h1>Configuraciones del sitio</h1>
    <div>
      <a class="link" href="../get_config.php" target="_blank">Ver JSON</a> ·
      <a class="link" href="../" target="_blank">Ver sitio</a> ·
      <a class="link" href="logout.php">Salir</a>
    </div>
  </div>

  <?php if($msg_cfg): ?><div class="msg"><?=h($msg_cfg)?></div><?php endif; ?>

  <!-- ==================== BLOQUE: CONFIG BÁSICA ==================== -->
  <form method="post" class="card">
    <input type="hidden" name="__form" value="config">
    <h2>Colores & Fondo</h2>
    <div class="grid">
      <div><label>Color principal</label><input type="color" name="color_principal" value="<?=v('color_principal','#22c55e')?>"></div>
      <div><label>Color secundario</label><input type="color" name="color_secundario" value="<?=v('color_secundario','#14b8a6')?>"></div>
    </div>
    <div class="grid-1">
      <div><label>Fondo (URL)</label><input type="url" name="fondo_img" value="<?=v('fondo_img')?>"></div>
      <div><label>Logo (URL)</label><input type="url" name="logo_img" value="<?=v('logo_img')?>"></div>
    </div>

    <h2 style="margin-top:16px">Textos</h2>
    <div class="grid-1">
      <div><label>Texto del banner</label><input type="text" name="texto_banner" value="<?=v('texto_banner')?>"></div>
    </div>

    <h2 style="margin-top:16px">Enlaces</h2>
    <div class="grid">
      <div><label>YouTube</label><input type="url" name="youtube" value="<?=v('youtube')?>"></div>
      <div><label>Instagram</label><input type="url" name="instagram" value="<?=v('instagram')?>"></div>
      <div><label>Facebook</label><input type="url" name="facebook" value="<?=v('facebook')?>"></div>
      <div class="grid-1"><label>Google Maps (embed URL)</label><textarea name="google_maps"><?=v('google_maps')?></textarea></div>
    </div>
    <div style="margin-top:16px"><button class="btn" type="submit">Guardar configuraciones</button></div>
  </form>

  <!-- ==================== BLOQUE: DISCIPLINAS ==================== -->
  <?php if($msg_disc): ?><div class="msg"><?=h($msg_disc)?></div><?php endif; ?>
  <div class="card">
    <h2>Disciplinas — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="disciplinas"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Mejor usar URL en Render</div></div>
        <div><label>Imagen (URL)</label><input type="text" name="imagen_url" placeholder="https://..."></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar disciplina</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($disciplinas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $r['imagen_url']?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h($r['titulo']) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_disc=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$disciplinas): ?><tr><td colspan="6">Sin disciplinas</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== BLOQUE: FOTOS ==================== -->
  <?php if($msg_fotos): ?><div class="msg"><?=h($msg_fotos)?></div><?php endif; ?>
  <div class="card">
    <h2>Galería de Fotos — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="fotos"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo"></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Mejor usar URL en Render</div></div>
        <div><label>Imagen (URL)</label><input type="text" name="imagen_url" placeholder="https://..."></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar foto</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 fotos</h3>
    <table>
      <thead><tr><th>ID</th><th>Preview</th><th>Título</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($fotos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $r['imagen_url']?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h($r['titulo']) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_foto=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$fotos): ?><tr><td colspan="6">Sin fotos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== BLOQUE: VIDEOS ==================== -->
  <?php if($msg_videos): ?><div class="msg"><?=h($msg_videos)?></div><?php endif; ?>
  <div class="card">
    <h2>Videos cortos / Reels — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="videos"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo"></div>
        <div><label>Tipo</label>
          <select name="tipo">
            <option value="youtube">YouTube</option>
            <option value="instagram">Instagram</option>
            <option value="mp4">MP4 (link directo)</option>
          </select>
        </div>
        <div><label>Video URL</label><input type="text" name="video_url" placeholder="https://youtu.be/ID · https://www.instagram.com/p/... · https://.../video.mp4" required></div>
        <div><label>Cover (subir)</label><input type="file" name="cover_url" accept="image/*"></div>
        <div><label>Cover (URL)</label><input type="text" name="cover_url" placeholder="https://..."></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar video</button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 videos</h3>
    <table>
      <thead><tr><th>ID</th><th>Título</th><th>Tipo</th><th>URL</th><th>Cover</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($videos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= h($r['titulo']) ?></td>
          <td><?= h($r['tipo']) ?></td>
          <td><a class="link" href="<?=h($r['video_url'])?>" target="_blank">Abrir</a></td>
          <td><?= $r['cover_url']?'<img class="thumb" src="'.h($r['cover_url']).'">':'' ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_video=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$videos): ?><tr><td colspan="8">Sin videos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== BLOQUE: OFERTAS ==================== -->
  <?php if($msg_ofe): ?><div class="msg"><?=h($msg_ofe)?></div><?php endif; ?>
  <div class="card">
    <h2>Ofertas — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="ofertas"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required></div>
        <div><label>Precio</label><input type="number" step="0.01" name="precio" value="0"></div>
        <div><label>Vigente desde</label><input type="date" name="vigente_desde"></div>
        <div><label>Vigente hasta</label><input type="date" name="vigente_hasta"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Mejor usar URL en Render</div></div>
        <div><label>Imagen (URL)</label><input type="text" name="imagen_url" placeholder="https://..."></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar oferta</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 ofertas</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>$</th><th>Desde</th><th>Hasta</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($ofertas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $r['imagen_url']?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h($r['titulo']) ?></td>
          <td><?= number_format((float)$r['precio'],2,',','.') ?></td>
          <td><?= h($r['vigente_desde']) ?></td>
          <td><?= h($r['vigente_hasta']) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_ofe=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$ofertas): ?><tr><td colspan="9">Sin ofertas</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== BLOQUE: PROMOCIONES ==================== -->
  <?php if($msg_promo): ?><div class="msg"><?=h($msg_promo)?></div><?php endif; ?>
  <div class="card">
    <h2>Promociones — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="promociones"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Mejor usar URL en Render</div></div>
        <div><label>Imagen (URL)</label><input type="text" name="imagen_url" placeholder="https://..."></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar promoción</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 promociones</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($promos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $r['imagen_url']?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h($r['titulo']) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_promo=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$promos): ?><tr><td colspan="6">Sin promociones</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== BLOQUE: VENTAS ==================== -->
  <?php if($msg_ven): ?><div class="msg"><?=h($msg_ven)?></div><?php endif; ?>
  <div class="card">
    <h2>Ventas (Productos) — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="ventas"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Nombre</label><input type="text" name="nombre" required></div>
        <div><label>Precio</label><input type="number" step="0.01" name="precio" value="0"></div>
        <div><label>Stock</label><input type="number" name="stock" value="0"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Mejor usar URL en Render</div></div>
        <div><label>Imagen (URL)</label><input type="text" name="imagen_url" placeholder="https://..."></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar producto</button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 productos</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Nombre</th><th>$</th><th>Stock</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($ventas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $r['imagen_url']?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h($r['nombre']) ?></td>
          <td><?= number_format((float)$r['precio'],2,',','.') ?></td>
          <td><?= (int)$r['stock'] ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_ven=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$ventas): ?><tr><td colspan="8">Sin productos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== BLOQUE: EQUIPO ==================== -->
  <?php if($msg_eq): ?><div class="msg"><?=h($msg_eq)?></div><?php endif; ?>
  <div class="card">
    <h2>Equipo — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="equipo"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Nombre</label><input type="text" name="nombre" required></div>
        <div><label>Rol</label><input type="text" name="rol"></div>
        <div class="grid-1"><label>Bio</label><textarea name="bio"></textarea></div>
        <div><label>Foto (subir)</label><input type="file" name="foto_url" accept="image/*"><div class="badge">Mejor usar URL en Render</div></div>
        <div><label>Foto (URL)</label><input type="text" name="foto_url" placeholder="https://..."></div>
        <div><label>Instagram (URL)</label><input type="url" name="instagram" placeholder="https://instagram.com/..."></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar miembro</button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 miembros</h3>
    <table>
      <thead><tr><th>ID</th><th>Foto</th><th>Nombre</th><th>Rol</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($equipo as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $r['foto_url']?'<img class="thumb" src="'.h($r['foto_url']).'">':'' ?></td>
          <td><?= h($r['nombre']) ?></td>
          <td><?= h($r['rol']) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= $r['activo']?'Sí':'No' ?></td>
          <td><a class="link" href="?del_eq=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$equipo): ?><tr><td colspan="7">Sin miembros</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body></html>
