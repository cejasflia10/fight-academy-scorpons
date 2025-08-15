<?php
// conexion.php — conexión robusta + esquema mínimo

ini_set('display_errors', 1);
error_reporting(E_ALL);

// MUY IMPORTANTE: que mysqli no lance excepciones (evita fatal)
mysqli_report(MYSQLI_REPORT_OFF);

// ===== Datos de entorno o defaults locales (XAMPP)
$ENV_HOST = getenv('DB_HOST') ?: 'localhost';
$ENV_PORT = (int)(getenv('DB_PORT') ?: 3306);
$ENV_USER = getenv('DB_USER') ?: 'root';
$ENV_PASS = getenv('DB_PASS') ?: '';
$ENV_NAME = getenv('DB_NAME') ?: 'railway';

// ===== Intento de conexión con timeouts
function try_connect($host,$port,$user,$pass,$db){
  $link = mysqli_init();
  if (!$link) return false;
  mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 4);
  mysqli_options($link, MYSQLI_OPT_READ_TIMEOUT, 8);

  // conectamos sin DB primero; luego creamos/seleccionamos
  if (!@mysqli_real_connect($link, $host, $user, $pass, null, (int)$port)) {
    @mysqli_close($link);
    return false;
  }

  // crear DB si no existe y seleccionar
  if ($db) {
    $db_safe = str_replace('`','', $db);
    @mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `$db_safe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    if (!@mysqli_select_db($link, $db_safe)) {
      @mysqli_close($link);
      return false;
    }
  }

  @$link->set_charset('utf8mb4');
  return $link;
}

// Orden de candidatos: env → 127.0.0.1 → localhost
$candidates = [
  [$ENV_HOST, $ENV_PORT, $ENV_USER, $ENV_PASS, $ENV_NAME],
  ['127.0.0.1', 3306,    $ENV_USER, $ENV_PASS, $ENV_NAME],
  ['localhost', 3306,    $ENV_USER, $ENV_PASS, $ENV_NAME],
];

$conexion = false;
foreach ($candidates as $c) {
  $conexion = try_connect($c[0], $c[1], $c[2], $c[3], $c[4]);
  if ($conexion) break;
}

// Si NO conectó, dejamos $conexion=false y que el panel muestre aviso
if (!$conexion) {
  return;
}

// ===== Esquema mínimo (todas con id/orden/activo donde aplica)
@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS site_settings (
    id TINYINT PRIMARY KEY,
    color_principal  VARCHAR(7)  DEFAULT '#22c55e',
    color_secundario VARCHAR(7)  DEFAULT '#14b8a6',
    fondo_img  VARCHAR(255) DEFAULT '',
    logo_img   VARCHAR(255) DEFAULT '',
    texto_banner VARCHAR(255) DEFAULT 'Promo de bienvenida: 50% OFF en matrícula + clase de prueba sin cargo.',
    youtube   VARCHAR(255) DEFAULT '',
    instagram VARCHAR(255) DEFAULT '',
    facebook  VARCHAR(255) DEFAULT '',
    google_maps TEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(255),
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150),
    imagen_url VARCHAR(255),
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150),
    video_url VARCHAR(255),
    tipo VARCHAR(20) DEFAULT 'youtube',
    cover_url VARCHAR(255),
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS ofertas (
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

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(255),
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS ventas (
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

@mysqli_query($conexion, "
  CREATE TABLE IF NOT EXISTS equipo (
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

// Semilla site_settings
@mysqli_query($conexion, "INSERT IGNORE INTO site_settings (id) VALUES (1)");
