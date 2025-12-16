<?php
require_once 'auth.php';
require_once '../config/database.php';

$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;
$db = getDB();

// Procesar actualización de orden (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    $orders = json_decode($_POST['orders'], true);
    
    try {
        foreach ($orders as $id => $orden) {
            $stmt = $db->prepare("UPDATE dias_viaje SET orden = ? WHERE id = ?");
            $stmt->execute([$orden, $id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="icon" href="images/favicon-32x32.png" sizes="32x32" type="image/png">
	<link rel="icon" href="images/favicon-16x16.png" sizes="16x16" type="image/png">
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .dragging { opacity: 0.5; }
        .drag-handle { cursor: grab; touch-action: none; }
        .drag-handle:active { cursor: grabbing; }
        
        @media (max-width: 768px) {
            .drag-handle { padding: 0.5rem; }
            .dia-item { padding: 0.75rem !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center min-w-0 w-full sm:w-auto">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 mr-2 sm:mr-4 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 truncate">Días del Viaje</h1>
                            <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
                        </div>
                    </div>
                    <a href="dia_form.php?viaje_id=<?php echo $viaje_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition w-full sm:w-auto text-center whitespace-nowrap">
                        + Nuevo Día
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
            <?php if (isset($_SESSION['import_success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['import_success']); unset($_SESSION['import_success']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['import_error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-red-400">error</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['import_error']); unset($_SESSION['import_error']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-3 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900">
                            Itinerario - <?php echo count($dias); ?> días
                        </h2>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            <p class="text-xs sm:text-sm text-gray-500">Arrastra para reordenar</p>
                            <div class="flex gap-1 sm:gap-2">
                                <a href="alojamiento_form.php?viaje_id=<?php echo $viaje_id; ?>" class="text-xs sm:text-sm bg-green-600 hover:bg-green-700 text-white px-2 sm:px-3 py-1 sm:py-1.5 rounded font-medium transition whitespace-nowrap">
                                    🏠 Aloj.
                                </a>
                                <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>" class="text-xs sm:text-sm bg-red-600 hover:bg-red-700 text-white px-2 sm:px-3 py-1 sm:py-1.5 rounded font-medium transition whitespace-nowrap">
                                    🚨 Emerg.
                                </a>
                            </div>
                        </div>
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
                <div id="diasContainer" class="divide-y divide-gray-200">
                    <?php foreach ($dias as $dia): 
                        $fecha = new DateTime($dia['fecha']);
                        $mes = $meses[(int)$fecha->format('n')];
                        $dia_num = $fecha->format('d');
                        $dia_semana = $dias_semana[(int)$fecha->format('w')];
                    ?>
                    <div class="dia-item px-3 sm:px-6 py-3 sm:py-4 hover:bg-gray-50 transition" data-id="<?php echo $dia['id']; ?>">
                        <div class="flex flex-col sm:flex-row items-start gap-3">
                            <!-- Handle y fecha -->
                            <div class="flex items-center space-x-3 sm:space-x-4 w-full sm:w-auto">
                                <div class="drag-handle text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-xl sm:text-2xl">drag_indicator</span>
                                </div>
                                <div class="flex-shrink-0 w-14 h-14 sm:w-16 sm:h-16 bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                                    <span class="text-xs font-semibold text-blue-600"><?php echo strtoupper($mes); ?></span>
                                    <span class="text-xl sm:text-2xl font-bold text-blue-700"><?php echo $dia_num; ?></span>
                                </div>
                                
                                <!-- Info principal -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 break-words"><?php echo htmlspecialchars($dia['titulo']); ?></h3>
                                        <span class="text-xs sm:text-sm text-gray-500 whitespace-nowrap">• Día <?php echo $dia['numero_dia']; ?></span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-600 mt-1"><?php echo $dia_semana; ?>, <?php echo $fecha->format('d/m/Y'); ?></p>
                                    
                                    <!-- Badges -->
                                    <div class="flex flex-wrap gap-1 sm:gap-2 mt-2">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 orden-badge whitespace-nowrap">
                                            #<?php echo $dia['orden']; ?>
                                        </span>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800 whitespace-nowrap">
                                            <?php echo $dia['total_secciones']; ?> secc.
                                        </span>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 whitespace-nowrap">
                                            <?php echo $dia['total_actividades']; ?> act.
                                        </span>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $dia['visible'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> whitespace-nowrap">
                                            <?php echo $dia['visible'] ? 'Visible' : 'Oculto'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="flex items-center space-x-1 ml-auto">
                                <a href="../dia.php?viaje=<?php echo urlencode($viaje['slug']); ?>&dia=<?php echo $dia['numero_dia']; ?>" 
                                   target="_blank" 
                                   class="text-blue-600 hover:text-blue-900 p-1.5 sm:p-2 rounded hover:bg-blue-50 transition"
                                   title="Ver">
                                    <span class="material-symbols-outlined text-lg sm:text-xl">visibility</span>
                                </a>
                                <a href="secciones_list.php?dia_id=<?php echo $dia['id']; ?>" 
                                   class="text-purple-600 hover:text-purple-900 p-1.5 sm:p-2 rounded hover:bg-purple-50 transition"
                                   title="Secciones">
                                    <span class="material-symbols-outlined text-lg sm:text-xl">segment</span>
                                </a>
                                <a href="actividades_list.php?dia_id=<?php echo $dia['id']; ?>" 
                                   class="text-green-600 hover:text-green-900 p-1.5 sm:p-2 rounded hover:bg-green-50 transition"
                                   title="Actividades">
                                    <span class="material-symbols-outlined text-lg sm:text-xl">list</span>
                                </a>
                                <a href="dia_form.php?id=<?php echo $dia['id']; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900 p-1.5 sm:p-2 rounded hover:bg-indigo-50 transition"
                                   title="Editar">
                                    <span class="material-symbols-outlined text-lg sm:text-xl">edit</span>
                                </a>
                                <a href="dia_delete.php?id=<?php echo $dia['id']; ?>" 
                                   onclick="return confirm('¿Eliminar este día y todas sus actividades?')" 
                                   class="text-red-600 hover:text-red-900 p-1.5 sm:p-2 rounded hover:bg-red-50 transition"
                                   title="Eliminar">
                                    <span class="material-symbols-outlined text-lg sm:text-xl">delete</span>
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

    <script>
    // Sistema de drag & drop mejorado para móvil y desktop
    const container = document.getElementById('diasContainer');
    let draggedElement = null;
    let touchStartY = 0;
    let isDragging = false;

    if (container) {
        const items = container.querySelectorAll('.dia-item');
        
        items.forEach(item => {
            const handle = item.querySelector('.drag-handle');
            
            // Desktop: Mouse events
            handle.addEventListener('mousedown', () => {
                item.draggable = true;
                isDragging = true;
            });
            
            handle.addEventListener('mouseup', () => {
                item.draggable = false;
                isDragging = false;
            });
            
            item.addEventListener('dragstart', (e) => {
                draggedElement = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                item.draggable = false;
                isDragging = false;
            });
            
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = getDragAfterElement(container, e.clientY);
                if (afterElement == null) {
                    container.appendChild(draggedElement);
                } else {
                    container.insertBefore(draggedElement, afterElement);
                }
            });
            
            // Mobile: Touch events
            handle.addEventListener('touchstart', (e) => {
                if (e.touches.length > 1) return;
                e.preventDefault();
                
                draggedElement = item;
                touchStartY = e.touches[0].clientY;
                item.classList.add('dragging');
                isDragging = true;
            }, { passive: false });
            
            handle.addEventListener('touchmove', (e) => {
                if (!isDragging || !draggedElement) return;
                e.preventDefault();
                
                const touch = e.touches[0];
                const currentY = touch.clientY;
                
                item.style.transform = `translateY(${currentY - touchStartY}px)`;
                
                const afterElement = getDragAfterElementTouch(container, currentY);
                if (afterElement == null) {
                    container.appendChild(draggedElement);
                } else {
                    container.insertBefore(draggedElement, afterElement);
                }
            }, { passive: false });
            
            handle.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                
                item.classList.remove('dragging');
                item.style.transform = '';
                isDragging = false;
                
                updateOrder();
                draggedElement = null;
            }, { passive: false });
        });
        
        container.addEventListener('dragend', () => {
            updateOrder();
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.dia-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    function getDragAfterElementTouch(container, y) {
        const draggableElements = [...container.querySelectorAll('.dia-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            if (child === draggedElement) return closest;
            
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateOrder() {
        const items = container.querySelectorAll('.dia-item');
        const orders = {};
        
        items.forEach((item, index) => {
            const id = item.dataset.id;
            orders[id] = index + 1;
            
            const badge = item.querySelector('.orden-badge');
            if (badge) badge.textContent = '#' + (index + 1);
        });
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_order&orders=${encodeURIComponent(JSON.stringify(orders))}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error al actualizar orden:', data.error);
                alert('Error al guardar el orden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar el orden');
        });
    }
    </script>
</body>
</html>