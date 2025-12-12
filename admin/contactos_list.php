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
            $stmt = $db->prepare("UPDATE contactos_emergencia SET orden = ? WHERE id = ?");
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
if (!$viaje) die("Viaje no encontrado");

// Obtener contactos
$stmt = $db->prepare("SELECT * FROM contactos_emergencia WHERE viaje_id = ? ORDER BY orden");
$stmt->execute([$viaje_id]);
$contactos = $stmt->fetchAll();

// Procesar eliminar
if (isset($_GET['delete'])) {
    $contacto_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM contactos_emergencia WHERE id = ? AND viaje_id = ?");
    $stmt->execute([$contacto_id, $viaje_id]);
    header("Location: contactos_list.php?viaje_id=$viaje_id&deleted=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos de Emergencia - Admin</title>
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
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Contactos de Emergencia</h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
                        </div>
                    </div>
                    <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        + Añadir Contacto
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <p class="text-sm text-green-700">Contacto eliminado correctamente</p>
            </div>
            <?php endif; ?>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Contactos Guardados (<?php echo count($contactos); ?>)
                        </h2>
                        <p class="text-sm text-gray-500">Arrastra para reordenar</p>
                    </div>
                </div>
                
                <?php if (empty($contactos)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay contactos</h3>
                    <p class="mt-1 text-sm text-gray-500">Añade números de emergencia, policía, hotel, etc.</p>
                    <div class="mt-6">
                        <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            + Añadir Contacto
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div id="contactosContainer" class="divide-y divide-gray-200">
                    <?php foreach ($contactos as $contacto): ?>
                    <div class="contacto-item px-6 py-4 hover:bg-gray-50 flex items-center justify-between" data-id="<?php echo $contacto['id']; ?>">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="drag-handle text-gray-400 hover:text-gray-600">
                                <span class="material-symbols-outlined">drag_indicator</span>
                            </div>
                            <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-red-600">
                                    <?php echo htmlspecialchars($contacto['icono']); ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($contacto['nombre']); ?></h3>
                                <p class="text-sm text-gray-600">📞 <?php echo htmlspecialchars($contacto['telefono']); ?></p>
                                <?php if ($contacto['descripcion']): ?>
                                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($contacto['descripcion']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded orden-badge">
                                Orden: <?php echo $contacto['orden']; ?>
                            </span>
                            <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>&id=<?php echo $contacto['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900 p-2 rounded hover:bg-indigo-50 transition"
                               title="Editar">
                                <span class="material-symbols-outlined">edit</span>
                            </a>
                            <a href="?viaje_id=<?php echo $viaje_id; ?>&delete=<?php echo $contacto['id']; ?>" 
                               onclick="return confirm('¿Eliminar este contacto?')"
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

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">💡 Consejo</h3>
                <p class="text-sm text-blue-800">
                    Los contactos aparecerán en el menú lateral de la parte pública. Añade emergencias locales (112, policía, etc.), 
                    teléfono del hotel, embajada, o cualquier contacto útil durante el viaje.
                </p>
            </div>
        </main>
    </div>

    <script>
    // Sistema de drag & drop para reordenar contactos
    const container = document.getElementById('contactosContainer');
    let draggedElement = null;

    if (container) {
        const items = container.querySelectorAll('.contacto-item');
        
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
        
        container.addEventListener('dragend', () => {
            updateOrder();
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.contacto-item:not(.dragging)')];
        
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
        const items = container.querySelectorAll('.contacto-item');
        const orders = {};
        
        items.forEach((item, index) => {
            const id = item.dataset.id;
            orders[id] = index + 1;
            
            // Actualizar el número visual
            const badge = item.querySelector('.orden-badge');
            if (badge) badge.textContent = 'Orden: ' + (index + 1);
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