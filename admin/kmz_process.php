<?php
require_once 'auth.php';
require_once '../config/database.php';

// Verificar que hay datos de importación en sesión
if (!isset($_SESSION['kmz_import_data'])) {
    header('Location: index.php');
    exit;
}

$import_data = $_SESSION['kmz_import_data'];
$viaje_id = $import_data['viaje_id'];
$capas = $import_data['capas'];

// Obtener información del viaje
$db = getDB();
$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();

if (!$viaje) {
    unset($_SESSION['kmz_import_data']);
    header('Location: index.php');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $fecha_inicio = new DateTime($viaje['fecha_inicio']);
        $dias_creados = 0;
        $actividades_creadas = 0;
        
        // Procesar cada capa
        foreach ($capas as $index => $capa) {
            $dia_numero = isset($_POST["capa_{$index}_dia"]) ? (int)$_POST["capa_{$index}_dia"] : 0;
            
            if ($dia_numero <= 0) {
                continue; // Saltar capas no asignadas
            }
            
            // Calcular la fecha del día
            $fecha_dia = clone $fecha_inicio;
            $fecha_dia->modify('+' . ($dia_numero - 1) . ' days');
            
            // Calcular centro del mapa (promedio de coordenadas)
            $total_lat = 0;
            $total_lng = 0;
            $total_puntos = count($capa['puntos']);
            
            foreach ($capa['puntos'] as $punto) {
                $total_lat += $punto['lat'];
                $total_lng += $punto['lng'];
            }
            
            $centro_lat = $total_puntos > 0 ? $total_lat / $total_puntos : 0;
            $centro_lng = $total_puntos > 0 ? $total_lng / $total_puntos : 0;
            
            // Verificar si ya existe un día con ese número
            $stmt = $db->prepare("SELECT id FROM dias_viaje WHERE viaje_id = ? AND numero_dia = ?");
            $stmt->execute([$viaje_id, $dia_numero]);
            $dia_existente = $stmt->fetch();
            
            if ($dia_existente) {
                // Usar el día existente
                $dia_id = $dia_existente['id'];
            } else {
                // Crear nuevo día
                $stmt = $db->prepare("
                    INSERT INTO dias_viaje (
                        viaje_id, 
                        numero_dia, 
                        fecha, 
                        titulo, 
                        descripcion, 
                        centro_mapa_lat, 
                        centro_mapa_lng, 
                        zoom_mapa, 
                        orden,
						visible
                    ) VALUES (?, ?, ?, ?, '', ?, ?, 14, ?, 1)
                ");
                
                $titulo_dia = $capa['nombre'];
                
                $stmt->execute([
                    $viaje_id,
                    $dia_numero,
                    $fecha_dia->format('Y-m-d'),
                    $titulo_dia,
                    $centro_lat,
                    $centro_lng,
                    $dia_numero
                ]);
                
                $dia_id = $db->lastInsertId();
                $dias_creados++;
            }
            
            // Crear actividades
            $orden = 1;
            foreach ($capa['puntos'] as $punto) {
                $stmt = $db->prepare("
                    INSERT INTO actividades (
                        dia_id,
                        seccion_id,
                        titulo,
                        descripcion,
                        icono,
                        color_categoria,
                        lat,
                        lng,
                        orden,
						visible
                    ) VALUES (?, NULL, ?, ?, 'place', 'primary', ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $dia_id,
                    $punto['nombre'],
                    $punto['descripcion'],
                    $punto['lat'],
                    $punto['lng'],
                    $orden
                ]);
                
                $actividades_creadas++;
                $orden++;
            }
        }
        
        $db->commit();
        
        // Limpiar sesión
        unset($_SESSION['kmz_import_data']);
        
        // Limpiar archivos temporales
        if (isset($import_data['archivo_temporal']) && file_exists($import_data['archivo_temporal'])) {
            $temp_dir = dirname($import_data['archivo_temporal']);
            array_map('unlink', glob("$temp_dir/*"));
            rmdir($temp_dir);
        }
        
        // Redirigir con mensaje de éxito
        $_SESSION['import_success'] = "Se crearon $dias_creados día(s) y $actividades_creadas actividad(es) exitosamente.";
        header("Location: dias_list.php?viaje_id=$viaje_id");
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['import_error'] = "Error al procesar la importación: " . $e->getMessage();
        header("Location: kmz_import.php?viaje_id=$viaje_id");
        exit;
    }
} else {
    // Si no es POST, redirigir
    header("Location: kmz_import.php?viaje_id=$viaje_id");
    exit;
}
