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

// Procesar actualización de orden (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    $orders = json_decode($_POST['orders'], true);
    
    try {
        foreach ($orders as $id => $orden) {
            $stmt = $db->prepare("UPDATE secciones_dia SET orden = ? WHERE id = ?");
            $stmt->execute([$orden, $id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Procesar crear sección rápida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_seccion'])) {
    $titulo = trim($_POST['titulo']);
    if (!empty($titulo)) {
        $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden FROM secciones_dia WHERE dia_id = ?");
        $stmt->execute([$dia_id]);
        $orden = $stmt->fetch()['nuevo_orden'];
        
        $stmt = $db->prepare("INSERT INTO secciones_dia (dia_id, titulo, orden) VALUES (?, ?, ?)");
        $stmt->execute([$dia_id, $titulo, $orden]);
        header("Location: secciones_list.php?dia_id=$dia_id");
        exit;
    }
}

// Procesar eliminar sección
if (isset($_GET['delete_seccion'])) {
    $seccion_id = (int)$_GET['delete_seccion'];
    $stmt = $db->prepare("DELETE FROM secciones_dia WHERE id = ?");
    $stmt->execute([$seccion_id]);
    header("Location: secciones_list.php?dia_id=$dia_id");
    exit;
}

// Obtener secciones
$stmt = $db->prepare("
    SELECT s.*, COUNT(a.id) as total_actividades
    FROM secciones_dia s
    LEFT JOIN actividades a ON a.seccion_id = s.id
    WHERE s.dia_id = ?
    GROUP BY s.id
    ORDER BY s.orden
");
$stmt->execute([$dia_id]);
$secciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secciones - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .dragging { opacity: 0.5; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
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
                            <h1 class="text-3xl font-bold text-gray-900">Secciones del Día</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($dia['titulo']); ?></p>
                        </div>
                    </div>
                    <a href="actividades_list.php?dia_id=<?php echo $dia_id; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        Ver Actividades
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Crear sección rápida -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Crear Nueva Sección</h2>
                <form method="POST" class="flex gap-3">
                    <input type="hidden" name="nueva_seccion" value="1">
                    <input type="text" name="titulo" placeholder="Ej: Mañana, Tarde, Noche, Opcional..." required
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition">
                        Crear
                    </button>
                </form>
                <p class="text-sm text-gray-500 mt-2">Las secciones dividen el día en partes (Mañana, Tarde, etc.)</p>
            </div>

            <!-- Lista de secciones -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Secciones (<?php echo count($secciones); ?>)
                        </h2>
                        <p class="text-sm text-gray-500">Arrastra para reordenar</p>
                    </div>
                </div>
                
                <?php if (empty($secciones)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay secciones</h3>
                    <p class="mt-1 text-sm text-gray-500">Las secciones son opcionales. Puedes crear actividades sin secciones.</p>
                </div>
                <?php else: ?>
                <div id="seccionesContainer" class="divide-y divide-gray-200">
                    <?php foreach ($secciones as $seccion): ?>
                    <div class="seccion-item px-6 py-4 hover:bg-gray-50 flex items-center justify-between" data-id="<?php echo $seccion['id']; ?>">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="drag-handle text-gray-400 hover:text-gray-600">
                                <span class="material-symbols-outlined">drag_indicator</span>
                            </div>
                            <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <span class="text-lg font-bold text-purple-600"><?php echo $seccion['orden']; ?></span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($seccion['titulo']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo $seccion['total_actividades']; ?> actividades</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="seccion_form.php?dia_id=<?php echo $dia_id; ?>&id=<?php echo $seccion['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900 p-2 rounded hover:bg-indigo-50 transition"
                               title="Editar">
                                <span class="material-symbols-outlined">edit</span>
                            </a>
                            <a href="?dia_id=<?php echo $dia_id; ?>&delete_seccion=<?php echo $seccion['id']; ?>" 
                               onclick="return confirm('¿Eliminar esta sección? Las actividades NO se eliminarán.')" 
                               class="text-red-600 hover:text-red-900 p-2 rounded hover:bg-red-50 transition"
                               title="Eliminar">
                                <span class="material-symbols-outlined">delete</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 text-center">
                <a href="actividades_list.php?dia_id=<?php echo $dia_id; ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                    Continuar a Actividades →
                </a>
            </div>
        </main>
    </div>

    <script>
    // Sistema de drag & drop para reordenar secciones
    const container = document.getElementById('seccionesContainer');
    let draggedElement = null;

    if (container) {
        const items = container.querySelectorAll('.seccion-item');
        
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
        });
        
        // Guardar orden cuando termina el drag
        container.addEventListener('dragend', () => {
            updateOrder();
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.seccion-item:not(.dragging)')];
        
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
        const items = container.querySelectorAll('.seccion-item');
        const orders = {};
        
        items.forEach((item, index) => {
            const id = item.dataset.id;
            orders[id] = index + 1;
            
            // Actualizar el número visual
            const badge = item.querySelector('.bg-purple-100 span');
            if (badge) badge.textContent = index + 1;
        });
        
        // Enviar al servidor
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