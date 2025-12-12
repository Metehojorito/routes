<?php
require_once 'auth.php';
require_once '../config/database.php';

$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;
$db = getDB();

// Obtener viaje
$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();

if (!$viaje) {
    die("Viaje no encontrado");
}

// Obtener días del viaje con conteo de actividades
$stmt = $db->prepare("
    SELECT d.*, 
        (SELECT COUNT(*) FROM actividades WHERE dia_id = d.id) as total_actividades,
        (SELECT COUNT(*) FROM secciones_dia WHERE dia_id = d.id) as total_secciones
    FROM dias_viaje d 
    WHERE d.viaje_id = ? 
    ORDER BY d.orden, d.numero_dia
");
$stmt->execute([$viaje_id]);
$dias = $stmt->fetchAll();

$meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$dias_semana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Días de <?php echo htmlspecialchars($viaje['titulo']); ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Días del Viaje</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
                        </div>
                    </div>
                    <a href="dia_form.php?viaje_id=<?php echo $viaje_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        + Nuevo Día
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Itinerario - <?php echo count($dias); ?> días
                    </h2>
                    <div class="flex gap-2">
                        <a href="alojamiento_form.php?viaje_id=<?php echo $viaje_id; ?>" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded font-medium transition">
                            Alojamiento
                        </a>
                        <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>" class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded font-medium transition">
                            Emergencias
                        </a>
                    </div>
                </div>
                
                <?php if (empty($dias)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay días</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza creando el primer día del viaje.</p>
                    <div class="mt-6">
                        <a href="dia_form.php?viaje_id=<?php echo $viaje_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            + Crear Día
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($dias as $dia): 
                        $fecha = new DateTime($dia['fecha']);
                        $mes = $meses[(int)$fecha->format('n')];
                        $dia_num = $fecha->format('d');
                        $dia_semana = $dias_semana[(int)$fecha->format('w')];
                    ?>
                    <div class="px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="flex-shrink-0 w-16 h-16 bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                                    <span class="text-xs font-semibold text-blue-600"><?php echo strtoupper($mes); ?></span>
                                    <span class="text-2xl font-bold text-blue-700"><?php echo $dia_num; ?></span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($dia['titulo']); ?></h3>
                                        <span class="text-sm text-gray-500">• Día <?php echo $dia['numero_dia']; ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo $dia_semana; ?>, <?php echo $fecha->format('d/m/Y'); ?></p>
                                    <div class="flex gap-2 mt-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                            <?php echo $dia['total_secciones']; ?> secciones
                                        </span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            <?php echo $dia['total_actividades']; ?> actividades
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="../dia.php?viaje=<?php echo urlencode($viaje['slug']); ?>&dia=<?php echo $dia['numero_dia']; ?>" target="_blank" 
                                   class="text-blue-600 hover:text-blue-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-blue-50 transition">
                                    Ver
                                </a>
                                <a href="secciones_list.php?dia_id=<?php echo $dia['id']; ?>" 
                                   class="text-purple-600 hover:text-purple-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-purple-50 transition">
                                    Secciones
                                </a>
                                <a href="actividades_list.php?dia_id=<?php echo $dia['id']; ?>" 
                                   class="text-green-600 hover:text-green-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-green-50 transition">
                                    Actividades
                                </a>
                                <a href="dia_form.php?id=<?php echo $dia['id']; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-indigo-50 transition">
                                    Editar
                                </a>
                                <a href="dia_delete.php?id=<?php echo $dia['id']; ?>" 
                                   onclick="return confirm('¿Eliminar este día y todas sus actividades?')" 
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
        </main>
    </div>
</body>
</html>