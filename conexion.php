<?php
// Lee variables de entorno desde Render
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: '3306';      // <-- agregado
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'railway';

// Conexión con puerto
$conexion = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
if ($conexion->connect_errno) {
  http_response_code(500);
  die('Error DB: '.$conexion->connect_error);
}
$conexion->set_charset('utf8mb4');

// Crea tabla/row si no existen (primer arranque)
$conexion->query("
CREATE TABLE IF NOT EXISTS site_settings (
  id INT PRIMARY KEY,
  color_principal VARCHAR(20) DEFAULT '#22c55e',
  color_secundario VARCHAR(20) DEFAULT '#14b8a6',
  fondo_img VARCHAR(255) DEFAULT '',
  logo_img VARCHAR(255) DEFAULT '',
  texto_banner VARCHAR(255) DEFAULT 'Promo de bienvenida: 50% OFF en matrícula + clase de prueba sin cargo.',
  youtube VARCHAR(255) DEFAULT '',
  instagram VARCHAR(255) DEFAULT '',
  facebook VARCHAR(255) DEFAULT '',
  google_maps TEXT
)");
$conexion->query("INSERT IGNORE INTO site_settings (id) VALUES (1)");
