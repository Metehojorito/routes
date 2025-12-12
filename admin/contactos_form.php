<?php
require_once 'auth.php';
require_once '../config/database.php';

$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;
$contacto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();
$error = '';
$success = '';

// Obtener viaje
$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();
if (!$viaje) die("Viaje no encontrado");

// Si estamos editando, obtener el contacto
$contacto_edit = null;
if ($contacto_id > 0) {
    $stmt = $db->prepare("SELECT * FROM contactos_emergencia WHERE id = ? AND viaje_id = ?");
    $stmt->execute([$contacto_id, $viaje_id]);
    $contacto_edit = $stmt->fetch();
    if (!$contacto_edit) {
        die("Contacto no encontrado");
    }
}

// Obtener contactos existentes
$stmt = $db->prepare("SELECT * FROM contactos_emergencia WHERE viaje_id = ? ORDER BY orden");
$stmt->execute([$viaje_id]);
$contactos = $stmt->fetchAll();

// Procesar crear/actualizar contacto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $icono = trim($_POST['icono'] ?? 'emergency');
    
    if (empty($nombre) || empty($telefono)) {
        $error = "El nombre y teléfono son obligatorios";
    } else {
        try {
            if ($contacto_id > 0) {
                // Actualizar contacto existente
                $stmt = $db->prepare("
                    UPDATE contactos_emergencia 
                    SET nombre = ?, telefono = ?, descripcion = ?, icono = ?
                    WHERE id = ? AND viaje_id = ?
                ");
                $stmt->execute([$nombre, $telefono, $descripcion, $icono, $contacto_id, $viaje_id]);
                $success = "Contacto actualizado correctamente";
                
                // Recargar el contacto editado
                $stmt = $db->prepare("SELECT * FROM contactos_emergencia WHERE id = ? AND viaje_id = ?");
                $stmt->execute([$contacto_id, $viaje_id]);
                $contacto_edit = $stmt->fetch();
            } else {
                // Crear nuevo contacto
                // Obtener siguiente orden
                $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden FROM contactos_emergencia WHERE viaje_id = ?");
                $stmt->execute([$viaje_id]);
                $orden = $stmt->fetch()['nuevo_orden'];
                
                $stmt = $db->prepare("
                    INSERT INTO contactos_emergencia (viaje_id, nombre, telefono, descripcion, icono, orden)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$viaje_id, $nombre, $telefono, $descripcion, $icono, $orden]);
                $success = "Contacto añadido correctamente";
            }
            
            // Recargar contactos
            $stmt = $db->prepare("SELECT * FROM contactos_emergencia WHERE viaje_id = ? ORDER BY orden");
            $stmt->execute([$viaje_id]);
            $contactos = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

// Procesar eliminar
if (isset($_GET['delete'])) {
    $contacto_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM contactos_emergencia WHERE id = ? AND viaje_id = ?");
    $stmt->execute([$contacto_id, $viaje_id]);
    header("Location: contactos_form.php?viaje_id=$viaje_id");
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
        .icon-option:hover { transform: scale(1.1); }
        .icon-option.selected { background: #3b82f6; color: white; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center">
                    <a href="dias_list.php?viaje_id=<?php echo $viaje_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Contactos de Emergencia</h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($viaje['titulo']); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

            <!-- Añadir/Editar contacto -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <?php echo $contacto_edit ? 'Editar Contacto' : 'Añadir Nuevo Contacto'; ?>
                </h2>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                            <input type="text" name="nombre" required
                                   value="<?php echo $contacto_edit ? htmlspecialchars($contacto_edit['nombre']) : ''; ?>"
                                   placeholder="Ej: Emergencias"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono *</label>
                            <input type="text" name="telefono" required
                                   value="<?php echo $contacto_edit ? htmlspecialchars($contacto_edit['telefono']) : ''; ?>"
                                   placeholder="112, +31 10 750 2520..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <input type="text" name="descripcion"
                               value="<?php echo $contacto_edit ? htmlspecialchars($contacto_edit['descripcion']) : ''; ?>"
                               placeholder="policía, bomberos, ambulancia..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Icono Material</label>
                        <div class="flex gap-2">
                            <div class="flex-1 relative">
                                <input type="text" id="iconoInput" name="icono" 
                                       value="<?php echo $contacto_edit ? htmlspecialchars($contacto_edit['icono']) : 'emergency'; ?>"
                                       placeholder="emergency, local_police..."
                                       class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="absolute right-3 top-2 flex items-center justify-center w-8 h-8 bg-gray-100 rounded">
                                    <span id="iconoPreview" class="material-symbols-outlined text-gray-700">
                                        <?php echo $contacto_edit ? htmlspecialchars($contacto_edit['icono']) : 'emergency'; ?>
                                    </span>
                                </div>
                            </div>
                            <button type="button" id="btnOpenIconPicker" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md font-medium transition">
                                Elegir icono
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Haz clic en "Elegir icono" para ver opciones visuales
                        </p>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <?php if ($contacto_edit): ?>
                        <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>" 
                           class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-md font-medium transition">
                            Cancelar
                        </a>
                        <?php else: ?>
                        <div></div>
                        <?php endif; ?>
                        
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                            <?php echo $contacto_edit ? 'Actualizar Contacto' : 'Añadir Contacto'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de contactos -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Contactos Guardados (<?php echo count($contactos); ?>)
                    </h2>
                </div>
                
                <?php if (empty($contactos)): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay contactos</h3>
                    <p class="mt-1 text-sm text-gray-500">Añade números de emergencia, hotel, etc.</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($contactos as $contacto): ?>
                    <div class="px-6 py-4 hover:bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
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
                            <a href="contactos_form.php?viaje_id=<?php echo $viaje_id; ?>&id=<?php echo $contacto['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-indigo-50 transition">
                                Editar
                            </a>
                            <a href="?viaje_id=<?php echo $viaje_id; ?>&delete=<?php echo $contacto['id']; ?>" 
                               onclick="return confirm('¿Eliminar este contacto?')"
                               class="text-red-600 hover:text-red-900 text-sm font-medium px-3 py-1.5 rounded hover:bg-red-50 transition">
                                Eliminar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal de Selector de Iconos -->
    <div id="iconModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[85vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Selecciona un icono</h3>
                <button id="btnCloseModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-3 border-b border-gray-200">
                <input type="text" id="iconSearch" placeholder="Buscar icono... (ej: phone, emergency, home)" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div id="iconGrid" class="grid grid-cols-8 gap-2"></div>
                <div id="noResults" class="hidden text-center py-12 text-gray-500">
                    <span class="material-symbols-outlined text-5xl mb-2">search_off</span>
                    <p>No se encontraron iconos</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Lista completa de iconos Material Symbols organizados por categoría
        const icons = {
            emergencia: ['emergency', 'e911_emergency', 'emergency_home', 'emergency_share', 'sos', 'medical_services', 'healing', 'vaccines', 'medication', 'health_and_safety'],
            policia: ['local_police', 'security', 'shield', 'shield_person', 'verified_user', 'gavel', 'policy'],
            hospital: ['local_hospital', 'emergency_home', 'medical_services', 'health_and_safety', 'favorite', 'monitor_heart', 'ecg_heart'],
            bomberos: ['local_fire_department', 'fire_truck', 'fire_extinguisher', 'fireplace'],
            telefono: ['phone', 'call', 'phone_in_talk', 'phone_enabled', 'contact_phone', 'phonelink_ring', 'ring_volume', 'call_end', 'phone_callback', 'phone_forwarded', 'phone_missed', 'phone_paused'],
            soporte: ['support_agent', 'headset_mic', 'contact_support', 'help', 'help_center', 'live_help'],
            lugares: ['home', 'hotel', 'apartment', 'cottage', 'villa', 'house', 'cabin', 'location_city', 'business', 'storefront', 'store', 'factory', 'warehouse', 'domain'],
            institucional: ['account_balance', 'museum', 'church', 'synagogue', 'mosque', 'temple_buddhist', 'temple_hindu'],
            transporte: ['local_taxi', 'airport_shuttle', 'train', 'subway', 'directions_bus', 'directions_car', 'directions_boat', 'flight', 'two_wheeler', 'electric_scooter', 'electric_bike', 'pedal_bike'],
            restaurante: ['restaurant', 'local_cafe', 'local_bar', 'local_dining', 'lunch_dining', 'dinner_dining', 'breakfast_dining', 'ramen_dining', 'local_pizza', 'fastfood', 'coffee', 'liquor', 'wine_bar'],
            compras: ['shopping_cart', 'shopping_bag', 'storefront', 'local_mall', 'local_grocery_store', 'local_convenience_store', 'sell', 'loyalty', 'redeem'],
            entretenimiento: ['theater_comedy', 'sports_esports', 'sports_soccer', 'casino', 'sports_bar', 'celebration', 'attractions', 'festival', 'nightlife', 'pool', 'spa'],
            naturaleza: ['park', 'forest', 'landscape', 'terrain', 'water', 'beach_access', 'sailing', 'surfing', 'hiking', 'nature', 'nature_people'],
            ubicacion: ['location_on', 'place', 'map', 'my_location', 'near_me', 'explore', 'navigation', 'pin_drop', 'add_location', 'edit_location', 'gps_fixed', 'gps_not_fixed'],
            informacion: ['info', 'info_outline', 'help', 'help_outline', 'announcement', 'campaign', 'notifications', 'notifications_active'],
            personas: ['person', 'people', 'group', 'groups', 'family_restroom', 'elderly', 'accessible', 'child_care', 'face', 'badge', 'supervisor_account'],
            trabajo: ['work', 'business_center', 'engineering', 'construction', 'handyman', 'plumbing', 'electrical_services'],
            educacion: ['school', 'local_library', 'menu_book', 'auto_stories', 'import_contacts'],
            finanzas: ['account_balance', 'account_balance_wallet', 'payments', 'credit_card', 'paid', 'attach_money', 'currency_exchange', 'atm', 'money'],
            tecnologia: ['computer', 'phone_android', 'phone_iphone', 'tablet', 'watch', 'headphones', 'router', 'wifi', 'signal_cellular_alt'],
            tiempo: ['schedule', 'access_time', 'alarm', 'timer', 'hourglass_empty', 'update', 'history', 'watch_later'],
            clima: ['wb_sunny', 'wb_cloudy', 'cloud', 'thunderstorm', 'ac_unit', 'thermostat', 'air', 'wind_power'],
            correo: ['mail', 'email', 'send', 'inbox', 'drafts', 'mark_email_read', 'forward_to_inbox'],
            configuracion: ['settings', 'tune', 'build', 'construction', 'handyman', 'home_repair_service'],
            varios: ['star', 'favorite', 'bookmark', 'label', 'flag', 'push_pin', 'priority_high', 'grade', 'verified', 'check_circle', 'cancel', 'error', 'warning', 'lightbulb', 'emoji_objects', 'key', 'lock', 'lock_open', 'visibility', 'visibility_off']
        };

        const modal = document.getElementById('iconModal');
        const btnOpen = document.getElementById('btnOpenIconPicker');
        const btnClose = document.getElementById('btnCloseModal');
        const iconoInput = document.getElementById('iconoInput');
        const iconoPreview = document.getElementById('iconoPreview');
        const iconGrid = document.getElementById('iconGrid');
        const iconSearch = document.getElementById('iconSearch');
        const noResults = document.getElementById('noResults');

        let allIcons = [];
        
        // Convertir objeto de iconos a array plano
        Object.values(icons).forEach(category => {
            allIcons = [...allIcons, ...category];
        });
        
        // Eliminar duplicados
        allIcons = [...new Set(allIcons)];

        // Función para renderizar iconos
        function renderIcons(iconsToRender) {
            iconGrid.innerHTML = '';
            
            if (iconsToRender.length === 0) {
                iconGrid.classList.add('hidden');
                noResults.classList.remove('hidden');
                return;
            }
            
            iconGrid.classList.remove('hidden');
            noResults.classList.add('hidden');
            
            iconsToRender.forEach(icon => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'icon-option flex flex-col items-center justify-center p-2 rounded-lg border-2 border-gray-200 hover:border-blue-500 transition cursor-pointer';
                btn.dataset.icon = icon;
                btn.innerHTML = `<span class="material-symbols-outlined text-2xl text-gray-700">${icon}</span>`;
                btn.title = icon;
                
                btn.addEventListener('click', () => selectIcon(icon));
                iconGrid.appendChild(btn);
            });
        }

        // Función para seleccionar icono
        function selectIcon(icon) {
            iconoInput.value = icon;
            iconoPreview.textContent = icon;
            
            // Marcar como seleccionado
            document.querySelectorAll('.icon-option').forEach(opt => {
                if (opt.dataset.icon === icon) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
            
            setTimeout(() => modal.classList.add('hidden'), 150);
        }

        // Abrir modal
        btnOpen.addEventListener('click', () => {
            modal.classList.remove('hidden');
            iconSearch.value = '';
            renderIcons(allIcons);
            iconSearch.focus();
            
            // Marcar icono actual
            setTimeout(() => {
                const currentIcon = iconoInput.value;
                document.querySelectorAll('.icon-option').forEach(opt => {
                    if (opt.dataset.icon === currentIcon) {
                        opt.classList.add('selected');
                        opt.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                });
            }, 100);
        });

        // Cerrar modal
        btnClose.addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });

        // Buscador
        iconSearch.addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase().trim();
            if (search === '') {
                renderIcons(allIcons);
            } else {
                const filtered = allIcons.filter(icon => icon.includes(search));
                renderIcons(filtered);
            }
        });

        // Actualizar preview cuando se escribe manualmente
        iconoInput.addEventListener('input', (e) => {
            iconoPreview.textContent = e.target.value || 'emergency';
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>