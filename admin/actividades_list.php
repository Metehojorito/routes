<?php
require_once 'auth.php';
require_once '../config/database.php';

$dia_id = isset($_GET['dia_id']) ? (int)$_GET['dia_id'] : 0;
$db = getDB();

// Obtener día y viaje
$stmt = $db->prepare("
    SELECT d.*, v.id as viaje_id, v.titulo as viaje_titulo, v.slug as viaje_slug
    FROM dias_viaje d
    JOIN viajes v ON d.viaje_id = v.id
    WHERE d.id = ?
");
$stmt->execute([$dia_id]);
$dia = $stmt->fetch();
if (!$dia) die("Día no encontrado");

// Obtener actividades con sus secciones
$stmt = $db->prepare("
    SELECT a.*, s.titulo as seccion_titulo,
        (SELECT COUNT(*) FROM detalles_actividad WHERE actividad_id = a.id) as total_detalles
    FROM actividades a
    LEFT JOIN secciones_dia s ON a.seccion_id = s.id
    WHERE a.dia_id = ?
    ORDER BY a.orden, a.id
");
$stmt->execute([$dia_id]);
$actividades = $stmt->fetchAll();

// Obtener secciones para el select
$stmt = $db->prepare("SELECT * FROM secciones_dia WHERE dia_id = ? ORDER BY orden");
$stmt->execute([$dia_id]);
$secciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="dias_list.php?viaje_id=<?php echo $dia['viaje_id']; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Actividades del Día</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($dia['titulo']); ?></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="secciones_list.php?dia_id=<?php echo $dia_id; ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            Secciones
                        </a>
                        <a href="actividad_form.php?dia_id=<?php echo $dia_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            + Nueva Actividad
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Actividades (<?php echo count($actividades); ?>)
                    </h2>
                </div>
                
                <?php if (empty($actividades)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay actividades</h3>
                    <p class="mt-1 text-sm text-gray-500">Crea la primera actividad de este día.</p>
                    <div class="mt-6">
                        <a href="actividad_form.php?dia_id=<?php echo $dia_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            + Crear Actividad
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php 
                    $seccion_actual = null;
                    foreach ($actividades as $actividad): 
                        // Mostrar encabezado de sección si cambia
                        if ($actividad['seccion_titulo'] !== $seccion_actual):
                            $seccion_actual = $actividad['seccion_titulo'];
                            if ($seccion_actual):
                    ?>
                    <div class="px-6 py-3 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase"><?php echo htmlspecialchars($seccion_actual); ?></h3>
                    </div>
                    <?php 
                            endif;
                        endif; 
                    ?>
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="flex-shrink-0 w-12 h-12 bg-<?php echo $actividad['color_categoria'] == 'primary' ? 'blue' : 'orange'; ?>-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-<?php echo $actividad['color_categoria'] == 'primary' ? 'blue' : 'orange'; ?>-600">
                                        <?php echo htmlspecialchars($actividad['icono']); ?>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
                                    <?php if ($actividad['descripcion']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                    <?php endif; ?>
                                    <div class="flex gap-3 mt-2 text-xs text-gray-500">
                                        <?php if ($actividad['lat'] && $actividad['lng']): ?>
                                        <span>📍 <?php echo $actividad['lat']; ?>, <?php echo $actividad['lng']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($actividad['total_detalles'] > 0): ?>
                                        <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full font-medium">
                                            <?php echo $actividad['total_detalles']; ?> detalles
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <a href="actividad_form.php?id=<?php echo $actividad['id']; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-indigo-50 transition">
                                    Editar
                                </a>
                                <a href="actividad_delete.php?id=<?php echo $actividad['id']; ?>" 
                                   onclick="return confirm('¿Eliminar esta actividad y sus detalles?')" 
                                   class="text-red-600 hover:text-red-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-red-50 transition">
                                    Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex justify-center gap-4">
                <a href="../dia.php?viaje=<?php echo urlencode($dia['viaje_slug']); ?>&dia=<?php echo $dia['numero_dia']; ?>" 
                   target="_blank"
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    🔍 Ver Vista Pública
                </a>
            </div>
        </main>
    </div>
</body>
</html>