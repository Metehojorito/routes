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

// Procesar actualización de orden y sección (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_order') {
        $updates = json_decode($_POST['updates'], true);
        
        try {
            foreach ($updates as $update) {
                $stmt = $db->prepare("UPDATE actividades SET orden = ?, seccion_id = ? WHERE id = ?");
                $stmt->execute([$update['orden'], $update['seccion_id'], $update['id']]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

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

// Agrupar actividades por sección
$actividades_agrupadas = [];
$actividades_sin_seccion = [];

foreach ($actividades as $actividad) {
    if ($actividad['seccion_id']) {
        if (!isset($actividades_agrupadas[$actividad['seccion_id']])) {
            $actividades_agrupadas[$actividad['seccion_id']] = [
                'titulo' => $actividad['seccion_titulo'],
                'actividades' => []
            ];
        }
        $actividades_agrupadas[$actividad['seccion_id']]['actividades'][] = $actividad;
    } else {
        $actividades_sin_seccion[] = $actividad;
    }
}
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
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="icon" href="images/favicon-32x32.png" sizes="32x32" type="image/png">
	<link rel="icon" href="images/favicon-16x16.png" sizes="16x16" type="image/png">
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .dragging { opacity: 0.5; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
        .drag-over { border: 2px dashed #3b82f6; background-color: #eff6ff; }
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
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Actividades (<?php echo count($actividades); ?>)
                        </h2>
                        <p class="text-sm text-gray-500">Arrastra para reordenar o mover entre secciones</p>
                    </div>
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
                <div id="actividadesContainer">
                    <!-- Actividades SIN sección -->
                    <?php if (!empty($actividades_sin_seccion)): ?>
                    <div class="seccion-container border-b border-gray-200" data-seccion-id="">
                        <div class="px-6 py-3 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-600 uppercase">Sin sección</h3>
                        </div>
                        <div class="actividades-list">
                            <?php foreach ($actividades_sin_seccion as $actividad): ?>
                            <?php include 'partials/actividad_item.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Actividades CON sección -->
                    <?php foreach ($secciones as $seccion): ?>
                        <?php if (isset($actividades_agrupadas[$seccion['id']])): ?>
                        <div class="seccion-container border-b border-gray-200" data-seccion-id="<?php echo $seccion['id']; ?>">
                            <div class="px-6 py-3 bg-gray-50">
                                <h3 class="text-sm font-semibold text-gray-600 uppercase"><?php echo htmlspecialchars($seccion['titulo']); ?></h3>
                            </div>
                            <div class="actividades-list">
                                <?php foreach ($actividades_agrupadas[$seccion['id']]['actividades'] as $actividad): ?>
                                <?php include 'partials/actividad_item.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
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

    <script>
    // Sistema de drag & drop para actividades
    let draggedElement = null;

    function initDragAndDrop() {
        const items = document.querySelectorAll('.actividad-item');
        
        items.forEach(item => {
            const handle = item.querySelector('.drag-handle');
            
            handle.addEventListener('mousedown', () => {
                item.draggable = true;
            });
            
            handle.addEventListener('mouseup', () => {
                item.draggable = false;
            });
            
            item.addEventListener('dragstart', (e) => {
                draggedElement = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                item.draggable = false;
                
                // Quitar highlight de todas las secciones
                document.querySelectorAll('.seccion-container').forEach(sec => {
                    sec.classList.remove('drag-over');
                });
                
                updateOrder();
            });
        });
        
        // Permitir drop en las listas de actividades
        const lists = document.querySelectorAll('.actividades-list');
        lists.forEach(list => {
            list.addEventListener('dragover', (e) => {
                e.preventDefault();
                const seccionContainer = list.closest('.seccion-container');
                seccionContainer.classList.add('drag-over');
                
                const afterElement = getDragAfterElement(list, e.clientY);
                if (afterElement == null) {
                    list.appendChild(draggedElement);
                } else {
                    list.insertBefore(draggedElement, afterElement);
                }
            });
            
            list.addEventListener('dragleave', (e) => {
                const seccionContainer = list.closest('.seccion-container');
                if (!seccionContainer.contains(e.relatedTarget)) {
                    seccionContainer.classList.remove('drag-over');
                }
            });
            
            list.addEventListener('drop', (e) => {
                e.preventDefault();
                const seccionContainer = list.closest('.seccion-container');
                seccionContainer.classList.remove('drag-over');
            });
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.actividad-item:not(.dragging)')];
        
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

    function updateOrder() {
        const updates = [];
        
        document.querySelectorAll('.seccion-container').forEach(seccionContainer => {
            const seccionId = seccionContainer.dataset.seccionId || null;
            const items = seccionContainer.querySelectorAll('.actividad-item');
            
            items.forEach((item, index) => {
                const id = item.dataset.id;
                updates.push({
                    id: parseInt(id),
                    seccion_id: seccionId ? parseInt(seccionId) : null,
                    orden: index + 1
                });
                
                // Actualizar número visual
                const badge = item.querySelector('.orden-badge');
                if (badge) badge.textContent = index + 1;
            });
        });
        
        // Enviar al servidor
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_order&updates=${encodeURIComponent(JSON.stringify(updates))}`
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

    // Inicializar cuando carga la página
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop();
    });
    </script>
</body>
</html>