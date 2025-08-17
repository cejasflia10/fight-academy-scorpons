<?php
// admin/configuraciones.php — Panel Único (SIN LOGIN)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ==================== Conexión ====================
require __DIR__ . '/../conexion.php';

// Estado de conexión
$db_ok = isset($conexion) && $conexion instanceof mysqli;
if ($db_ok) {
  if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); }
  @$conexion->set_charset('utf8mb4');
}

// ==================== Helpers ====================
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('v')) {
  function v($k,$d=''){ global $data; return h($data[$k]??$d); }
}

// Helpers seguros para arrays/filas
if (!function_exists('arr_get')) {
  function arr_get($a, $k, $d = '') {
    return (is_array($a) && array_key_exists($k, $a)) ? $a[$k] : $d;
  }
}
if (!function_exists('first_of')) {
  // Devuelve el primer campo existente/no vacío de una fila
  function first_of(array $row, array $keys, $default = '') {
    foreach ($keys as $k) {
      if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
        return (string)$row[$k];
      }
    }
    return $default;
  }
}

// Lee env de varios lugares y alias
if (!function_exists('envv')) {
  function envv(array $keys){
    foreach($keys as $k){
      $v = getenv($k);
      if ($v === false || $v === '') { $v = $_ENV[$k]   ?? ''; }
      if ($v === '')                 { $v = $_SERVER[$k]?? ''; }
      if ($v !== '') return trim($v);
    }
    return '';
  }
}

// Subir ARCHIVO (mismo nombre para file y texto)
if (!function_exists('up_or_url')) {
  function up_or_url($inputName, $fallback=''){
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
}

// Subir ARCHIVO (campo archivo distinto de campo URL) – preferir URL si viene
if (!function_exists('up_or_url2')) {
  function up_or_url2($fileField, $urlField, $fallback=''){
    $url = trim($_POST[$urlField] ?? '');
    if ($url !== '') return $url; // preferir URL si está

    if (!empty($_FILES[$fileField]['name'])) {
      $dir = __DIR__ . '/../uploads';
      if (!is_dir($dir)) @mkdir($dir, 0775, true);
      $ext = pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION);
      $fn  = time().'_'.mt_rand(100000,999999).($ext?'.'.$ext:'');
      $dest = $dir.'/'.$fn;
      if (@move_uploaded_file($_FILES[$fileField]['tmp_name'], $dest)) {
        return '/uploads/'.$fn;
      }
    }
    return $fallback;
  }
}

// ===== Helpers SQL: esquema/ORDER BY y prepare seguro =====
if (!function_exists('tabla_existe')) {
  function tabla_existe($cx, $tabla){
    $rs = @$cx->query("SHOW TABLES LIKE '$tabla'");
    return $rs && $rs->num_rows>0;
  }
}
if (!function_exists('col_existe')) {
  function col_existe($cx, $tabla, $col){
    $rs = @$cx->query("SHOW COLUMNS FROM `$tabla` LIKE '$col'");
    return $rs && $rs->num_rows>0;
  }
}
if (!function_exists('ensure_table')) {
  function ensure_table($cx, $tabla, $createSql){
    if (!tabla_existe($cx,$tabla)) { @ $cx->query($createSql); }
  }
}
if (!function_exists('ensure_col')) {
  function ensure_col($cx, $tabla, $col, $addSql){
    if (!col_existe($cx,$tabla,$col)) { @ $cx->query("ALTER TABLE `$tabla` $addSql"); }
  }
}
if (!function_exists('order_by')) {
  function order_by($cx, $tabla){
    return col_existe($cx,$tabla,'orden') ? 'orden ASC, id DESC' : 'id DESC';
  }
}
// prepare que no rompe: devuelve stmt o false y setea $err
if (!function_exists('stmt_or_err')) {
  function stmt_or_err($sql, &$err){
    global $conexion;
    $err = '';
    $stmt = @$conexion->prepare($sql);
    if (!$stmt) { $err = $conexion->error ?: 'prepare() falló'; return false; }
    return $stmt;
  }
}

