<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;
$db = getDB();
$dia = null;
$viaje = null;
$error = '';
$success = '';

// Si es edición, cargar día
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM dias_viaje WHERE id = ?");
    $stmt->execute([$id]);
    $dia = $stmt->fetch();
    if (!$dia) die("Día no encontrado");
    $viaje_id = $dia['viaje_id'];
}

// Obtener viaje
$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();
if (!$viaje) die("Viaje no encontrado");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_dia = (int)$_POST['numero_dia'];
    $fecha = $_POST['fecha'];
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $centro_mapa_lat = (float)$_POST['centro_mapa_lat'];
    $centro_mapa_lng = (float)$_POST['centro_mapa_lng'];
    $zoom_mapa = (int)($_POST['zoom_mapa'] ?? 14);
    $orden = (int)($_POST['orden'] ?? $numero_dia);
	$visible = isset($_POST['visible']) ? 1 : 0;
    
    if (empty($titulo) || empty($fecha)) {
        $error = "El título y la fecha son obligatorios";
    } elseif ($centro_mapa_lat == 0 || $centro_mapa_lng == 0) {
        $error = "Las coordenadas del mapa son obligatorias";
    } else {
        try {
            if ($id > 0) {
				$stmt = $db->prepare("
					UPDATE dias_viaje SET 
						numero_dia = ?, fecha = ?, titulo = ?, descripcion = ?,
						centro_mapa_lat = ?, centro_mapa_lng = ?, zoom_mapa = ?, orden = ?, visible = ?
					WHERE id = ?
				");
				$stmt->execute([$numero_dia, $fecha, $titulo, $descripcion, 
							   $centro_mapa_lat, $centro_mapa_lng, $zoom_mapa, $orden, $visible, $id]);
                $success = "Día actualizado correctamente";
            } else {
				$stmt = $db->prepare("
					INSERT INTO dias_viaje 
					(viaje_id, numero_dia, fecha, titulo, descripcion, centro_mapa_lat, 
					 centro_mapa_lng, zoom_mapa, orden, visible)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				");
				$stmt->execute([$viaje_id, $numero_dia, $fecha, $titulo, $descripcion,
								$centro_mapa_lat, $centro_mapa_lng, $zoom_mapa, $orden, $visible]);
                $id = $db->lastInsertId();
                header("Location: dia_form.php?id=$id&success=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

if (isset($_GET['success'])) $success = "Día creado correctamente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Editar' : 'Nuevo'; ?> Día - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="icon" href="images/favicon-32x32.png" sizes="32x32" type="image/png">
	<link rel="icon" href="images/favicon-16x16.png" sizes="16x16" type="image/png">
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
	<!-- Leaflet CSS -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
		  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
		  crossorigin=""/>
    <style>
		body { font-family: 'Inter', sans-serif; }
	
	/* Estilos para el mapa */
	#location-map { 
		height: 320px;
		border-radius: 0.5rem;
	}
	.leaflet-container {
		font-family: 'Inter', sans-serif;
	}
	</style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="dias_list.php?viaje_id=<?php echo $viaje_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900"><?php echo $id > 0 ? 'Editar' : 'Nuevo'; ?> Día</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número de Día *</label>
                        <input type="number" name="numero_dia" value="<?php echo htmlspecialchars($dia['numero_dia'] ?? 1); ?>" required min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha *</label>
                        <input type="date" name="fecha" value="<?php echo htmlspecialchars($dia['fecha'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título del Día *</label>
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($dia['titulo'] ?? ''); ?>" required
                           placeholder="Ej: Día 1: Llegada y tarde en Dordrecht"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="2"
                              placeholder="Descripción opcional del día"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($dia['descripcion'] ?? ''); ?></textarea>
                </div>
				
				<div class="border-t pt-6">
					<h3 class="text-lg font-semibold text-gray-900 mb-4">🗺️ Configuración del Mapa</h3>
					
					<div class="grid grid-cols-2 gap-4 mb-4">
						<div>
							<label class="block text-sm font-medium text-gray-700 mb-2">Latitud Centro *</label>
							<input type="number" id="centro_mapa_lat" name="centro_mapa_lat" 
								   value="<?php echo htmlspecialchars($dia['centro_mapa_lat'] ?? ''); ?>" 
								   step="0.00000001" required placeholder="51.8143"
								   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						</div>
						<div>
							<label class="block text-sm font-medium text-gray-700 mb-2">Longitud Centro *</label>
							<input type="number" id="centro_mapa_lng" name="centro_mapa_lng" 
								   value="<?php echo htmlspecialchars($dia['centro_mapa_lng'] ?? ''); ?>" 
								   step="0.00000001" required placeholder="4.6650"
								   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						</div>
					</div>
					
					<!-- Mapa interactivo -->
					<div class="mb-4">
						<label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar centro del mapa</label>
						<div id="location-map" class="w-full h-80 rounded-lg border-2 border-gray-300 overflow-hidden"></div>
					</div>
					
					<div class="mt-4">
						<label class="block text-sm font-medium text-gray-700 mb-2">Zoom del Mapa</label>
						<input type="number" name="zoom_mapa" value="<?php echo htmlspecialchars($dia['zoom_mapa'] ?? 14); ?>" 
							   min="1" max="20"
							   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<p class="mt-1 text-sm text-gray-500">Entre 1 (muy alejado) y 20 (muy cerca). Recomendado: 12-16</p>
					</div>
					
					<div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Orden</label>
                        <input type="number" name="orden" value="<?php echo htmlspecialchars($dia['orden'] ?? $dia['numero_dia'] ?? 1); ?>" 
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">Orden de aparición en la lista. Por defecto, igual al número de día.</p>
                    </div>
					
					<div class="flex items-center mt-4">
						<input type="checkbox" name="visible" id="visible" 
							   <?php echo ($dia['visible'] ?? 1) ? 'checked' : ''; ?> 
							   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
						<label for="visible" class="ml-2 block text-sm text-gray-900">
							Día visible en la vista pública
						</label>
					</div>
					<p class="mt-1 text-sm text-gray-500">
						Si está desactivado, este día no aparecerá
					</p>
					
					<div class="mt-4 p-4 bg-blue-50 rounded-lg">
						<p class="text-sm text-blue-800">
							<strong>💡 Consejo:</strong> Haz click en el centro aproximado del área que quieres mostrar. 
							Las actividades aparecerán como pines en este mapa.
						</p>
					</div>
				</div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="dias_list.php?viaje_id=<?php echo $viaje_id; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                        <?php echo $id > 0 ? 'Actualizar' : 'Crear'; ?> Día
                    </button>
                </div>
            </form>
        </main>
    </div>
	
	<!-- Leaflet JavaScript -->
	<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
			integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
			crossorigin=""></script>

	<!-- Location Picker Component -->
	<script src="js/location-picker.js"></script>
	
	<script>
	// Inicializar el selector de ubicación
	document.addEventListener('DOMContentLoaded', () => {
		const picker = createLocationPicker('location-map', 'centro_mapa_lat', 'centro_mapa_lng', {
			initialLat: 51.8143,
			initialLng: 4.6650,
			initialZoom: 13
		});
	});
	</script>
</body>
</html>