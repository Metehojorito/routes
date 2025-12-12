<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();
$viaje = null;
$error = '';
$success = '';

// Cargar viaje si es edición
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
    $stmt->execute([$id]);
    $viaje = $stmt->fetch();
    if (!$viaje) {
        die("Viaje no encontrado");
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $imagen_portada = trim($_POST['imagen_portada'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Colores personalizables
    $color_primary = trim($_POST['color_primary'] ?? '#4A90E2');
    $color_secondary = trim($_POST['color_secondary'] ?? '#F5A623');
    $color_bg_light = trim($_POST['color_bg_light'] ?? '#F4F4F8');
    $color_bg_dark = trim($_POST['color_bg_dark'] ?? '#101922');
    $color_card_light = trim($_POST['color_card_light'] ?? '#FFFFFF');
    $color_card_dark = trim($_POST['color_card_dark'] ?? '#1c2c3a');
    
    // Validaciones
    if (empty($titulo)) {
        $error = "El título es obligatorio";
    } elseif (empty($slug)) {
        $error = "El slug es obligatorio";
    } elseif (empty($fecha_inicio) || empty($fecha_fin)) {
        $error = "Las fechas son obligatorias";
    } else {
        // Auto-generar slug si está vacío
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo)));
        }
        
        try {
            if ($id > 0) {
                // Actualizar
                $stmt = $db->prepare("
                    UPDATE viajes SET 
                        titulo = ?, slug = ?, descripcion = ?, 
                        fecha_inicio = ?, fecha_fin = ?, 
                        imagen_portada = ?, activo = ?,
                        color_primary = ?, color_secondary = ?,
                        color_bg_light = ?, color_bg_dark = ?,
                        color_card_light = ?, color_card_dark = ?
                    WHERE id = ?
                ");
                $stmt->execute([$titulo, $slug, $descripcion, $fecha_inicio, $fecha_fin, $imagen_portada, $activo,
                               $color_primary, $color_secondary, $color_bg_light, $color_bg_dark, 
                               $color_card_light, $color_card_dark, $id]);
                $success = "Viaje actualizado correctamente";
                
                // Recargar datos
                $stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
                $stmt->execute([$id]);
                $viaje = $stmt->fetch();
            } else {
                // Insertar
                $stmt = $db->prepare("
                    INSERT INTO viajes (titulo, slug, descripcion, fecha_inicio, fecha_fin, imagen_portada, activo,
                                       color_primary, color_secondary, color_bg_light, color_bg_dark,
                                       color_card_light, color_card_dark) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$titulo, $slug, $descripcion, $fecha_inicio, $fecha_fin, $imagen_portada, $activo,
                               $color_primary, $color_secondary, $color_bg_light, $color_bg_dark,
                               $color_card_light, $color_card_dark]);
                $id = $db->lastInsertId();
                $success = "Viaje creado correctamente";
                
                // Redirigir a edición
                header("Location: viaje_form.php?id=$id&success=1");
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "El slug ya existe. Por favor usa otro.";
            } else {
                $error = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['success'])) {
    $success = "Viaje creado correctamente";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Editar' : 'Nuevo'; ?> Viaje - Admin</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">
                            <?php echo $id > 0 ? 'Editar Viaje' : 'Nuevo Viaje'; ?>
                        </h1>
                    </div>
                    <?php if ($id > 0): ?>
                    <div class="flex gap-2">
                        <a href="dias_list.php?viaje_id=<?php echo $id; ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            Gestionar Días
                        </a>
                        <a href="../index.php?viaje=<?php echo urlencode($viaje['slug']); ?>" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            Ver Público
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título del Viaje *</label>
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($viaje['titulo'] ?? ''); ?>" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL amigable) *</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($viaje['slug'] ?? ''); ?>" required 
                           pattern="[a-z0-9-]+" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Solo letras minúsculas, números y guiones. Ej: roterdam-2024</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($viaje['descripcion'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio *</label>
                        <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($viaje['fecha_inicio'] ?? ''); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin *</label>
                        <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($viaje['fecha_fin'] ?? ''); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Imagen de Portada</label>
                    <input type="url" name="imagen_portada" value="<?php echo htmlspecialchars($viaje['imagen_portada'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">URL completa de la imagen de fondo para la portada</p>
                </div>

                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🎨 Personalización de Colores</h3>
                    <p class="text-sm text-gray-600 mb-4">Personaliza los colores del viaje para darle una identidad visual única</p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color Principal</label>
                            <div class="flex gap-2">
                                <input type="color" name="color_primary" value="<?php echo htmlspecialchars($viaje['color_primary'] ?? '#4A90E2'); ?>" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars($viaje['color_primary'] ?? '#4A90E2'); ?>" 
                                       readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Iconos principales, enlaces</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color Secundario</label>
                            <div class="flex gap-2">
                                <input type="color" name="color_secondary" value="<?php echo htmlspecialchars($viaje['color_secondary'] ?? '#F5A623'); ?>" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars($viaje['color_secondary'] ?? '#F5A623'); ?>" 
                                       readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Actividades secundarias</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fondo Claro</label>
                            <div class="flex gap-2">
                                <input type="color" name="color_bg_light" value="<?php echo htmlspecialchars($viaje['color_bg_light'] ?? '#F4F4F8'); ?>" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars($viaje['color_bg_light'] ?? '#F4F4F8'); ?>" 
                                       readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Fondo en modo claro</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fondo Oscuro</label>
                            <div class="flex gap-2">
                                <input type="color" name="color_bg_dark" value="<?php echo htmlspecialchars($viaje['color_bg_dark'] ?? '#101922'); ?>" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars($viaje['color_bg_dark'] ?? '#101922'); ?>" 
                                       readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Fondo en modo oscuro</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tarjetas Claro</label>
                            <div class="flex gap-2">
                                <input type="color" name="color_card_light" value="<?php echo htmlspecialchars($viaje['color_card_light'] ?? '#FFFFFF'); ?>" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars($viaje['color_card_light'] ?? '#FFFFFF'); ?>" 
                                       readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Tarjetas en modo claro</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tarjetas Oscuro</label>
                            <div class="flex gap-2">
                                <input type="color" name="color_card_dark" value="<?php echo htmlspecialchars($viaje['color_card_dark'] ?? '#1c2c3a'); ?>" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars($viaje['color_card_dark'] ?? '#1c2c3a'); ?>" 
                                       readonly class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Tarjetas en modo oscuro</p>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800 mb-2"><strong>💡 Plantillas de Colores:</strong></p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="aplicarPreset('tropical')" class="text-xs px-3 py-1.5 bg-cyan-500 text-white rounded hover:bg-cyan-600">
                                🏖️ Tropical
                            </button>
                            <button type="button" onclick="aplicarPreset('montana')" class="text-xs px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700">
                                🏔️ Montaña
                            </button>
                            <button type="button" onclick="aplicarPreset('romantico')" class="text-xs px-3 py-1.5 bg-pink-500 text-white rounded hover:bg-pink-600">
                                💕 Romántico
                            </button>
                            <button type="button" onclick="aplicarPreset('cultural')" class="text-xs px-3 py-1.5 bg-red-600 text-white rounded hover:bg-red-700">
                                🏯 Cultural
                            </button>
                            <button type="button" onclick="aplicarPreset('default')" class="text-xs px-3 py-1.5 bg-gray-600 text-white rounded hover:bg-gray-700">
                                ↺ Por Defecto
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="activo" id="activo" <?php echo ($viaje['activo'] ?? 1) ? 'checked' : ''; ?> 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="activo" class="ml-2 block text-sm text-gray-900">Viaje activo (visible en público)</label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                        <?php echo $id > 0 ? 'Actualizar' : 'Crear'; ?> Viaje
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    // Presets de colores predefinidos
    const presets = {
        tropical: {
            primary: '#00CED1',
            secondary: '#FFD700',
            bg_light: '#F0F8FF',
            bg_dark: '#001F3F',
            card_light: '#FFFFFF',
            card_dark: '#1a3a52'
        },
        montana: {
            primary: '#228B22',
            secondary: '#8B4513',
            bg_light: '#F5F5DC',
            bg_dark: '#2F4F4F',
            card_light: '#FFFFFF',
            card_dark: '#1c2c1c'
        },
        romantico: {
            primary: '#FF1493',
            secondary: '#8B008B',
            bg_light: '#FFF0F5',
            bg_dark: '#2F2F4F',
            card_light: '#FFFFFF',
            card_dark: '#3a2a3a'
        },
        cultural: {
            primary: '#DC143C',
            secondary: '#FFD700',
            bg_light: '#FFFAF0',
            bg_dark: '#1C1C1C',
            card_light: '#FFFFFF',
            card_dark: '#2a2a2a'
        },
        default: {
            primary: '#4A90E2',
            secondary: '#F5A623',
            bg_light: '#F4F4F8',
            bg_dark: '#101922',
            card_light: '#FFFFFF',
            card_dark: '#1c2c3a'
        }
    };

    function aplicarPreset(preset) {
        const colores = presets[preset];
        if (!colores) return;

        // Actualizar inputs de color
        document.querySelector('input[name="color_primary"]').value = colores.primary;
        document.querySelector('input[name="color_secondary"]').value = colores.secondary;
        document.querySelector('input[name="color_bg_light"]').value = colores.bg_light;
        document.querySelector('input[name="color_bg_dark"]').value = colores.bg_dark;
        document.querySelector('input[name="color_card_light"]').value = colores.card_light;
        document.querySelector('input[name="color_card_dark"]').value = colores.card_dark;

        // Actualizar inputs de texto (readonly)
        const textInputs = document.querySelectorAll('input[type="text"][readonly]');
        textInputs[0].value = colores.primary;
        textInputs[1].value = colores.secondary;
        textInputs[2].value = colores.bg_light;
        textInputs[3].value = colores.bg_dark;
        textInputs[4].value = colores.card_light;
        textInputs[5].value = colores.card_dark;
    }

    // Sincronizar inputs de color con inputs de texto
    document.querySelectorAll('input[type="color"]').forEach((colorInput, index) => {
        colorInput.addEventListener('input', function() {
            const textInput = this.nextElementSibling;
            if (textInput && textInput.tagName === 'INPUT') {
                textInput.value = this.value.toUpperCase();
            }
        });
    });
    </script>
</body>
</html>