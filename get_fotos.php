<?php
// /get_fotos.php  → devuelve JSON con las fotos activas
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

try {
  require __DIR__ . '/conexion.php';                    // usa tu conexión existente
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $conexion->set_charset('utf8mb4');

  // Trae fotos activas ordenadas
  $sql = "SELECT id, titulo, imagen_url
          FROM fotos
          WHERE activo = 1
          ORDER BY COALESCE(orden,0) ASC, id DESC
          LIMIT 120";
  $res = $conexion->query($sql);

  $items = [];
  while ($row = $res->fetch_assoc()) {
    $url = trim((string)$row['imagen_url']);
    // Si guardaste solo el nombre de archivo, forzá prefijo /uploads/
    if ($url !== '' && !preg_match('~^(?:https?:)?/|^data:~i', $url)) {
      $url = '/uploads/' . ltrim($url, '/');
    }
    $items[] = [
      'id'         => (int)$row['id'],
      'titulo'     => (string)($row['titulo'] ?? ''),
      'imagen_url' => $url,
    ];
  }

  echo json_encode(['ok' => true, 'items' => $items],
                   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
