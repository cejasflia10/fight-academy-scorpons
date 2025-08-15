<?php
// admin/contenido.php
session_start();
if (empty($_SESSION['ADMIN_OK'])) { header('Location: login.php'); exit; }
require __DIR__ . '/../conexion.php';

$secciones = [
  'disciplinas' => [
    'titulo' => 'Disciplinas',
    'campos' => [
      'titulo' => ['label'=>'Título','type'=>'text','req'=>true],
      'descripcion' => ['label'=>'Descripción','type'=>'textarea'],
      'imagen_url' => ['label'=>'Imagen (URL o subir)','type'=>'file_or_url'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
  'fotos' => [
    'titulo' => 'Fotos (Galería)',
    'campos' => [
      'titulo' => ['label'=>'Título','type'=>'text'],
      'imagen_url' => ['label'=>'Imagen (URL o subir)','type'=>'file_or_url'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
  'videos' => [
    'titulo' => 'Videos cortos (Reels)',
    'campos' => [
      'titulo' => ['label'=>'Título','type'=>'text'],
      'video_url' => ['label'=>'Enlace (YouTube/Instagram o MP4)','type'=>'text','req'=>true],
      'tipo' => ['label'=>'Tipo','type'=>'select','options'=>['youtube'=>'YouTube','instagram'=>'Instagram','mp4'=>'MP4']],
      'cover_url' => ['label'=>'Cover (URL opcional)','type'=>'text'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
  'ofertas' => [
    'titulo' => 'Ofertas',
    'campos' => [
      'titulo' => ['label'=>'Título','type'=>'text','req'=>true],
      'descripcion' => ['label'=>'Descripción','type'=>'textarea'],
      'precio' => ['label'=>'Precio','type'=>'number','step'=>'0.01'],
      'vigente_desde' => ['label'=>'Desde','type'=>'date'],
      'vigente_hasta' => ['label'=>'Hasta','type'=>'date'],
      'imagen_url' => ['label'=>'Imagen (URL o subir)','type'=>'file_or_url'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
  'promociones' => [
    'titulo' => 'Promociones',
    'campos' => [
      'titulo' => ['label'=>'Título','type'=>'text','req'=>true],
      'descripcion' => ['label'=>'Descripción','type'=>'textarea'],
      'imagen_url' => ['label'=>'Imagen (URL o subir)','type'=>'file_or_url'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
  'ventas' => [
    'titulo' => 'Ventas (Productos)',
    'campos' => [
      'nombre' => ['label'=>'Nombre','type'=>'text','req'=>true],
      'descripcion' => ['label'=>'Descripción','type'=>'textarea'],
      'precio' => ['label'=>'Precio','type'=>'number','step'=>'0.01'],
      'imagen_url' => ['label'=>'Imagen (URL o subir)','type'=>'file_or_url'],
      'stock' => ['label'=>'Stock','type'=>'number'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
  'equipo' => [
    'titulo' => 'Equipo',
    'campos' => [
      'nombre' => ['label'=>'Nombre','type'=>'text','req'=>true],
      'rol' => ['label'=>'Rol','type'=>'text'],
      'bio' => ['label'=>'Bio','type'=>'textarea'],
      'foto_url' => ['label'=>'Foto (URL o subir)','type'=>'file_or_url'],
      'instagram' => ['label'=>'Instagram (URL)','type'=>'text'],
      'orden' => ['label'=>'Orden','type'=>'number'],
      'activo' => ['label'=>'Activo','type'=>'checkbox']
    ]
  ],
];

$sec = $_GET['sec'] ?? 'disciplinas';
if (!isset($secciones[$sec])) $sec = 'disciplinas';
$cfg = $secciones[$sec];

$msg = ''; $err = '';

function up_or_url($name, $fallback=''){
  // intenta subir archivo, si no hay, devuelve el URL del input *_url
  $url_key = $name; // el campo visible del form se llama igual
  if (!empty($_FILES[$name]['name'])) {
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $fn = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES[$name]['name']);
    $dest = $dir . '/' . $fn;
    if (@move_uploaded_file($_FILES[$name]['tmp_name'], $dest)) {
      return '/uploads/' . $fn;
    }
  }
  return trim($_POST[$url_key] ?? $fallback);
}

// Crear/Editar
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $cols = array_keys($cfg['campos']);
  $vals = [];

  foreach ($cols as $c) {
    $def = $cfg['campos'][$c];
    if (($def['type'] ?? '') === 'file_or_url') {
      $vals[$c] = up_or_url($c, $_POST[$c] ?? '');
    } elseif (($def['type'] ?? '') === 'checkbox') {
      $vals[$c] = isset($_POST[$c]) ? 1 : 0;
    } else {
      $vals[$c] = $_POST[$c] ?? null;
    }
  }

  if ($id>0) {
    // update
    $sets = implode(', ', array_map(fn($k)=>"$k=?", $cols));
    $stmt = $conexion->prepare("UPDATE $sec SET $sets WHERE id=?");
    $types = str_repeat('s', count($cols)) . 'i';
    $stmt->bind_param($types, ...array_values($vals), $id);
    $ok = $stmt->execute();
    $stmt->close();
    $msg = $ok ? '✅ Actualizado' : '❌ Error al actualizar';
  } else {
    // insert
    $marks = implode(',', array_fill(0, count($cols), '?'));
    $stmt = $conexion->prepare("INSERT INTO $sec (".implode(',',$cols).") VALUES ($marks)");
    $types = str_repeat('s', count($cols));
    $stmt->bind_param($types, ...array_values($vals));
    $ok = $stmt->execute();
    $stmt->close();
    $msg = $ok ? '✅ Guardado' : '❌ Error al guardar';
  }
}

// Eliminar
if (isset($_GET['del'])) {
  $del = (int)$_GET['del'];
  $conexion->query("DELETE FROM $sec WHERE id=$del");
  $msg = '🗑️ Eliminado';
}

// Listado
$rows = [];
$r = $conexion->query("SELECT * FROM $sec ORDER BY orden ASC, id DESC");
if ($r) while($x = $r->fetch_assoc()) $rows[] = $x;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contenido | <?=h($cfg['titulo'])?></title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0b1220;color:#fff;margin:0}
.wrap{max-width:1100px;margin:28px auto;padding:0 16px}
.top{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-bottom:10px}
nav.tabs a{color:#a3e635;text-decoration:none;margin-right:10px;padding:6px 10px;border-radius:8px;border:1px solid rgba(163,230,53,.2)}
nav.tabs a.active{background:rgba(163,230,53,.15)}
.card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:14px;margin-bottom:14px}
table{width:100%;border-collapse:collapse}
th,td{border-bottom:1px solid rgba(255,255,255,.12);padding:8px;vertical-align:top}
input[type="text"],input[type="number"],input[type="date"],textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.3);color:#fff}
textarea{min-height:90px;resize:vertical}
.btn{background:#22c55e;border:0;color:#000;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer}
.badge{font-size:.8rem;opacity:.85}
a.link{color:#22c55e;text-decoration:none}
img.thumb{height:44px;border-radius:6px}
.msg{margin:10px 0;padding:10px;border-radius:8px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.35)}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>Contenido — <?=h($cfg['titulo'])?></h1>
    <div>
      <a class="link" href="configuraciones.php">← Configuraciones</a> ·
      <a class="link" href="../" target="_blank">Ver sitio</a> ·
      <a class="link" href="logout.php">Salir</a>
    </div>
  </div>

  <nav class="tabs">
    <?php foreach ($secciones as $k=>$v): ?>
      <a class="<?= $k===$sec?'active':''?>" href="?sec=<?=$k?>"><?=h($v['titulo'])?></a>
    <?php endforeach; ?>
  </nav>

  <?php if($msg): ?><div class="msg"><?=h($msg)?></div><?php endif; ?>

  <div class="card">
    <h2><?=h($cfg['titulo'])?> — nuevo/editar</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" id="form-id" value="">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <?php foreach ($cfg['campos'] as $name=>$def): ?>
          <div>
            <label><strong><?=h($def['label'])?></strong></label>
            <?php if (($def['type']??'') === 'textarea'): ?>
              <textarea name="<?=$name?>" id="f-<?=$name?>"></textarea>
            <?php elseif (($def['type']??'') === 'checkbox'): ?>
              <input type="checkbox" name="<?=$name?>" id="f-<?=$name?>" value="1">
            <?php elseif (($def['type']??'') === 'file_or_url'): ?>
              <input type="file" name="<?=$name?>" accept="image/*,video/mp4" style="margin-bottom:6px">
              <input type="text" name="<?=$name?>" id="f-<?=$name?>" placeholder="o pegar URL https://">
              <div class="badge">TIP: En Render es preferible usar URL (las subidas locales se pierden al redeploy).</div>
            <?php elseif (($def['type']??'') === 'select'): ?>
              <select name="<?=$name?>" id="f-<?=$name?>">
                <?php foreach(($def['options']??[]) as $val=>$lab): ?>
                  <option value="<?=$val?>"><?=h($lab)?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input
                type="<?=h($def['type']??'text')?>"
                name="<?=$name?>" id="f-<?=$name?>"
                <?= isset($def['step']) ? 'step="'.h($def['step']).'"' : '' ?>
                <?= !empty($def['req']) ? 'required' : '' ?>
              >
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit">Guardar</button></div>
    </form>
  </div>

  <div class="card">
    <h2>Listado</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <?php foreach (array_keys($cfg['campos']) as $c): ?>
            <th><?=h(ucfirst(str_replace('_',' ',$c)))?></th>
          <?php endforeach; ?>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <?php foreach ($cfg['campos'] as $c=>$def): ?>
              <td>
                <?php
                  $val = $row[$c] ?? '';
                  if (($def['type']??'')==='checkbox'){
                    echo $val ? 'Sí' : 'No';
                  } elseif ($c==='imagen_url' || $c==='foto_url') {
                    echo $val ? '<img class="thumb" src="'.h($val).'" alt="">' : '';
                  } else {
                    echo h((string)$val);
                  }
                ?>
              </td>
            <?php endforeach; ?>
            <td style="white-space:nowrap">
              <a class="link" href="?sec=<?=$sec?>&del=<?=$row['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="<?=2+count($cfg['campos'])?>">Sin registros</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
