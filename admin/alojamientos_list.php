<?php
// alojamientos_list.php
require_once 'auth.php';
require_once '../config/database.php';

$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;
$db = getDB();

$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();
if (!$viaje) die("Viaje no encontrado");

$stmt = $db->prepare("SELECT * FROM alojamientos WHERE viaje_id = ? ORDER BY id");
$stmt->execute([$viaje_id]);
$alojamientos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alojamientos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="icon" href="images/favicon-32x32.png" sizes="32x32" type="image/png">
	<link rel="icon" href="images/favicon-16x16.png" sizes="16x16" type="image/png">
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
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
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Alojamientos</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
                        </div>
                    </div>
                    <a href="alojamiento_form.php?viaje_id=<?php echo $viaje_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        + Añadir Alojamiento
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Alojamientos del Viaje (<?php echo count($alojamientos); ?>)
                    </h2>
                </div>
                
                <?php if (empty($alojamientos)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay alojamientos configurados</h3>
                    <p class="mt-1 text-sm text-gray-500">Añade el hotel, apartamento o lugar donde te alojas.</p>
                    <div class="mt-6">
                        <a href="alojamiento_form.php?viaje_id=<?php echo $viaje_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            + Añadir Alojamiento
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($alojamientos as $alojamiento): ?>
                    <div class="px-6 py-6 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="flex-shrink-0 w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600 text-3xl">home</span>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($alojamiento['nombre']); ?></h3>
                                    
                                    <?php if ($alojamiento['direccion']): ?>
                                    <div class="flex items-start gap-2 mt-2">
                                        <span class="material-symbols-outlined text-base text-gray-500 mt-0.5">location_on</span>
                                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($alojamiento['direccion'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($alojamiento['telefono']): ?>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="material-symbols-outlined text-base text-gray-500">call</span>
                                        <a href="tel:<?php echo htmlspecialchars($alojamiento['telefono']); ?>" 
                                           class="text-sm text-blue-600 hover:text-blue-800">
                                            <?php echo htmlspecialchars($alojamiento['telefono']); ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($alojamiento['lat'] && $alojamiento['lng']): ?>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="material-symbols-outlined text-base text-gray-500">map</span>
                                        <a href="https://www.google.com/maps?q=<?php echo $alojamiento['lat']; ?>,<?php echo $alojamiento['lng']; ?>" 
                                           target="_blank"
                                           class="text-sm text-blue-600 hover:text-blue-800">
                                            Ver en Google Maps
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 ml-4">
                                <a href="alojamiento_form.php?id=<?php echo $alojamiento['id']; ?>&viaje_id=<?php echo $viaje_id; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900 p-2 rounded hover:bg-indigo-50 transition"
                                   title="Editar">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <a href="alojamiento_delete.php?id=<?php echo $alojamiento['id']; ?>&viaje_id=<?php echo $viaje_id; ?>" 
                                   onclick="return confirm('¿Eliminar este alojamiento?')" 
                                   class="text-red-600 hover:text-red-900 p-2 rounded hover:bg-red-50 transition"
                                   title="Eliminar">
                                    <span class="material-symbols-outlined">delete</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">💡 Consejo</h3>
                <p class="text-sm text-blue-800">
                    Los alojamientos aparecerán en el menú lateral de la parte pública. Puedes añadir varios si 
                    cambias de hotel durante el viaje. El primero será el que se muestre por defecto.
                </p>
            </div>
        </main>
    </div>
</body>
</html>