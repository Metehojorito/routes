<?php
require_once 'auth.php';
require_once '../config/database.php';

// Obtener todos los viajes
$db = getDB();
$stmt = $db->query("SELECT v.*, 
    (SELECT COUNT(*) FROM dias_viaje WHERE viaje_id = v.id) as total_dias
    FROM viajes v ORDER BY created_at DESC");
$viajes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Gestor de Viajes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">Panel de Administración</h1>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">
                            👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                        </span>
                        <a href="cambiar_password.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium">
                            🔑 Cambiar Contraseña
                        </a>
                        <a href="logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium">
                            Cerrar Sesión
                        </a>
                        <a href="viaje_form.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            + Nuevo Viaje
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-gray-500 text-sm font-medium">Total Viajes</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo count($viajes); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-gray-500 text-sm font-medium">Viajes Activos</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                <?php echo count(array_filter($viajes, fn($v) => $v['activo'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-gray-500 text-sm font-medium">Total Días</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                <?php echo array_sum(array_column($viajes, 'total_dias')); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Viajes Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Mis Viajes</h2>
                </div>
                
                <?php if (empty($viajes)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay viajes</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo viaje.</p>
                    <div class="mt-6">
                        <a href="viaje_form.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            + Crear Viaje
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Viaje</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fechas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Días</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($viajes as $viaje): 
                                $fecha_inicio = new DateTime($viaje['fecha_inicio']);
                                $fecha_fin = new DateTime($viaje['fecha_fin']);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($viaje['titulo']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($viaje['slug']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $fecha_inicio->format('d/m/Y'); ?></div>
                                    <div class="text-sm text-gray-500">a <?php echo $fecha_fin->format('d/m/Y'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $viaje['total_dias']; ?> días
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($viaje['activo']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="../index.php?viaje=<?php echo urlencode($viaje['slug']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3">Ver</a>
                                    <a href="viaje_form.php?id=<?php echo $viaje['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                    <a href="dias_list.php?viaje_id=<?php echo $viaje['id']; ?>" class="text-purple-600 hover:text-purple-900 mr-3">Días</a>
                                    <a href="alojamientos_list.php?viaje_id=<?php echo $viaje['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Alojamiento</a>
                                    <a href="contactos_list.php?viaje_id=<?php echo $viaje['id']; ?>" class="text-orange-600 hover:text-orange-900 mr-3">Contactos</a>
                                    <a href="viaje_delete.php?id=<?php echo $viaje['id']; ?>" onclick="return confirm('¿Estás seguro de eliminar este viaje?')" class="text-red-600 hover:text-red-900">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>