<?php
require_once 'auth.php';
require_once '../config/database.php';

// Verificar que se proporcionó un viaje_id
if (!isset($_GET['viaje_id'])) {
    header('Location: index.php');
    exit;
}

$viaje_id = (int)$_GET['viaje_id'];

// Obtener información del viaje
$db = getDB();
$stmt = $db->prepare("SELECT * FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch();

if (!$viaje) {
    header('Location: index.php');
    exit;
}

// Calcular días disponibles del viaje
$fecha_inicio = new DateTime($viaje['fecha_inicio']);
$fecha_fin = new DateTime($viaje['fecha_fin']);
$dias_disponibles = [];
$current_date = clone $fecha_inicio;
$dia_numero = 1;

while ($current_date <= $fecha_fin) {
    $dias_disponibles[] = [
        'numero' => $dia_numero,
        'fecha' => $current_date->format('Y-m-d'),
        'fecha_formato' => $current_date->format('d/m/Y')
    ];
    $current_date->modify('+1 day');
    $dia_numero++;
}

$error = '';
$success = '';
$capas = [];
$archivo_temporal = '';

// Procesar archivo subido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['kmz_file'])) {
    $file = $_FILES['kmz_file'];
    
    // Validar archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo.';
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, ['kmz', 'kml'])) {
            $error = 'Formato de archivo no válido. Solo se permiten archivos KMZ o KML.';
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB
            $error = 'El archivo es demasiado grande. Tamaño máximo: 10MB.';
        } else {
            // Crear directorio temporal si no existe
            $temp_dir = sys_get_temp_dir() . '/kmz_import_' . $viaje_id . '_' . time();
            mkdir($temp_dir, 0777, true);
            
            $kml_content = '';
            
            if ($extension === 'kmz') {
                // Extraer KMZ (es un archivo ZIP)
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === TRUE) {
                    // Buscar el archivo doc.kml o el primer .kml
                    $kml_file = null;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (pathinfo($filename, PATHINFO_EXTENSION) === 'kml') {
                            $kml_file = $filename;
                            break;
                        }
                    }
                    
                    if ($kml_file) {
                        $kml_content = $zip->getFromName($kml_file);
                        $zip->close();
                    } else {
                        $zip->close();
                        $error = 'No se encontró ningún archivo KML dentro del KMZ.';
                    }
                } else {
                    $error = 'No se pudo abrir el archivo KMZ.';
                }
            } else {
                // Leer KML directamente
                $kml_content = file_get_contents($file['tmp_name']);
            }
            
            // Parsear KML
            if ($kml_content && empty($error)) {
                try {
                    // Guardar archivo temporal para el siguiente paso
                    $archivo_temporal = $temp_dir . '/doc.kml';
                    file_put_contents($archivo_temporal, $kml_content);
                    
                    // Parsear XML
                    $xml = simplexml_load_string($kml_content);
                    
                    if ($xml === false) {
                        $error = 'El archivo KML no es válido.';
                    } else {
                        // Buscar todas las carpetas (capas)
                        // Usamos SimpleXML con el namespace KML para mayor robustez
                        $folders = $xml->xpath('//kml:Folder');
                        
                        // Si no se encuentran con el namespace, intentar sin él (para KMLs mal formados)
                        if (empty($folders)) {
                            $folders = $xml->xpath('//Folder');
                        }
                        
                        if (empty($folders)) {
                            $error = 'No se encontraron capas en el archivo KML.';
                        } else {
                            foreach ($folders as $folder) {
                                $capa_nombre = (string)$folder->name;
                                // Acceso directo a los Placemarks dentro de la carpeta
                                $placemarks = $folder->Placemark;
                                
                                $puntos = [];
                                foreach ($placemarks as $placemark) {
                                    $nombre = (string)$placemark->name;
                                    $descripcion = isset($placemark->description) ? (string)$placemark->description : '';
                                    
                                    // Extraer coordenadas
                                    // Acceso directo a Point/coordinates
                                    $point = $placemark->Point->coordinates;
                                    if ($point) {
                                        $coords = trim((string)$point);
                                        $coords_array = explode(',', $coords);
                                        
                                        if (count($coords_array) >= 2) {
                                            $lng = (float)$coords_array[0];
                                            $lat = (float)$coords_array[1];
                                            
                                            // Validar coordenadas
                                            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                                                $puntos[] = [
                                                    'nombre' => $nombre,
                                                    'descripcion' => $descripcion,
                                                    'lat' => $lat,
                                                    'lng' => $lng
                                                ];
                                            }
                                        }
                                    }
                                }
                                
                                if (!empty($puntos)) {
                                    $capas[] = [
                                        'nombre' => $capa_nombre,
                                        'puntos' => $puntos,
                                        'total_puntos' => count($puntos)
                                    ];
                                }
                            }
                            
                            if (empty($capas)) {
                                $error = 'No se encontraron puntos válidos en las capas.';
                            } else {
                                // Guardar información en sesión para el siguiente paso
                                $_SESSION['kmz_import_data'] = [
                                    'viaje_id' => $viaje_id,
                                    'archivo_temporal' => $archivo_temporal,
                                    'capas' => $capas
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Error al procesar el archivo: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar KMZ/KML - <?php echo htmlspecialchars($viaje['titulo']); ?></title>
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
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">
                        <span class="material-symbols-outlined text-3xl">arrow_back</span>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Importar KMZ/KML</h1>
                        <p class="text-sm text-gray-600 mt-1">Viaje: <?php echo htmlspecialchars($viaje['titulo']); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-red-400">error</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($capas)): ?>
            <!-- Formulario de subida -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Paso 1: Subir Archivo</h2>
                    <p class="text-sm text-gray-600 mt-1">Selecciona un archivo KMZ o KML exportado desde Google My Maps</p>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="p-6">
                    <div class="space-y-6">
                        <!-- Información del viaje -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-blue-900 mb-2">Información del Viaje</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-blue-700 font-medium">Fecha inicio:</span>
                                    <span class="text-blue-900"><?php echo $fecha_inicio->format('d/m/Y'); ?></span>
                                </div>
                                <div>
                                    <span class="text-blue-700 font-medium">Fecha fin:</span>
                                    <span class="text-blue-900"><?php echo $fecha_fin->format('d/m/Y'); ?></span>
                                </div>
                                <div>
                                    <span class="text-blue-700 font-medium">Total días:</span>
                                    <span class="text-blue-900"><?php echo count($dias_disponibles); ?> días</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selector de archivo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Archivo KMZ/KML
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition">
                                <div class="space-y-1 text-center">
                                    <span class="material-symbols-outlined text-gray-400 text-6xl">upload_file</span>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="kmz_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Seleccionar archivo</span>
                                            <input id="kmz_file" name="kmz_file" type="file" accept=".kmz,.kml" class="sr-only" required>
                                        </label>
                                        <p class="pl-1">o arrastra y suelta</p>
                                    </div>
                                    <p class="text-xs text-gray-500">KMZ o KML hasta 10MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Instrucciones -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">info</span>
                                Cómo exportar desde Google My Maps
                            </h3>
                            <ol class="text-sm text-gray-700 space-y-1 ml-6 list-decimal">
                                <li>Abre tu mapa en Google My Maps</li>
                                <li>Haz clic en el menú (3 puntos) junto al título del mapa</li>
                                <li>Selecciona "Exportar a KML/KMZ"</li>
                                <li>Marca "Exportar todo el mapa"</li>
                                <li>Descarga el archivo KMZ</li>
                            </ol>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-between pt-4">
                            <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                                Subir y Analizar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <!-- Formulario de mapeo de capas -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Paso 2: Mapear Capas a Días</h2>
                    <p class="text-sm text-gray-600 mt-1">Asigna cada capa del archivo a un día específico del viaje</p>
                </div>
                
                <form method="POST" action="kmz_process.php" class="p-6">
                    <div class="space-y-6">
                        <!-- Resumen -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-green-900 mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined">check_circle</span>
                                Archivo procesado exitosamente
                            </h3>
                            <p class="text-sm text-green-700">
                                Se encontraron <strong><?php echo count($capas); ?> capas</strong> con un total de 
                                <strong><?php echo array_sum(array_column($capas, 'total_puntos')); ?> puntos</strong>
                            </p>
                        </div>

                        <!-- Mapeo de capas -->
                        <div class="space-y-4">
                            <?php foreach ($capas as $index => $capa): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-blue-600">folder</span>
                                            <?php echo htmlspecialchars($capa['nombre']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo $capa['total_puntos']; ?> punto<?php echo $capa['total_puntos'] > 1 ? 's' : ''; ?>
                                        </p>
                                        
                                        <!-- Previsualización de puntos -->
                                        <div class="mt-2 text-xs text-gray-500">
                                            <?php 
                                            $preview_puntos = array_slice($capa['puntos'], 0, 3);
                                            foreach ($preview_puntos as $punto): 
                                            ?>
                                            <div class="flex items-center gap-1 mt-1">
                                                <span class="material-symbols-outlined text-xs">place</span>
                                                <?php echo htmlspecialchars($punto['nombre']); ?>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php if ($capa['total_puntos'] > 3): ?>
                                            <div class="text-gray-400 mt-1">
                                                ... y <?php echo $capa['total_puntos'] - 3; ?> más
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Asignar a día:
                                        </label>
                                        <select name="capa_<?php echo $index; ?>_dia" 
                                                class="block w-48 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                required>
                                            <option value="">Seleccionar día...</option>
                                            <?php foreach ($dias_disponibles as $dia): ?>
                                            <option value="<?php echo $dia['numero']; ?>">
                                                Día <?php echo $dia['numero']; ?> - <?php echo $dia['fecha_formato']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-between pt-4 border-t border-gray-200">
                            <a href="kmz_import.php?viaje_id=<?php echo $viaje_id; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Volver
                            </a>
                            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition flex items-center gap-2">
                                <span class="material-symbols-outlined">check</span>
                                Crear Días y Actividades
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Mejorar UX del selector de archivos
        const fileInput = document.getElementById('kmz_file');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const fileName = e.target.files[0].name;
                    const label = document.querySelector('label[for="kmz_file"] span');
                    if (label) {
                        label.textContent = fileName;
                    }
                }
            });
        }
    </script>
</body>
</html>
