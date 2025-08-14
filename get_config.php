<?php
// get_config.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

$res = $conexion->query("SELECT * FROM site_settings WHERE id=1");
$row = $res && $res->num_rows ? $res->fetch_assoc() : [];

$defaults = [
  'color_principal' => '#22c55e',
  'color_secundario' => '#14b8a6',
  'fondo_img' => '',
  'logo_img' => '',
  'texto_banner' => 'Promo de bienvenida: 50% OFF en matrícula + clase de prueba sin cargo.',
  'youtube' => '',
  'instagram' => '',
  'facebook' => '',
  'google_maps' => ''
];

echo json_encode(array_merge($defaults, $row), JSON_UNESCAPED_UNICODE);
