<?php
// api/fotos.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/../conexion.php';

try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $conexion->set_charset('utf8mb4');

  $rs = $conexion->query("SELECT id, titulo, imagen_url, orden, activo
                          FROM fotos
                          WHERE activo = 1
                          ORDER BY orden ASC, id DESC");
  $out = [];
  while ($row = $rs->fetch_assoc()) {
    // Normalizamos la ruta por si viene relativa sin barra
    $img = trim($row['imagen_url'] ?? '');
    if ($img !== '' && !preg_match('~^https?://~i', $img)) {
      if ($img[0] !== '/') $img = '/'.$img; // ej: uploads/xxx.jpg -> /uploads/xxx.jpg
    }
    $out[] = [
      'id'    => (int)$row['id'],
      'titulo'=> (string)($row['titulo'] ?? ''),
      'src'   => $img,
    ];
  }
  echo json_encode(['ok'=>true,'items'=>$out], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
