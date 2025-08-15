<?php
// api.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // permitir que tu web estática consuma
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/conexion.php';

$allowed = [
  'disciplinas' => ['cols' => 'id,titulo,descripcion,imagen_url,orden,activo,creado'],
  'fotos'       => ['cols' => 'id,titulo,imagen_url,orden,activo,creado'],
  'videos'      => ['cols' => 'id,titulo,video_url,tipo,cover_url,orden,activo,creado'],
  'ofertas'     => ['cols' => 'id,titulo,descripcion,precio,vigente_desde,vigente_hasta,imagen_url,orden,activo,creado'],
  'promociones' => ['cols' => 'id,titulo,descripcion,imagen_url,orden,activo,creado'],
  'ventas'      => ['cols' => 'id,nombre,descripcion,precio,imagen_url,stock,orden,activo,creado'],
  'equipo'      => ['cols' => 'id,nombre,rol,bio,foto_url,instagram,orden,activo,creado']
];

$sec = $_GET['sec'] ?? '';
if (!isset($allowed[$sec])) {
  http_response_code(400);
  echo json_encode(['error' => 'Sección inválida']);
  exit;
}

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active  = isset($_GET['active']) ? (int)$_GET['active'] : 1; // por defecto solo activos
$limit   = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 100;
$offset  = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$cols    = $allowed[$sec]['cols'];

if ($id > 0) {
  $stmt = $conexion->prepare("SELECT $cols FROM $sec WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  echo json_encode($row ?: new stdClass(), JSON_UNESCAPED_UNICODE);
  exit;
}

$where = '1';
$params = [];
$types  = '';

if ($active === 1) { $where .= ' AND activo=1'; }

$sql = "SELECT $cols FROM $sec WHERE $where ORDER BY orden ASC, id DESC LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;

echo json_encode([
  'section' => $sec,
  'count'   => count($out),
  'items'   => $out
], JSON_UNESCAPED_UNICODE);
