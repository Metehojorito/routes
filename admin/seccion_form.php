<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dia_id = isset($_GET['dia_id']) ? (int)$_GET['dia_id'] : 0;
$db = getDB();
$seccion = null;
$dia = null;
$error = '';
$success = '';

// Si es edición, cargar sección
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM secciones_dia WHERE id = ?");
    $stmt->execute([$id]);
    $seccion = $stmt->fetch();
    if (!$seccion) die("Sección no encontrada");
    $dia_id = $seccion['dia_id'];
}

// Obtener día y viaje
$stmt = $db->prepare("
    SELECT d.*, v.titulo as viaje_titulo
    FROM dias_viaje d
    JOIN viajes v ON d.viaje_id = v.id
    WHERE d.id = ?
");
$stmt->execute([$dia_id]);
$dia = $stmt->fetch();
if (!$dia) die("Día no encontrado");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $orden = (int)($_POST['orden'] ?? 0);
    
    if (empty($titulo)) {
        $error = "El título es obligatorio";
    } else {
        try {
            if ($id > 0) {
                // Actualizar
                $stmt = $db->prepare("UPDATE secciones_dia SET titulo = ?, orden = ? WHERE id = ?");
                $stmt->execute([$titulo, $orden, $id]);
                $success = "Sección actualizada correctamente";
            } else {
                // Insertar
                if ($orden == 0) {
                    // Auto-calcular orden
                    $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden FROM secciones_dia WHERE dia_id = ?");
                    $stmt->execute([$dia_id]);
                    $orden = $stmt->fetch()['nuevo_orden'];
                }
                
                $stmt = $db->prepare("INSERT INTO secciones_dia (dia_id, titulo, orden) VALUES (?, ?, ?)");
                $stmt->execute([$dia_id, $titulo, $orden]);
                $id = $db->lastInsertId();
                $success = "Sección creada correctamente";
            }
            
            // Recargar
            if ($id > 0) {
                $stmt = $db->prepare("SELECT * FROM secciones_dia WHERE id = ?");
                $stmt->execute([$id]);
                $seccion = $stmt->fetch();
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
    <title><?php echo $id > 0 ? 'Editar' : 'Nueva'; ?> Sección - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="icon" href="images/favicon-32x32.png" sizes="32x32" type="image/png">
	<link rel="icon" href="images/favicon-16x16.png" sizes="16x16" type="image/png">
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center">
                    <a href="secciones_list.php?dia_id=<?php echo $dia_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo $id > 0 ? 'Editar' : 'Nueva'; ?> Sección</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($dia['titulo']); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título de la Sección *</label>
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($seccion['titulo'] ?? ''); ?>" required
                           placeholder="Ej: Mañana, Tarde, Noche, Opcional..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Las secciones dividen el día en partes organizadas</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Orden</label>
                    <input type="number" name="orden" value="<?php echo htmlspecialchars($seccion['orden'] ?? 0); ?>" 
                           min="0"
                           class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Orden de aparición. 0 = auto</p>
                </div>

                <div class="p-4 bg-blue-50 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">💡 Ejemplos de secciones:</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• <strong>Mañana</strong> - Actividades de 9:00 a 13:00</li>
                        <li>• <strong>Tarde</strong> - Actividades de 14:00 a 19:00</li>
                        <li>• <strong>Noche</strong> - Cenas y actividades nocturnas</li>
                        <li>• <strong>Opcional</strong> - Actividades si hay tiempo</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="secciones_list.php?dia_id=<?php echo $dia_id; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                        <?php echo $id > 0 ? 'Actualizar' : 'Crear'; ?> Sección
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>