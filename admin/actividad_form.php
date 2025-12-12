<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dia_id = isset($_GET['dia_id']) ? (int)$_GET['dia_id'] : 0;
$db = getDB();
$actividad = null;
$dia = null;
$error = '';
$success = '';

// Si es edición, cargar actividad
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM actividades WHERE id = ?");
    $stmt->execute([$id]);
    $actividad = $stmt->fetch();
    if (!$actividad) die("Actividad no encontrada");
    $dia_id = $actividad['dia_id'];
}

// Obtener día
$stmt = $db->prepare("
    SELECT d.*, v.titulo as viaje_titulo
    FROM dias_viaje d
    JOIN viajes v ON d.viaje_id = v.id
    WHERE d.id = ?
");
$stmt->execute([$dia_id]);
$dia = $stmt->fetch();
if (!$dia) die("Día no encontrado");

// Obtener secciones
$stmt = $db->prepare("SELECT * FROM secciones_dia WHERE dia_id = ? ORDER BY orden");
$stmt->execute([$dia_id]);
$secciones = $stmt->fetchAll();

// Obtener detalles si es edición
$detalles = [];
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM detalles_actividad WHERE actividad_id = ? ORDER BY orden");
    $stmt->execute([$id]);
    $detalles = $stmt->fetchAll();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $icono = trim($_POST['icono']);
    $color_categoria = $_POST['color_categoria'];
    $seccion_id = !empty($_POST['seccion_id']) ? (int)$_POST['seccion_id'] : null;
    $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;
    $orden = (int)($_POST['orden'] ?? 0);
    
    if (empty($titulo) || empty($icono)) {
        $error = "El título y el icono son obligatorios";
    } else {
        try {
            if ($id > 0) {
                $stmt = $db->prepare("
                    UPDATE actividades SET 
                        titulo = ?, descripcion = ?, icono = ?, color_categoria = ?,
                        seccion_id = ?, lat = ?, lng = ?, orden = ?
                    WHERE id = ?
                ");
                $stmt->execute([$titulo, $descripcion, $icono, $color_categoria, 
                               $seccion_id, $lat, $lng, $orden, $id]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO actividades 
                    (dia_id, titulo, descripcion, icono, color_categoria, seccion_id, lat, lng, orden)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$dia_id, $titulo, $descripcion, $icono, $color_categoria,
                               $seccion_id, $lat, $lng, $orden]);
                $id = $db->lastInsertId();
            }
            
            // Procesar detalles
            if (isset($_POST['detalles'])) {
                // Eliminar detalles existentes
                $stmt = $db->prepare("DELETE FROM detalles_actividad WHERE actividad_id = ?");
                $stmt->execute([$id]);
                
                // Insertar nuevos detalles
                $orden_detalle = 1;
                foreach ($_POST['detalles'] as $detalle) {
                    if (!empty($detalle['texto']) && !empty($detalle['icono'])) {
                        $stmt = $db->prepare("
                            INSERT INTO detalles_actividad (actividad_id, icono, texto, orden)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$id, $detalle['icono'], $detalle['texto'], $orden_detalle++]);
                    }
                }
            }
            
            header("Location: actividades_list.php?dia_id=$dia_id&success=1");
            exit;
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
    <title><?php echo $id > 0 ? 'Editar' : 'Nueva'; ?> Actividad - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center">
                    <a href="actividades_list.php?dia_id=<?php echo $dia_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo $id > 0 ? 'Editar' : 'Nueva'; ?> Actividad</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($dia['titulo']); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Información básica -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Básica</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                            <input type="text" name="titulo" value="<?php echo htmlspecialchars($actividad['titulo'] ?? ''); ?>" required
                                   placeholder="Ej: Vuelo de ida a Róterdam"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea name="descripcion" rows="2"
                                      placeholder="Ej: Sevilla (SVQ) → Róterdam (RTM)"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($actividad['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Icono Material * 
                                    <a href="https://fonts.google.com/icons" target="_blank" class="text-blue-600 text-xs">(Ver iconos)</a>
                                </label>
                                <input type="text" name="icono" value="<?php echo htmlspecialchars($actividad['icono'] ?? 'place'); ?>" required
                                       placeholder="flight, restaurant, museum..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                                <select name="color_categoria"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="primary" <?php echo ($actividad['color_categoria'] ?? 'primary') == 'primary' ? 'selected' : ''; ?>>Azul (Primary)</option>
                                    <option value="secondary" <?php echo ($actividad['color_categoria'] ?? '') == 'secondary' ? 'selected' : ''; ?>>Naranja (Secondary)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sección (Opcional)</label>
                            <select name="seccion_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Sin sección --</option>
                                <?php foreach ($secciones as $seccion): ?>
                                <option value="<?php echo $seccion['id']; ?>" <?php echo ($actividad['seccion_id'] ?? '') == $seccion['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($seccion['titulo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($secciones)): ?>
                            <p class="mt-1 text-sm text-blue-600">
                                <a href="secciones_list.php?dia_id=<?php echo $dia_id; ?>" class="underline">Crear secciones</a> para organizar mejor el día
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Ubicación en Mapa</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Latitud</label>
                            <input type="number" name="lat" value="<?php echo htmlspecialchars($actividad['lat'] ?? ''); ?>" 
                                   step="0.00000001" placeholder="51.8143"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Longitud</label>
                            <input type="number" name="lng" value="<?php echo htmlspecialchars($actividad['lng'] ?? ''); ?>" 
                                   step="0.00000001" placeholder="4.6650"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Opcional. Deja en blanco si no tiene ubicación específica.</p>
                </div>

                <!-- Detalles -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Detalles Adicionales (Opcional)</h2>
                    <p class="text-sm text-gray-600 mb-4">Horarios, precios, números de vuelo, etc.</p>
                    
                    <div id="detalles-container" class="space-y-3">
                        <?php if (!empty($detalles)): ?>
                            <?php foreach ($detalles as $idx => $detalle): ?>
                            <div class="flex gap-2 detalle-row">
                                <input type="text" name="detalles[<?php echo $idx; ?>][icono]" 
                                       value="<?php echo htmlspecialchars($detalle['icono']); ?>"
                                       placeholder="schedule, confirmation_number..."
                                       class="w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" name="detalles[<?php echo $idx; ?>][texto]" 
                                       value="<?php echo htmlspecialchars($detalle['texto']); ?>"
                                       placeholder="Llegada: 14:25"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="this.parentElement.remove()" 
                                        class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md">
                                    ✕
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" onclick="agregarDetalle()" 
                            class="mt-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                        + Añadir detalle
                    </button>
                </div>

                <!-- Orden -->
                <div class="bg-white shadow rounded-lg p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Orden</label>
                    <input type="number" name="orden" value="<?php echo htmlspecialchars($actividad['orden'] ?? 0); ?>" 
                           min="0" class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Orden de aparición. 0 = auto</p>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3">
                    <a href="actividades_list.php?dia_id=<?php echo $dia_id; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                        <?php echo $id > 0 ? 'Actualizar' : 'Crear'; ?> Actividad
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
    let detalleIndex = <?php echo !empty($detalles) ? count($detalles) : 0; ?>;
    
    function agregarDetalle() {
        const container = document.getElementById('detalles-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2 detalle-row';
        div.innerHTML = `
            <input type="text" name="detalles[${detalleIndex}][icono]" 
                   placeholder="schedule, confirmation_number..."
                   class="w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="text" name="detalles[${detalleIndex}][texto]" 
                   placeholder="Llegada: 14:25"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="button" onclick="this.parentElement.remove()" 
                    class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md">
                ✕
            </button>
        `;
        container.appendChild(div);
        detalleIndex++;
    }
    </script>
</body>
</html>