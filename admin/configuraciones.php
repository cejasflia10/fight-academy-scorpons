<?php
// admin/configuraciones.php — Panel Único (SIN LOGIN)
ini_set('display_errors', 1);
// Evitar spam de deprecated/notices en el panel
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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
  function h($s){
    if ($s === null) $s = '';
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
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

// Subir ARCHIVO (mismo nombre para file y texto) — **prioriza URL**
if (!function_exists('up_or_url')) {
  function up_or_url($inputName, $fallback=''){
    $url = trim($_POST[$inputName] ?? '');
    if ($url !== '') return $url; // preferir URL (Cloudinary)

    if (!empty($_FILES[$inputName]['name'])) {
      $dir = __DIR__ . '/../uploads';
      if (!is_dir($dir)) @mkdir($dir, 0775, true);
      $fn = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES[$inputName]['name']);
      $dest = $dir.'/'.$fn;
      if (@move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
        return '/uploads/'.$fn; // efímero en Render
      }
    }
    return $fallback;
  }
}

// Subir ARCHIVO (campo archivo distinto de campo URL) – ya prioriza URL
if (!function_exists('up_or_url2')) {
  function up_or_url2($fileField, $urlField, $fallback=''){
    $url = trim($_POST[$urlField] ?? '');
    if ($url !== '') return $url;
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
// fetch genérico por id
if (!function_exists('fetch_by_id')) {
  function fetch_by_id($tabla, $id){
    global $conexion;
    $stmt = @$conexion->prepare("SELECT * FROM `$tabla` WHERE id=? LIMIT 1");
    if(!$stmt) return null;
    $stmt->bind_param('i',$id);
    if (!$stmt->execute()) { $stmt->close(); return null; }
    $res = $stmt->get_result();
    $row = $res? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
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
  ensure_col($conexion,'disciplinas','descripcion',"ADD COLUMN descripcion TEXT NULL AFTER titulo");
  ensure_col($conexion,'disciplinas','imagen_url',"ADD COLUMN imagen_url VARCHAR(255) NULL AFTER descripcion");
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
  ensure_col($conexion,'fotos','imagen_url',"ADD COLUMN imagen_url VARCHAR(255) NULL AFTER titulo");
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
  ensure_col($conexion,'videos','video_url',"ADD COLUMN video_url VARCHAR(255) NULL AFTER titulo");
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

// ==================== CRUD: guardar ====================
if ($db_ok && $_SERVER['REQUEST_METHOD']==='POST') {
  $form = $_POST['__form'] ?? '';
  if ($form === 'config') {
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
    $CLD_NAME   = envv(['CLOUDINARY_CLOUD_NAME','CLD_CLOUD_NAME']) ?: ($data['cld_cloud_name'] ?? '');
    $CLD_PRESET = envv(['CLOUDINARY_UNSIGNED_PRESET','CLD_UPLOAD_PRESET']) ?: ($data['cld_upload_preset'] ?? '');
    $CLD_FOLDER = envv(['CLOUDINARY_FOLDER']) ?: 'scorpions';
  }

  // Disciplinas
  if ($form === 'disciplinas') {
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

  // Fotos
  if ($form === 'fotos') {
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

  // Videos
  if ($form === 'videos') {
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

  // Ofertas
  if ($form === 'ofertas') {
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

  // Promociones
  if ($form === 'promociones') {
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

  // Ventas
  if ($form === 'ventas') {
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

  // Equipo
  if ($form === 'equipo') {
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
}

// ==================== BORRAR (GET) ====================
if ($db_ok) {
  if (isset($_GET['del_disc'])) { @$conexion->query("DELETE FROM disciplinas  WHERE id=".(int)$_GET['del_disc']);  $msg_disc='🗑️ Disciplina eliminada'; }
  if (isset($_GET['del_foto'])) { @$conexion->query("DELETE FROM fotos        WHERE id=".(int)$_GET['del_foto']);  $msg_fotos='🗑️ Foto eliminada'; }
  if (isset($_GET['del_video'])){ @$conexion->query("DELETE FROM videos       WHERE id=".(int)$_GET['del_video']); $msg_videos='🗑️ Video eliminado'; }
  if (isset($_GET['del_ofe']))  { @$conexion->query("DELETE FROM ofertas      WHERE id=".(int)$_GET['del_ofe']);   $msg_ofe='🗑️ Oferta eliminada'; }
  if (isset($_GET['del_promo'])){ @$conexion->query("DELETE FROM promociones  WHERE id=".(int)$_GET['del_promo']); $msg_promo='🗑️ Promoción eliminada'; }
  if (isset($_GET['del_ven']))  { @$conexion->query("DELETE FROM ventas       WHERE id=".(int)$_GET['del_ven']);   $msg_ven='🗑️ Producto eliminado'; }
  if (isset($_GET['del_eq']))   { @$conexion->query("DELETE FROM equipo       WHERE id=".(int)$_GET['del_eq']);    $msg_eq='🗑️ Miembro eliminado'; }
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

// ==================== EDITAR (precarga) ====================
$ed_disc = isset($_GET['edit_disc'])  ? fetch_by_id('disciplinas',(int)$_GET['edit_disc']) : null;
$ed_foto = isset($_GET['edit_foto'])  ? fetch_by_id('fotos',(int)$_GET['edit_foto'])       : null;
$ed_video= isset($_GET['edit_video']) ? fetch_by_id('videos',(int)$_GET['edit_video'])     : null;
$ed_ofe  = isset($_GET['edit_ofe'])   ? fetch_by_id('ofertas',(int)$_GET['edit_ofe'])      : null;
$ed_promo= isset($_GET['edit_promo']) ? fetch_by_id('promociones',(int)$_GET['edit_promo']): null;
$ed_ven  = isset($_GET['edit_ven'])   ? fetch_by_id('ventas',(int)$_GET['edit_ven'])       : null;
$ed_eq   = isset($_GET['edit_eq'])    ? fetch_by_id('equipo',(int)$_GET['edit_eq'])        : null;

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
.actions a{margin-right:8px}
.editing{background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.35)}
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
  <form method="post" class="card" id="sec-config">
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
  <div class="card <?= $ed_disc?'editing':'' ?>" id="sec-disc">
    <h2><?= $ed_disc ? 'Editar disciplina #'.(int)$ed_disc['id'] : 'Disciplinas — alta rápida' ?></h2>
    <?php if($ed_disc): ?><div class="small">Estás editando. <a class="link" href="?#sec-disc">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="disciplinas"><input type="hidden" name="id" value="<?= (int)($ed_disc['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required value="<?= h(arr_get($ed_disc,'titulo','')) ?>"></div>
        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_disc,'orden',0) ?>"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"><?= h(arr_get($ed_disc,'descripcion','')) ?></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: usar URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="disc-img-url" placeholder="https://..." value="<?= h(arr_get($ed_disc,'imagen_url','')) ?>">
            <button type="button" class="btn" id="btn-disc-img">Subir a la nube</button>
          </div>
          <img id="disc-img-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_disc,'imagen_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_disc,'imagen_url','')) ?>">
        </div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_disc,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_disc?'Guardar cambios':'Guardar disciplina' ?></button>
        <?php if($ed_disc): ?> <a class="link" href="?#sec-disc">Cancelar</a><?php endif; ?>
      </div>
    </form>

    <h3 style="margin-top:16px">Últimas 15</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($disciplinas as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td class="actions">
            <a class="link" href="?edit_disc=<?=$r['id']?>#sec-disc">Editar</a>
            <a class="link" href="?del_disc=<?=$r['id']?>#sec-disc" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; if(!$disciplinas): ?><tr><td colspan="6">Sin disciplinas</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== FOTOS ==================== -->
  <?php if($msg_fotos): ?><div class="msg"><?=h($msg_fotos)?></div><?php endif; ?>
  <div class="card <?= $ed_foto?'editing':'' ?>" id="sec-fotos">
    <h2><?= $ed_foto ? 'Editar foto #'.(int)$ed_foto['id'] : 'Galería de Fotos — alta rápida' ?></h2>
    <?php if($ed_foto): ?><div class="small">Estás editando. <a class="link" href="?#sec-fotos">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="fotos"><input type="hidden" name="id" value="<?= (int)($ed_foto['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" value="<?= h(arr_get($ed_foto,'titulo','')) ?>"></div>
        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_foto,'orden',0) ?>"></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="foto-url" placeholder="https://..." value="<?= h(arr_get($ed_foto,'imagen_url','')) ?>">
            <button type="button" class="btn" id="cld-foto-btn">Subir a la nube</button>
          </div>
          <img id="foto-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_foto,'imagen_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_foto,'imagen_url','')) ?>">
        </div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_foto,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_foto?'Guardar cambios':'Guardar foto' ?></button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 fotos</h3>
    <table>
      <thead><tr><th>ID</th><th>Preview</th><th>Título</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($fotos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td class="actions">
            <a class="link" href="?edit_foto=<?=$r['id']?>#sec-fotos">Editar</a>
            <a class="link" href="?del_foto=<?=$r['id']?>#sec-fotos" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; if(!$fotos): ?><tr><td colspan="6">Sin fotos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== VIDEOS ==================== -->
  <?php if($msg_videos): ?><div class="msg"><?=h($msg_videos)?></div><?php endif; ?>
  <div class="card <?= $ed_video?'editing':'' ?>" id="sec-videos">
    <h2><?= $ed_video ? 'Editar video #'.(int)$ed_video['id'] : 'Videos cortos / Reels — alta rápida' ?></h2>
    <?php if($ed_video): ?><div class="small">Estás editando. <a class="link" href="?#sec-videos">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="videos"><input type="hidden" name="id" value="<?= (int)($ed_video['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" value="<?= h(arr_get($ed_video,'titulo','')) ?>"></div>

        <div>
          <label>Tipo</label>
          <select name="tipo">
            <?php $tipo = arr_get($ed_video,'tipo','youtube'); ?>
            <option value="youtube"   <?= $tipo==='youtube'?'selected':'' ?>>YouTube</option>
            <option value="instagram" <?= $tipo==='instagram'?'selected':'' ?>>Instagram</option>
            <option value="mp4"       <?= $tipo==='mp4'?'selected':'' ?>>MP4 (archivo o link)</option>
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
            <input type="url" name="video_url" id="video-url" placeholder="https://..." value="<?= h(arr_get($ed_video,'video_url','')) ?>">
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
            <input type="url" name="cover_url" id="cover-url" placeholder="https://..." value="<?= h(arr_get($ed_video,'cover_url','')) ?>">
            <button type="button" class="btn" id="cld-cover-btn">Subir cover</button>
          </div>
          <img id="cover-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_video,'cover_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_video,'cover_url','')) ?>">
        </div>

        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_video,'orden',0) ?>"></div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_video,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_video?'Guardar cambios':'Guardar video' ?></button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 videos</h3>
    <table>
      <thead><tr><th>ID</th><th>Título</th><th>Tipo</th><th>URL</th><th>Cover</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
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
          <td class="actions">
            <a class="link" href="?edit_video=<?=$r['id']?>#sec-videos">Editar</a>
            <a class="link" href="?del_video=<?=$r['id']?>#sec-videos" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; if(!$videos): ?><tr><td colspan="8">Sin videos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== OFERTAS ==================== -->
  <?php if($msg_ofe): ?><div class="msg"><?=h($msg_ofe)?></div><?php endif; ?>
  <div class="card <?= $ed_ofe?'editing':'' ?>" id="sec-ofe">
    <h2><?= $ed_ofe ? 'Editar oferta #'.(int)$ed_ofe['id'] : 'Ofertas — alta rápida' ?></h2>
    <?php if($ed_ofe): ?><div class="small">Estás editando. <a class="link" href="?#sec-ofe">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="ofertas"><input type="hidden" name="id" value="<?= (int)($ed_ofe['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required value="<?= h(arr_get($ed_ofe,'titulo','')) ?>"></div>
        <div><label>Precio</label><input type="number" step="0.01" name="precio" value="<?= h(arr_get($ed_ofe,'precio','0')) ?>"></div>
        <div><label>Vigente desde</label><input type="date" name="vigente_desde" value="<?= h(arr_get($ed_ofe,'vigente_desde','')) ?>"></div>
        <div><label>Vigente hasta</label><input type="date" name="vigente_hasta" value="<?= h(arr_get($ed_ofe,'vigente_hasta','')) ?>"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"><?= h(arr_get($ed_ofe,'descripcion','')) ?></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="ofe-img-url" placeholder="https://..." value="<?= h(arr_get($ed_ofe,'imagen_url','')) ?>">
            <button type="button" class="btn" id="btn-ofe-img">Subir a la nube</button>
          </div>
          <img id="ofe-img-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_ofe,'imagen_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_ofe,'imagen_url','')) ?>">
        </div>
        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_ofe,'orden',0) ?>"></div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_ofe,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_ofe?'Guardar cambios':'Guardar oferta' ?></button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 ofertas</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>$</th><th>Desde</th><th>Hasta</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
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
          <td class="actions">
            <a class="link" href="?edit_ofe=<?=$r['id']?>#sec-ofe">Editar</a>
            <a class="link" href="?del_ofe=<?=$r['id']?>#sec-ofe" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; if(!$ofertas): ?><tr><td colspan="9">Sin ofertas</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== PROMOCIONES ==================== -->
  <?php if($msg_promo): ?><div class="msg"><?=h($msg_promo)?></div><?php endif; ?>
  <div class="card <?= $ed_promo?'editing':'' ?>" id="sec-promo">
    <h2><?= $ed_promo ? 'Editar promoción #'.(int)$ed_promo['id'] : 'Promociones — alta rápida' ?></h2>
    <?php if($ed_promo): ?><div class="small">Estás editando. <a class="link" href="?#sec-promo">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="promociones"><input type="hidden" name="id" value="<?= (int)($ed_promo['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Título</label><input type="text" name="titulo" required value="<?= h(arr_get($ed_promo,'titulo','')) ?>"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"><?= h(arr_get($ed_promo,'descripcion','')) ?></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="promo-img-url" placeholder="https://..." value="<?= h(arr_get($ed_promo,'imagen_url','')) ?>">
            <button type="button" class="btn" id="btn-promo-img">Subir a la nube</button>
          </div>
          <img id="promo-img-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_promo,'imagen_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_promo,'imagen_url','')) ?>">
        </div>
        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_promo,'orden',0) ?>"></div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_promo,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_promo?'Guardar cambios':'Guardar promoción' ?></button></div>
    </form>

    <h3 style="margin-top:16px">Últimas 15 promociones</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Título</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($promos as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['imagen_url'])?'<img class="thumb" src="'.h($r['imagen_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['titulo','nombre','title','descripcion','slug'], '(sin título)')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td class="actions">
            <a class="link" href="?edit_promo=<?=$r['id']?>#sec-promo">Editar</a>
            <a class="link" href="?del_promo=<?=$r['id']?>#sec-promo" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; if(!$promos): ?><tr><td colspan="6">Sin promociones</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== VENTAS ==================== -->
  <?php if($msg_ven): ?><div class="msg"><?=h($msg_ven)?></div><?php endif; ?>
  <div class="card <?= $ed_ven?'editing':'' ?>" id="sec-ven">
    <h2><?= $ed_ven ? 'Editar producto #'.(int)$ed_ven['id'] : 'Ventas (Productos) — alta rápida' ?></h2>
    <?php if($ed_ven): ?><div class="small">Estás editando. <a class="link" href="?#sec-ven">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="ventas"><input type="hidden" name="id" value="<?= (int)($ed_ven['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Nombre</label><input type="text" name="nombre" required value="<?= h(arr_get($ed_ven,'nombre','')) ?>"></div>
        <div><label>Precio</label><input type="number" step="0.01" name="precio" value="<?= h(arr_get($ed_ven,'precio','0')) ?>"></div>
        <div><label>Stock</label><input type="number" name="stock" value="<?= (int)arr_get($ed_ven,'stock',0) ?>"></div>
        <div class="grid-1"><label>Descripción</label><textarea name="descripcion"><?= h(arr_get($ed_ven,'descripcion','')) ?></textarea></div>
        <div><label>Imagen (subir)</label><input type="file" name="imagen_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Imagen (URL)</label>
          <div class="inline">
            <input type="text" name="imagen_url" id="ven-img-url" placeholder="https://..." value="<?= h(arr_get($ed_ven,'imagen_url','')) ?>">
            <button type="button" class="btn" id="btn-ven-img">Subir a la nube</button>
          </div>
          <img id="ven-img-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_ven,'imagen_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_ven,'imagen_url','')) ?>">
        </div>
        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_ven,'orden',0) ?>"></div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_ven,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_ven?'Guardar cambios':'Guardar producto' ?></button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 productos</h3>
    <table>
      <thead><tr><th>ID</th><th>Img</th><th>Nombre</th><th>$</th><th>Stock</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
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
          <td class="actions">
            <a class="link" href="?edit_ven=<?=$r['id']?>#sec-ven">Editar</a>
            <a class="link" href="?del_ven=<?=$r['id']?>#sec-ven" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; if(!$ventas): ?><tr><td colspan="8">Sin productos</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ==================== EQUIPO ==================== -->
  <?php if($msg_eq): ?><div class="msg"><?=h($msg_eq)?></div><?php endif; ?>
  <div class="card <?= $ed_eq?'editing':'' ?>" id="sec-eq">
    <h2><?= $ed_eq ? 'Editar miembro #'.(int)$ed_eq['id'] : 'Equipo — alta rápida' ?></h2>
    <?php if($ed_eq): ?><div class="small">Estás editando. <a class="link" href="?#sec-eq">Cancelar edición</a></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="__form" value="equipo"><input type="hidden" name="id" value="<?= (int)($ed_eq['id'] ?? 0) ?>">
      <div class="grid">
        <div><label>Nombre</label><input type="text" name="nombre" required value="<?= h(arr_get($ed_eq,'nombre','')) ?>"></div>
        <div><label>Rol</label><input type="text" name="rol" value="<?= h(arr_get($ed_eq,'rol','')) ?>"></div>
        <div class="grid-1"><label>Bio</label><textarea name="bio"><?= h(arr_get($ed_eq,'bio','')) ?></textarea></div>
        <div><label>Foto (subir)</label><input type="file" name="foto_url" accept="image/*"><div class="badge">Ideal: URL Cloudinary</div></div>
        <div>
          <label>Foto (URL)</label>
          <div class="inline">
            <input type="text" name="foto_url" id="eq-foto-url" placeholder="https://..." value="<?= h(arr_get($ed_eq,'foto_url','')) ?>">
            <button type="button" class="btn" id="btn-eq-foto">Subir a la nube</button>
          </div>
          <img id="eq-foto-prev" class="thumb" style="margin-top:8px;<?= arr_get($ed_eq,'foto_url','')?'':'display:none' ?>" src="<?= h(arr_get($ed_eq,'foto_url','')) ?>">
        </div>
        <div><label>Instagram (URL)</label><input type="url" name="instagram" placeholder="https://instagram.com/..." value="<?= h(arr_get($ed_eq,'instagram','')) ?>"></div>
        <div><label>Orden</label><input type="number" name="orden" value="<?= (int)arr_get($ed_eq,'orden',0) ?>"></div>
        <div><label><input type="checkbox" name="activo" <?= (int)arr_get($ed_eq,'activo',1)?'checked':'' ?>> Activo</label></div>
      </div>
      <div style="margin-top:12px"><button class="btn" type="submit" <?= !$db_ok?'disabled':''; ?>><?= $ed_eq?'Guardar cambios':'Guardar miembro' ?></button></div>
    </form>

    <h3 style="margin-top:16px">Últimos 15 miembros</h3>
    <table>
      <thead><tr><th>ID</th><th>Foto</th><th>Nombre</th><th>Rol</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($equipo as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= !empty($r['foto_url'])?'<img class="thumb" src="'.h($r['foto_url']).'">':'' ?></td>
          <td><?= h(first_of($r, ['nombre','titulo','name'], '(sin nombre)')) ?></td>
          <td><?= h(arr_get($r,'rol','')) ?></td>
          <td><?= (int)$r['orden'] ?></td>
          <td><?= !empty($r['activo'])?'Sí':'No' ?></td>
          <td class="actions">
            <a class="link" href="?edit_eq=<?=$r['id']?>#sec-eq">Editar</a>
            <a class="link" href="?del_eq=<?=$r['id']?>#sec-eq" onclick="return confirm('¿Eliminar?')">Eliminar</a>
          </td>
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

  function canUseWidget(){ return (typeof cloudinary !== 'undefined') && !!CLD_NAME && !!CLD_PRESET; }

  // Subida directa (unsigned) a Cloudinary si el widget no está disponible
  async function directUpload(file, resourceType){
    const endpoint = `https://api.cloudinary.com/v1_1/${CLD_NAME}/${resourceType}/upload`;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('upload_preset', CLD_PRESET);
    if (CLD_FOLDER) fd.append('folder', CLD_FOLDER);
    const res = await fetch(endpoint, { method:'POST', body: fd });
    if (!res.ok) throw new Error('Upload HTTP ' + res.status);
    return await res.json(); // { secure_url, bytes, ... }
  }

  // btnId, inputId (texto URL), type ('image'|'video'), prevId (img opcional), fileName del input file
  function attachUploader(btnId, inputId, type, prevId, fileFieldName){
    const btn   = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    const prev  = prevId ? document.getElementById(prevId) : null;
    if (!btn || !input) return;

    const form = btn.closest('form');
    const file = form ? form.querySelector(`input[type="file"][name="${fileFieldName}"]`) : null;

    // Mostrar preview si pegan una URL manualmente
    if (prev && input){
      const syncPrev = () => {
        const v = input.value.trim();
        if (v) { prev.src = v; prev.style.display = 'inline-block'; }
        else   { prev.style.display = 'none'; }
      };
      input.addEventListener('input', syncPrev);
      input.addEventListener('change', syncPrev);
    }

    // Si hay widget, lo usamos; si no, fallback directo
    let widget = null;
    if (canUseWidget()){
      widget = cloudinary.createUploadWidget({
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
          input.value = result.info.secure_url || '';
          if (prev && result.info.secure_url){ prev.src = result.info.secure_url; prev.style.display='inline-block'; }
          if (type === 'video') {
            const vf = document.getElementById('video-file');
            if (vf) { vf.value = ''; vf.disabled = true; }
            const hint = document.getElementById('video-hint');
            if (hint) hint.textContent = 'Subido ('+Math.round(result.info.bytes/1024/1024)+' MB)';
          }
        }
      });
    } else {
      btn.title = "Subida directa (sin widget)";
    }

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (widget) { widget.open(); return; }

      // Fallback: subir el archivo de la galería/cámara
      const f = file && file.files ? file.files[0] : null;
      if (!f) { alert('Elegí un archivo en “Imagen (subir)” o pegá una URL.'); return; }

      const old = btn.textContent;
      btn.disabled = true; btn.textContent = 'Subiendo...';
      try{
        const r = await directUpload(f, type === 'video' ? 'video' : 'image');
        input.value = r.secure_url || '';
        if (prev && r.secure_url){ prev.src = r.secure_url; prev.style.display='inline-block'; }
        if (type === 'video') {
          const hint = document.getElementById('video-hint');
          if (hint && r.bytes) hint.textContent = 'Subido ('+Math.round(r.bytes/1024/1024)+' MB)';
        }
      }catch(err){
        console.error(err);
        alert('No se pudo subir a la nube. Probá nuevamente o pegá una URL.');
      }finally{
        btn.disabled = false; btn.textContent = old;
      }
    });
  }

  // Secciones (mapeo del campo file correcto)
  attachUploader('cld-foto-btn','foto-url','image','foto-prev','imagen_url'); // FOTOS
  attachUploader('btn-disc-img','disc-img-url','image','disc-img-prev','imagen_url'); // DISCIPLINAS
  attachUploader('btn-ofe-img','ofe-img-url','image','ofe-img-prev','imagen_url');   // OFERTAS
  attachUploader('btn-promo-img','promo-img-url','image','promo-img-prev','imagen_url'); // PROMOS
  attachUploader('btn-ven-img','ven-img-url','image','ven-img-prev','imagen_url');   // VENTAS
  attachUploader('btn-eq-foto','eq-foto-url','image','eq-foto-prev','foto_url');     // EQUIPO
  attachUploader('cld-video-btn','video-url','video',null,'video_file');             // VIDEOS: archivo
  attachUploader('cld-cover-btn','cover-url','image','cover-prev','cover_url');      // VIDEOS: cover

  // Habilitar/deshabilitar file de video según URL
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
