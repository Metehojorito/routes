<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;
$db = getDB();
$alojamiento = null;
$error = '';
$success = '';

// Si es edición, cargar alojamiento
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM alojamientos WHERE id = ?");
    $stmt->execute([$id]);
    $alojamiento = $stmt->fetch();
    if (!$alojamiento) die("Alojamiento no encontrado");
    $viaje_id = $alojamiento['viaje_id'];
}

// Obtener viaje
$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();
if (!$viaje) die("Viaje no encontrado");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;
    
    if (empty($nombre)) {
        $error = "El nombre del alojamiento es obligatorio";
    } else {
        try {
            if ($id > 0) {
                // Actualizar
                $stmt = $db->prepare("
                    UPDATE alojamientos SET 
                        nombre = ?, direccion = ?, telefono = ?, lat = ?, lng = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $direccion, $telefono, $lat, $lng, $id]);
                $success = "Alojamiento actualizado correctamente";
            } else {
                // Insertar
                $stmt = $db->prepare("
                    INSERT INTO alojamientos (viaje_id, nombre, direccion, telefono, lat, lng)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$viaje_id, $nombre, $direccion, $telefono, $lat, $lng]);
                $id = $db->lastInsertId();
                $success = "Alojamiento creado correctamente";
            }
            
            // Recargar
            if ($id > 0) {
                $stmt = $db->prepare("SELECT * FROM alojamientos WHERE id = ?");
                $stmt->execute([$id]);
                $alojamiento = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Editar' : 'Nuevo'; ?> Alojamiento - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <div class="flex items-center">
                    <a href="alojamientos_list.php?viaje_id=<?php echo $viaje_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo $id > 0 ? 'Editar' : 'Nuevo'; ?> Alojamiento</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Alojamiento *</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($alojamiento['nombre'] ?? ''); ?>" required
                           placeholder="Ej: Hotel Ibis Rotterdam City Centre"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección Completa</label>
                    <textarea name="direccion" rows="2"
                              placeholder="Calle, número, código postal, ciudad, país"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($alojamiento['direccion'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($alojamiento['telefono'] ?? ''); ?>"
                           placeholder="+31 10 750 2520"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="border-t pt-6">
					<h3 class="text-lg font-semibold text-gray-900 mb-4">📍 Ubicación (Opcional)</h3>
					
					<div class="grid grid-cols-2 gap-4 mb-4">
						<div>
							<label class="block text-sm font-medium text-gray-700 mb-2">Latitud</label>
							<input type="number" id="lat" name="lat" 
								   value="<?php echo htmlspecialchars($alojamiento['lat'] ?? ''); ?>" 
								   step="0.00000001" placeholder="51.9190"
								   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						</div>
						<div>
							<label class="block text-sm font-medium text-gray-700 mb-2">Longitud</label>
							<input type="number" id="lng" name="lng" 
								   value="<?php echo htmlspecialchars($alojamiento['lng'] ?? ''); ?>" 
								   step="0.00000001" placeholder="4.4887"
								   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						</div>
					</div>
					
					<!-- Mapa interactivo -->
					<div class="mb-4">
						<label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar ubicación del alojamiento</label>
						<div id="location-map" class="w-full h-80 rounded-lg border-2 border-gray-300 overflow-hidden"></div>
					</div>
					
					<div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
						<p class="text-sm text-blue-800">
							<strong>💡 Búsqueda rápida:</strong> Introduce la dirección en Google Maps, copia las coordenadas 
							(click derecho en el mapa) y pégalas aquí, o simplemente busca el hotel en el mapa y haz click.
						</p>
					</div>
				</div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="alojamientos_list.php?viaje_id=<?php echo $viaje_id; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                        <?php echo $id > 0 ? 'Actualizar' : 'Guardar'; ?> Alojamiento
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
		const picker = createLocationPicker('location-map', 'lat', 'lng', {
			initialLat: 51.9190,
			initialLng: 4.4887,
			initialZoom: 14
		});
	});
	</script>
</body>
</html>