// ===== Autofix mínimo de esquema =====
if ($db_ok) {
  ensure_table($conexion,'site_settings',"
    CREATE TABLE site_settings(
      id TINYINT PRIMARY KEY,
      color_principal VARCHAR(7),
      color_secundario VARCHAR(7),
      fondo_img VARCHAR(255),
      logo_img VARCHAR(255),
      texto_banner VARCHAR(255),
      youtube VARCHAR(255),
      instagram VARCHAR(255),
      facebook VARCHAR(255),
      google_maps TEXT,
      cld_cloud_name VARCHAR(100),
      cld_upload_preset VARCHAR(120)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'site_settings','cld_cloud_name',"ADD COLUMN cld_cloud_name VARCHAR(100)");
  ensure_col($conexion,'site_settings','cld_upload_preset',"ADD COLUMN cld_upload_preset VARCHAR(120)");
  @$conexion->query("INSERT IGNORE INTO site_settings (id) VALUES (1)");

  // DISCIPLINAS
  ensure_table($conexion,'disciplinas',"
    CREATE TABLE disciplinas(
      id INT AUTO_INCREMENT PRIMARY KEY,
      titulo VARCHAR(150) NOT NULL,
      descripcion TEXT,
      imagen_url VARCHAR(255),
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'disciplinas','titulo',"ADD COLUMN titulo VARCHAR(150) NOT NULL DEFAULT ''");
  ensure_col($conexion,'disciplinas','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'disciplinas','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

  // FOTOS
  ensure_table($conexion,'fotos',"
    CREATE TABLE fotos(
      id INT AUTO_INCREMENT PRIMARY KEY,
      titulo VARCHAR(150),
      imagen_url VARCHAR(255),
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'fotos','titulo',"ADD COLUMN titulo VARCHAR(150) NULL");
  ensure_col($conexion,'fotos','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'fotos','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

  // VIDEOS
  ensure_table($conexion,'videos',"
    CREATE TABLE videos(
      id INT AUTO_INCREMENT PRIMARY KEY,
      titulo VARCHAR(150),
      video_url VARCHAR(255),
      tipo VARCHAR(20) DEFAULT 'youtube',
      cover_url VARCHAR(255),
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'videos','titulo',"ADD COLUMN titulo VARCHAR(150) NULL");
  ensure_col($conexion,'videos','tipo',"ADD COLUMN tipo VARCHAR(20) DEFAULT 'youtube'");
  ensure_col($conexion,'videos','cover_url',"ADD COLUMN cover_url VARCHAR(255)");
  ensure_col($conexion,'videos','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'videos','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

  // OFERTAS
  ensure_table($conexion,'ofertas',"
    CREATE TABLE ofertas(
      id INT AUTO_INCREMENT PRIMARY KEY,
      titulo VARCHAR(150) NOT NULL,
      descripcion TEXT,
      precio DECIMAL(10,2) NOT NULL DEFAULT 0,
      vigente_desde DATE NULL,
      vigente_hasta DATE NULL,
      imagen_url VARCHAR(255),
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'ofertas','titulo',"ADD COLUMN titulo VARCHAR(150) NOT NULL DEFAULT ''");
  ensure_col($conexion,'ofertas','precio',"ADD COLUMN precio DECIMAL(10,2) NOT NULL DEFAULT 0");
  ensure_col($conexion,'ofertas','vigente_desde',"ADD COLUMN vigente_desde DATE NULL");
  ensure_col($conexion,'ofertas','vigente_hasta',"ADD COLUMN vigente_hasta DATE NULL");
  ensure_col($conexion,'ofertas','imagen_url',"ADD COLUMN imagen_url VARCHAR(255)");
  ensure_col($conexion,'ofertas','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'ofertas','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

  // PROMOCIONES
  ensure_table($conexion,'promociones',"
    CREATE TABLE promociones(
      id INT AUTO_INCREMENT PRIMARY KEY,
      titulo VARCHAR(150) NOT NULL,
      descripcion TEXT,
      imagen_url VARCHAR(255),
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'promociones','titulo',"ADD COLUMN titulo VARCHAR(150) NOT NULL DEFAULT ''");
  ensure_col($conexion,'promociones','imagen_url',"ADD COLUMN imagen_url VARCHAR(255)");
  ensure_col($conexion,'promociones','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'promociones','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

  // VENTAS
  ensure_table($conexion,'ventas',"
    CREATE TABLE ventas(
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(150) NOT NULL,
      descripcion TEXT,
      precio DECIMAL(10,2) NOT NULL DEFAULT 0,
      imagen_url VARCHAR(255),
      stock INT NOT NULL DEFAULT 0,
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'ventas','precio',"ADD COLUMN precio DECIMAL(10,2) NOT NULL DEFAULT 0");
  ensure_col($conexion,'ventas','imagen_url',"ADD COLUMN imagen_url VARCHAR(255)");
  ensure_col($conexion,'ventas','stock',"ADD COLUMN stock INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'ventas','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'ventas','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");

  // EQUIPO
  ensure_table($conexion,'equipo',"
    CREATE TABLE equipo(
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(150) NOT NULL,
      rol VARCHAR(150),
      bio TEXT,
      foto_url VARCHAR(255),
      instagram VARCHAR(255),
      orden INT NOT NULL DEFAULT 0,
      activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
  ensure_col($conexion,'equipo','foto_url',"ADD COLUMN foto_url VARCHAR(255)");
  ensure_col($conexion,'equipo','instagram',"ADD COLUMN instagram VARCHAR(255)");
  ensure_col($conexion,'equipo','orden',"ADD COLUMN orden INT NOT NULL DEFAULT 0");
  ensure_col($conexion,'equipo','activo',"ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
}

// ==================== Estado ====================
$data = [];
$msg_cfg = $msg_disc = $msg_fotos = $msg_videos = $msg_ofe = $msg_promo = $msg_ven = $msg_eq = '';

// ==================== Config básica ====================
if ($db_ok) {
  $res  = $conexion->query("SELECT * FROM site_settings WHERE id=1");
  $data = $res && $res->num_rows ? $res->fetch_assoc() : [];
}

// Cloudinary: usar env con alias; si no, valores guardados en DB
$CLD_NAME   = envv(['CLOUDINARY_CLOUD_NAME','CLD_CLOUD_NAME']);
if ($CLD_NAME==='')   $CLD_NAME   = $data['cld_cloud_name']    ?? '';
$CLD_PRESET = envv(['CLOUDINARY_UNSIGNED_PRESET','CLD_UPLOAD_PRESET']);
if ($CLD_PRESET==='') $CLD_PRESET = $data['cld_upload_preset'] ?? '';
$CLD_FOLDER = envv(['CLOUDINARY_FOLDER']) ?: 'scorpions';

if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='config') {
  $color_principal  = trim($_POST['color_principal'] ?? '');
  $color_secundario = trim($_POST['color_secundario'] ?? '');
  $fondo_img        = trim($_POST['fondo_img'] ?? '');
  $logo_img         = trim($_POST['logo_img'] ?? '');
  $texto_banner     = trim($_POST['texto_banner'] ?? '');
  $youtube          = trim($_POST['youtube'] ?? '');
  $instagram        = trim($_POST['instagram'] ?? '');
  $facebook         = trim($_POST['facebook'] ?? '');
  $google_maps      = trim($_POST['google_maps'] ?? '');
  $cld_cloud_name   = trim($_POST['cld_cloud_name'] ?? '');
  $cld_upload_preset= trim($_POST['cld_upload_preset'] ?? '');

  $err = '';
  $stmt = stmt_or_err("
    INSERT INTO site_settings
      (id,color_principal,color_secundario,fondo_img,logo_img,texto_banner,youtube,instagram,facebook,google_maps,cld_cloud_name,cld_upload_preset)
    VALUES (1,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      color_principal=VALUES(color_principal),
      color_secundario=VALUES(color_secundario),
      fondo_img=VALUES(fondo_img),
      logo_img=VALUES(logo_img),
      texto_banner=VALUES(texto_banner),
      youtube=VALUES(youtube),
      instagram=VALUES(instagram),
      facebook=VALUES(facebook),
      google_maps=VALUES(google_maps),
      cld_cloud_name=VALUES(cld_cloud_name),
      cld_upload_preset=VALUES(cld_upload_preset)
  ", $err);

  if ($stmt) {
    $stmt->bind_param(
      'sssssssssss',
      $color_principal,$color_secundario,$fondo_img,$logo_img,$texto_banner,
      $youtube,$instagram,$facebook,$google_maps,$cld_cloud_name,$cld_upload_preset
    );
    $ok = $stmt->execute();
    $msg_cfg = $ok ? '✅ Configuraciones guardadas' : ('❌ Error al guardar: '.$stmt->error);
    $stmt->close();
  } else {
    $msg_cfg = '❌ Error SQL (config): '.$err;
  }

  $res  = $conexion->query("SELECT * FROM site_settings WHERE id=1");
  $data = $res && $res->num_rows ? $res->fetch_assoc() : [];

  // refrescar vars
  $CLD_NAME   = envv(['CLOUDINARY_CLOUD_NAME','CLD_CLOUD_NAME']) ?: ($data['cld_cloud_name'] ?? '');
  $CLD_PRESET = envv(['CLOUDINARY_UNSIGNED_PRESET','CLD_UPLOAD_PRESET']) ?: ($data['cld_upload_preset'] ?? '');
  $CLD_FOLDER = envv(['CLOUDINARY_FOLDER']) ?: 'scorpions';
}

// ==================== Disciplinas ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='disciplinas') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $descripcion=$_POST['descripcion']??'';
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("UPDATE disciplinas SET titulo=?, descripcion=?, imagen_url=?, orden=?, activo=? WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('sssiii',$titulo,$descripcion,$imagen,$orden,$activo,$id); $ok=$stmt->execute(); $msg_disc=$ok?'✅ Disciplina actualizada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_disc='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("INSERT INTO disciplinas (titulo,descripcion,imagen_url,orden,activo) VALUES (?,?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('sssii',$titulo,$descripcion,$imagen,$orden,$activo); $ok=$stmt->execute(); $msg_disc=$ok?'✅ Disciplina guardada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_disc='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_disc'])) {
  @$conexion->query("DELETE FROM disciplinas WHERE id=".(int)$_GET['del_disc']);
  $msg_disc='🗑️ Disciplina eliminada';
}

// ==================== Fotos ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='fotos') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("UPDATE fotos SET titulo=?, imagen_url=?, orden=?, activo=? WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('ssiii',$titulo,$imagen,$orden,$activo,$id); $ok=$stmt->execute(); $msg_fotos=$ok?'✅ Foto actualizada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_fotos='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("INSERT INTO fotos (titulo,imagen_url,orden,activo) VALUES (?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('ssii',$titulo,$imagen,$orden,$activo); $ok=$stmt->execute(); $msg_fotos=$ok?'✅ Foto guardada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_fotos='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_foto'])) {
  @$conexion->query("DELETE FROM fotos WHERE id=".(int)$_GET['del_foto']);
  $msg_fotos='🗑️ Foto eliminada';
}

// ==================== Videos ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='videos') {
  $id     =(int)($_POST['id']??0);
  $titulo = $_POST['titulo']??'';
  $tipo   = $_POST['tipo']??'youtube';
  $video  = up_or_url2('video_file','video_url', $_POST['video_url']??'');
  $cover  = up_or_url('cover_url', $_POST['cover_url']??'');
  $orden  = (int)($_POST['orden']??0);
  $activo = isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("UPDATE videos SET titulo=?, video_url=?, tipo=?, cover_url=?, orden=?, activo=? WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('ssssiii',$titulo,$video,$tipo,$cover,$orden,$activo,$id); $ok=$stmt->execute(); $msg_videos=$ok?'✅ Video actualizado':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_videos='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("INSERT INTO videos (titulo,video_url,tipo,cover_url,orden,activo) VALUES (?,?,?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('ssssii',$titulo,$video,$tipo,$cover,$orden,$activo); $ok=$stmt->execute(); $msg_videos=$ok?'✅ Video guardado':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_videos='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_video'])) {
  @$conexion->query("DELETE FROM videos WHERE id=".(int)$_GET['del_video']);
  $msg_videos='🗑️ Video eliminado';
}

// ==================== Ofertas ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='ofertas') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??'';
  $descripcion=$_POST['descripcion']??'';
  $precio=(float)($_POST['precio']??0);
  $desde=$_POST['vigente_desde']??null;
  $hasta=$_POST['vigente_hasta']??null;
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0);
  $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("
      UPDATE ofertas
         SET titulo=?, descripcion=?, precio=?, vigente_desde=?, vigente_hasta=?, imagen_url=?, orden=?, activo=?
       WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('ssdsssiii', $titulo,$descripcion,$precio,$desde,$hasta,$imagen,$orden,$activo,$id); $ok=$stmt->execute(); $msg_ofe=$ok?'✅ Oferta actualizada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_ofe='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("
      INSERT INTO ofertas (titulo,descripcion,precio,vigente_desde,vigente_hasta,imagen_url,orden,activo)
      VALUES (?,?,?,?,?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('ssdsssii', $titulo,$descripcion,$precio,$desde,$hasta,$imagen,$orden,$activo); $ok=$stmt->execute(); $msg_ofe=$ok?'✅ Oferta guardada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_ofe='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_ofe'])) {
  @$conexion->query("DELETE FROM ofertas WHERE id=".(int)$_GET['del_ofe']);
  $msg_ofe='🗑️ Oferta eliminada';
}

// ==================== Promociones ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='promociones') {
  $id=(int)($_POST['id']??0);
  $titulo=$_POST['titulo']??''; $descripcion=$_POST['descripcion']??'';
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("UPDATE promociones SET titulo=?, descripcion=?, imagen_url=?, orden=?, activo=? WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('sssiii',$titulo,$descripcion,$imagen,$orden,$activo,$id); $ok=$stmt->execute(); $msg_promo=$ok?'✅ Promoción actualizada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_promo='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("INSERT INTO promociones (titulo,descripcion,imagen_url,orden,activo) VALUES (?,?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('sssii',$titulo,$descripcion,$imagen,$orden,$activo); $ok=$stmt->execute(); $msg_promo=$ok?'✅ Promoción guardada':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_promo='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_promo'])) {
  @$conexion->query("DELETE FROM promociones WHERE id=".(int)$_GET['del_promo']);
  $msg_promo='🗑️ Promoción eliminada';
}

// ==================== Ventas ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='ventas') {
  $id=(int)($_POST['id']??0);
  $nombre=$_POST['nombre']??''; $descripcion=$_POST['descripcion']??'';
  $precio=(float)($_POST['precio']??0); $stock=(int)($_POST['stock']??0);
  $imagen=up_or_url('imagen_url', $_POST['imagen_url']??'');
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("UPDATE ventas SET nombre=?, descripcion=?, precio=?, imagen_url=?, stock=?, orden=?, activo=? WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('ssdsiiii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo,$id); $ok=$stmt->execute(); $msg_ven=$ok?'✅ Producto actualizado':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_ven='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("INSERT INTO ventas (nombre,descripcion,precio,imagen_url,stock,orden,activo) VALUES (?,?,?,?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('ssdsiii', $nombre,$descripcion,$precio,$imagen,$stock,$orden,$activo); $ok=$stmt->execute(); $msg_ven=$ok?'✅ Producto guardado':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_ven='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_ven'])) {
  @$conexion->query("DELETE FROM ventas WHERE id=".(int)$_GET['del_ven']);
  $msg_ven='🗑️ Producto eliminado';
}

// ==================== Equipo ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form']??'')==='equipo') {
  $id=(int)($_POST['id']??0);
  $nombre=$_POST['nombre']??''; $rol=$_POST['rol']??''; $bio=$_POST['bio']??'';
  $foto=up_or_url('foto_url', $_POST['foto_url']??''); $insta=$_POST['instagram']??'';
  $orden=(int)($_POST['orden']??0); $activo=isset($_POST['activo'])?1:0;

  if ($id>0){
    $err=''; $stmt=stmt_or_err("UPDATE equipo SET nombre=?, rol=?, bio=?, foto_url=?, instagram=?, orden=?, activo=? WHERE id=?", $err);
    if ($stmt){ $stmt->bind_param('sssssiii',$nombre,$rol,$bio,$foto,$insta,$orden,$activo,$id); $ok=$stmt->execute(); $msg_eq=$ok?'✅ Miembro actualizado':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_eq='❌ Error SQL: '.$err; }
  } else {
    $err=''; $stmt=stmt_or_err("INSERT INTO equipo (nombre,rol,bio,foto_url,instagram,orden,activo) VALUES (?,?,?,?,?,?,?)", $err);
    if ($stmt){ $stmt->bind_param('sssssii',$nombre,$rol,$bio,$foto,$insta,$orden,$activo); $ok=$stmt->execute(); $msg_eq=$ok?'✅ Miembro guardado':'❌ Error: '.$stmt->error; $stmt->close(); }
    else { $msg_eq='❌ Error SQL: '.$err; }
  }
}
if ($db_ok && isset($_GET['del_eq'])) {
  @$conexion->query("DELETE FROM equipo WHERE id=".(int)$_GET['del_eq']);
  $msg_eq='🗑️ Miembro eliminado';
}

// ==================== Listados (últimos 15) ====================
$disciplinas=$fotos=$videos=$ofertas=$promos=$ventas=$equipo=[];
if ($db_ok) {
  try { $r=$conexion->query("SELECT * FROM disciplinas  ORDER BY ".order_by($conexion,'disciplinas')."  LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $disciplinas[]=$x; } catch(Throwable $e) {}
  try { $r=$conexion->query("SELECT * FROM fotos        ORDER BY ".order_by($conexion,'fotos')."        LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $fotos[]=$x; } catch(Throwable $e) {}
  try { $r=$conexion->query("SELECT * FROM videos       ORDER BY ".order_by($conexion,'videos')."       LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $videos[]=$x; } catch(Throwable $e) {}
  try { $r=$conexion->query("SELECT * FROM ofertas      ORDER BY ".order_by($conexion,'ofertas')."      LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $ofertas[]=$x; } catch(Throwable $e) {}
  try { $r=$conexion->query("SELECT * FROM promociones  ORDER BY ".order_by($conexion,'promociones')."  LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $promos[]=$x; } catch(Throwable $e) {}
  try { $r=$conexion->query("SELECT * FROM ventas       ORDER BY ".order_by($conexion,'ventas')."       LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $ventas[]=$x; } catch(Throwable $e) {}
  try { $r=$conexion->query("SELECT * FROM equipo       ORDER BY ".order_by($conexion,'equipo')."       LIMIT 15"); if($r) while($x=$r->fetch_assoc()) $equipo[]=$x; } catch(Throwable $e) {}
}

// Para mostrar estado de Cloudinary
$cld_ok = ($CLD_NAME !== '' && $CLD_PRESET !== '');
function mask($s){ if($s==='') return ''; $len=strlen($s); return substr($s,0,2).str_repeat('•',max(0,$len-4)).substr($s,-2); }
?>
<!doctype html>
<html lang="es">
<head>
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
.btn[disabled]{opacity:.6;cursor:not-allowed}
.msg{margin:12px 0;padding:10px;border-radius:8px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.35)}
.warn{margin:12px 0;padding:10px;border-radius:8px;background:rgba(255,193,7,.15);border:1px solid rgba(255,193,7,.35)}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid rgba(255,255,255,.12);vertical-align:top}
img.thumb{height:52px;border-radius:8px}
.badge{font-size:.85rem;opacity:.85}
.inline{display:flex;gap:8px;align-items:center}
.small{font-size:.9rem;opacity:.85}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>Configuraciones del sitio</h1>
    <div><a class="link" href="../" target="_blank">Ver sitio</a></div>
  </div>

  <?php if(!$db_ok): ?>
    <div class="warn">⚠️ Sin conexión a la base de datos. Podés navegar el panel, pero no se guardará hasta que la DB responda.</div>
  <?php endif; ?>

  <div class="<?= $cld_ok ? 'msg' : 'warn' ?>">
    <strong>Estado Cloudinary:</strong>
    <?= $cld_ok ? 'Listo para subir.' : 'Faltan datos de Cloudinary.' ?>
    <div class="small">
      Cloud: <code><?= h(mask($CLD_NAME)) ?></code> · Preset: <code><?= h(mask($CLD_PRESET)) ?></code> · Carpeta: <code><?= h($CLD_FOLDER) ?></code>
    </div>
  </div>

  <?php if($msg_cfg): ?><div class="msg"><?=h($msg_cfg)?></div><?php endif; ?>

  <!-- ==================== CONFIG BÁSICA ==================== -->
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

    <h2 style="margin:20px 0 6px">Cloudinary (para subir a la nube)</h2>
    <div class="small" style="margin-bottom:10px">
      Cargá estos datos o definí las variables <code>CLOUDINARY_CLOUD_NAME</code> y
      <code>CLOUDINARY_UNSIGNED_PRESET</code> (opcional <code>CLOUDINARY_FOLDER</code>) en Render.
    </div>
    <div class="grid">
      <div><label>Cloud name</label><input type="text" name="cld_cloud_name" value="<?=v('cld_cloud_name')?>"></div>
      <div><label>Upload preset (unsigned)</label><input type="text" name="cld_upload_preset" value="<?=v('cld_upload_preset')?>"></div>
    </div>

    <div style="margin-top:16px">
      <button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar configuraciones</button>
    </div>
  </form>

  <!-- ==================== DISCIPLINAS ==================== -->
  <?php if($msg_disc): ?><div class="msg"><?=h($msg_disc)?></div><?php endif; ?>
  <div class="card">
    <h2>Disciplinas — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="disciplinas"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: usar URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="disc-img-url" placeholder="https://...">
            <button type="button" class="btn" id="btn-disc-img">Subir a la nube</button>
          </div>
          <img id="disc-img-prev" class="thumb" style="margin-top:8px;display:none">
        </div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar disciplina</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($disciplinas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_disc=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$disciplinas): ?><tr><td colspan="6">Sin disciplinas</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== FOTOS ==================== -->
  <?php if($msg_fotos): ?><div class="msg"><?=h($msg_fotos)?></div><?php endif; ?>
  <div class="card">
    <h2>Galería de Fotos — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="fotos"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo"></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="foto-url" placeholder="https://...">
            <button type="button" class="btn" id="cld-foto-btn">Subir a la nube</button>
          </div>
          <img id="foto-prev" class="thumb" style="margin-top:8px;display:none">
        </div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar foto</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 fotos</h3>
    <table>
      <thead><tr><th>ID</th><th>Preview</th><th>Título</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($fotos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_foto=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$fotos): ?><tr><td colspan="6">Sin fotos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== VIDEOS ==================== -->
  <?php if($msg_videos): ?><div class="msg"><?=h($msg_videos)?></div><?php endif; ?>
  <div class="card">
    <h2>Videos cortos / Reels — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="videos"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo"></div>

        <div>
          <label>Tipo</label>
          <select name="tipo">
            <option value="youtube">YouTube</option>
            <option value="instagram">Instagram</option>
            <option value="mp4">MP4 (archivo o link)</option>
          </select>
        </div>

        <div>
          <label>Video (subir)</label>
          <input type="file" name="video_file" id="video-file" accept="video/*">
          <div class="badge">En Render free, los archivos locales no persisten.</div>
        </div>

        <div>
          <label>Video URL</label>
          <div class="inline">
            <input type="url" name="video_url" id="video-url" placeholder="https://youtu.be/ID · https://www.instagram.com/p/... · https://.../video.mp4">
            <button type="button" class="btn" id="cld-video-btn">Subir a la nube</button>
          </div>
          <div id="video-hint" class="badge"></div>
        </div>

        <div>
          <label>Cover (subir)</label>
          <input type="file" name="cover_url" accept="image/*">
        </div>
        <div>
          <label>Cover (URL)</label>
          <div class="inline">
            <input type="url" name="cover_url" id="cover-url" placeholder="https://...">
            <button type="button" class="btn" id="cld-cover-btn">Subir cover</button>
          </div>
          <img id="cover-prev" class="thumb" style="margin-top:8px;display:none">
        </div>

        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar video</button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 videos</h3>
    <table>
      <thead><tr><th>ID</th><th>Título</th><th>Tipo</th><th>URL</th><th>Cover</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($videos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title'], '(sin título)')) ?></td>
          <td><?= h(arr_get($r,'tipo','')) ?></td>
          <td><?= !empty($r['video_url']) ? '<a class="link" href="'.h($r['video_url']).'" target="_blank">Abrir</a>' : '' ?></td>
          <td><?= !empty($r['cover_url'])?'<img class="thumb" src="'.h($r['cover_url']).'">':'' ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_video=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$videos): ?><tr><td colspan="8">Sin videos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== OFERTAS ==================== -->
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
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="ofe-img-url" placeholder="https://...">
            <button type="button" class="btn" id="btn-ofe-img">Subir a la nube</button>
          </div>
          <img id="ofe-img-prev" class="thumb" style="margin-top:8px;display:none">
        </div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar oferta</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 ofertas</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>$</th><th>Desde</th><th>Hasta</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($ofertas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= number_format((float)$r['precio'],2,',','.') ?></td>
          <td><?= h(arr_get($r,'vigente_desde','')) ?></td>
          <td><?= h(arr_get($r,'vigente_hasta','')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_ofe=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$ofertas): ?><tr><td colspan="9">Sin ofertas</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== PROMOCIONES ==================== -->
  <?php if($msg_promo): ?><div class="msg"><?=h($msg_promo)?></div><?php endif; ?>
  <div class="card">
    <h2>Promociones — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="promociones"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="promo-img-url" placeholder="https://...">
            <button type="button" class="btn" id="btn-promo-img">Subir a la nube</button>
          </div>
          <img id="promo-img-prev" class="thumb" style="margin-top:8px;display:none">
        </div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar promoción</button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 promociones</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($promos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_promo=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$promos): ?><tr><td colspan="6">Sin promociones</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== VENTAS ==================== -->
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
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="ven-img-url" placeholder="https://...">
            <button type="button" class="btn" id="btn-ven-img">Subir a la nube</button>
          </div>
          <img id="ven-img-prev" class="thumb" style="margin-top:8px;display:none">
        </div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar producto</button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 productos</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Nombre</th><th>$</th><th>Stock</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($ventas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['nombre','titulo','name'], '(sin nombre)')) ?></td>
          <td><?= number_format((float)$r['precio'],2,',','.') ?></td>
          <td><?= (int)$r['stock'] ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_ven=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$ventas): ?><tr><td colspan="8">Sin productos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== EQUIPO ==================== -->
  <?php if($msg_eq): ?><div class="msg"><?=h($msg_eq)?></div><?php endif; ?>
  <div class="card">
    <h2>Equipo — alta rápida</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="equipo"><input type="hidden" name="id" value="0">
      <div class="grid">
        <div><label>Nombre</label><input type="text" name="nombre" required></div>
        <div><label>Rol</label><input type="text" name="rol"></div>
        <div class="grid-1"><label>Bio</label><textarea name="bio"></textarea></div>
        <div><label>Foto (subir)</label><input type="file" name="foto_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Foto (URL)</label>
          <div class="inline">
            <input type="text" name="foto_url" id="eq-foto-url" placeholder="https://...">
            <button type="button" class="btn" id="btn-eq-foto">Subir a la nube</button>
          </div>
          <img id="eq-foto-prev" class="thumb" style="margin-top:8px;display:none">
        </div>
        <div><label>Instagram (URL)</label><input type="url" name="instagram" placeholder="https://instagram.com/..."></div>
        <div><label>Orden</label><input type="number" name="orden" value="0"></div>
        <div><label><input type="checkbox" name="activo" checked> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>>Guardar miembro</button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 miembros</h3>
    <table>
      <thead><tr><th>ID</th><th>Foto</th><th>Nombre</th><th>Rol</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($equipo as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['foto_url'])?'<img class="thumb" src="'.h($r['foto_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['nombre','titulo','name'], '(sin nombre)')) ?></td>
          <td><?= h(arr_get($r,'rol','')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td><a class="link" href="?del_eq=<?=$r['id']?>" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
        </tr>
      <?php endforeach; if(!$equipo): ?><tr><td colspan="7">Sin miembros</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Cloudinary widget -->
<script src="https://upload-widget.cloudinary.com/v2.0/global/all.js" type="text/javascript"></script>
<script>
(function(){
  const CLD_NAME   = <?= json_encode($CLD_NAME) ?>;
  const CLD_PRESET = <?= json_encode($CLD_PRESET) ?>;
  const CLD_FOLDER = <?= json_encode($CLD_FOLDER) ?>;

  function canUseCloudinary(){ return (typeof cloudinary !== 'undefined') && !!CLD_NAME && !!CLD_PRESET; }

  function attachUploader(btnId, inputId, type, prevId){
    const btn = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    const prev = prevId ? document.getElementById(prevId) : null;
    if (!btn || !input) return;

    if (!canUseCloudinary()){
      btn.disabled = true;
      btn.title = "Cloudinary no configurado en el servidor (Cloud/Preset vacíos o librería bloqueada)";
      console.log('[Cloudinary] no habilitado', {cloudName: CLD_NAME, preset: CLD_PRESET, lib: typeof cloudinary});
      return;
    }

    const w = cloudinary.createUploadWidget({
      cloudName: CLD_NAME,
      uploadPreset: CLD_PRESET,
      folder: CLD_FOLDER,
      sources: ['local','url','camera'],
      multiple: false,
      maxFileSize: (type==='video' ? 100 : 15) * 1024 * 1024,
      clientAllowedFormats: (type==='video' ? ['mp4','mov','webm'] : ['jpg','jpeg','png','webp']),
      resourceType: type
    }, (error, result) => {
      if (!error && result && result.event === "success") {
        input.value = result.info.secure_url;
        if (prev){ prev.src = result.info.secure_url; prev.style.display = 'inline-block'; }

        if (type === 'video') {
          const fileField = document.getElementById('video-file');
          if (fileField) { fileField.value = ''; fileField.disabled = true; }
          const hint = document.getElementById('video-hint');
          if (hint) hint.textContent = 'Subido ('+Math.round(result.info.bytes/1024/1024)+' MB)';
        }
      }
    });

    btn.addEventListener('click', (e) => { e.preventDefault(); w.open(); });
  }

  // Fotos
  attachUploader('cld-foto-btn','foto-url','image','foto-prev');

  // Disciplinas / Ofertas / Promos / Ventas / Equipo (imágenes)
  attachUploader('btn-disc-img','disc-img-url','image','disc-img-prev');
  attachUploader('btn-ofe-img','ofe-img-url','image','ofe-img-prev');
  attachUploader('btn-promo-img','promo-img-url','image','promo-img-prev');
  attachUploader('btn-ven-img','ven-img-url','image','ven-img-prev');
  attachUploader('btn-eq-foto','eq-foto-url','image','eq-foto-prev');

  // Videos: archivo remoto y cover
  attachUploader('cld-video-btn','video-url','video',null);
  attachUploader('cld-cover-btn','cover-url','image','cover-prev');

  // Rehabilitar file si la URL queda vacía
  (function () {
    const url = document.getElementById('video-url');
    const file = document.getElementById('video-file');
    if (!url || !file) return;
    function toggle(){ file.disabled = url.value.trim() !== ''; }
    url.addEventListener('input', toggle);
    url.addEventListener('change', toggle);
    toggle();
  })();

  // Bloquear submit si intentan mandar un archivo de video grande
  (function(){
    const form = Array.from(document.querySelectorAll('form'))
      .find(f => f.querySelector('input[name="__form"][value="videos"]'));
    const file = document.getElementById('video-file');
    if (!form || !file) return;
    form.addEventListener('submit', (e) => {
      const f = file.files && file.files[0];
      if (f && f.size > 20 * 1024 * 1024) {
        e.preventDefault();
        alert('El video local supera 20MB. Usá “Subir a la nube” y guardá con la URL.');
      }
    });
  })();
})();
</script>
</body>
</html>
