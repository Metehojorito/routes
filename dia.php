<?php
require_once 'config/database.php';

$numero_dia = isset($_GET['dia']) ? (int)$_GET['dia'] : 1;
$db = getDB();

// Obtener viaje actual (desde parámetro o sesión)
$viaje = getViajeActual();

if (!$viaje) {
    die("No hay viajes disponibles");
}

// Obtener colores personalizados
$colores = getViajeColores($viaje['id']);

// Obtener día actual
$stmt = $db->prepare("SELECT * FROM dias_viaje WHERE viaje_id = ? AND numero_dia = ? LIMIT 1");
$stmt->execute([$viaje['id'], $numero_dia]);
$dia = $stmt->fetch();
if (!$dia) die("Día no encontrado");

if (!$dia['visible']) {
    die("Este día no está disponible");
}

// Obtener días anterior y siguiente
$stmt = $db->prepare("SELECT numero_dia FROM dias_viaje WHERE viaje_id = ? AND numero_dia < ? ORDER BY numero_dia DESC LIMIT 1");
$stmt->execute([$viaje['id'], $numero_dia]);
$dia_anterior = $stmt->fetch();

$stmt = $db->prepare("SELECT numero_dia FROM dias_viaje WHERE viaje_id = ? AND numero_dia > ? ORDER BY numero_dia ASC LIMIT 1");
$stmt->execute([$viaje['id'], $numero_dia]);
$dia_siguiente = $stmt->fetch();

// Obtener secciones del día
$stmt = $db->prepare("SELECT * FROM secciones_dia WHERE dia_id = ? ORDER BY orden");
$stmt->execute([$dia['id']]);
$secciones = $stmt->fetchAll();

// Obtener actividades del día
$stmt = $db->prepare("SELECT * FROM actividades WHERE dia_id = ? AND visible = 1 ORDER BY orden");
$stmt->execute([$dia['id']]);
$actividades = $stmt->fetchAll();

// Obtener detalles de actividades
$detalles_por_actividad = [];
foreach ($actividades as $actividad) {
    $stmt = $db->prepare("SELECT * FROM detalles_actividad WHERE actividad_id = ? ORDER BY orden");
    $stmt->execute([$actividad['id']]);
    $detalles_por_actividad[$actividad['id']] = $stmt->fetchAll();
}

$meses = ['', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$fecha = new DateTime($dia['fecha']);
$mes = $meses[(int)$fecha->format('n')];
$dia_semana = $dias_semana[(int)$fecha->format('w')];

// Determinar qué pin usar: personalizado del viaje o el por defecto
$pin_url = 'images/pin.gif'; // Pin por defecto
if (!empty($viaje['pin_mapa'])) {
    $pin_url = $viaje['pin_mapa']; // Pin personalizado del viaje
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo htmlspecialchars($dia['titulo']); ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    
    <style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body, html { height: 100dvh; margin: 0; padding: 0; overflow: hidden; }
    
    /* Estilos específicos para Leaflet */
    #map { height: 100%; width: 100%; }
    .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; }
    
    /* Estilo para el popup de Leaflet en modo oscuro */
    .dark .leaflet-popup-content-wrapper,
    .dark .leaflet-popup-tip {
        background-color: #1c2c3a;
        color: #ffffff;
    }
    .dark .leaflet-popup-close-button {
        color: #ffffff !important;
    }
    </style>
    
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "<?php echo $colores['color_primary']; ?>",
                    "secondary": "<?php echo $colores['color_secondary']; ?>",
                    "background-light": "<?php echo $colores['color_bg_light']; ?>",
                    "background-dark": "<?php echo $colores['color_bg_dark']; ?>",
                    "text-light-primary": "#333333",
                    "text-light-secondary": "#888888",
                    "text-dark-primary": "#FFFFFF",
                    "text-dark-secondary": "#92adc9",
                    "card-light": "<?php echo $colores['color_card_light']; ?>",
                    "card-dark": "<?php echo $colores['color_card_dark']; ?>",
                },
                fontFamily: { "display": ["Plus Jakarta Sans", "sans-serif"] },
                borderRadius: {"DEFAULT": "0.5rem", "lg": "0.75rem", "xl": "1rem", "full": "9999px"},
            },
        },
    }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
    <div class="relative flex h-screen w-full flex-col">
        <div class="sticky top-0 z-10 flex items-center bg-background-light/80 dark:bg-background-dark/80 p-4 pb-2 backdrop-blur-sm">
            <button id="btnBack" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-text-light-primary dark:text-text-dark-primary">
                <span class="material-symbols-outlined text-2xl">arrow_back_ios_new</span>
            </button>
            <h1 class="flex-1 text-center text-lg font-bold leading-tight tracking-[-0.015em] text-text-light-primary dark:text-text-dark-primary">
                <a href="menu.php">Día <?php echo $numero_dia; ?>: <?php echo $fecha->format('d'); ?> <?php echo $mes; ?>, <?php echo $dia_semana; ?></a>
            </h1>
            <button id="btnNext" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-text-light-primary dark:text-text-dark-primary <?php echo $dia_siguiente ? '' : 'opacity-50'; ?>">
                <span class="material-symbols-outlined text-2xl">arrow_forward_ios</span>
            </button>
        </div>
        
        <div class="h-[40vh] shrink-0 px-4 pt-2">
            <div id="map" class="h-full w-full rounded-xl"></div>
        </div>
        
        <div class="overflow-y-auto pt-2" style="max-height: calc(100dvh - 40vh - 64px);">
            <?php if (!empty($secciones)): ?>
                <div class="space-y-4 p-4 pt-6">
                    <?php 
                    // Agrupar actividades por sección
                    $actividades_por_seccion = [];
                    $actividades_sin_seccion = [];
                    
                    foreach ($actividades as $actividad) {
                        if ($actividad['seccion_id']) {
                            if (!isset($actividades_por_seccion[$actividad['seccion_id']])) {
                                $actividades_por_seccion[$actividad['seccion_id']] = [];
                            }
                            $actividades_por_seccion[$actividad['seccion_id']][] = $actividad;
                        } else {
                            $actividades_sin_seccion[] = $actividad;
                        }
                    }
                    
                    foreach ($secciones as $seccion): 
                        if (!isset($actividades_por_seccion[$seccion['id']])) continue;
                    ?>
                    <div>
                        <h2 class="mb-2 text-xl font-bold leading-tight tracking-[-0.015em] text-text-light-primary dark:text-text-dark-primary">
                            <?php echo htmlspecialchars($seccion['titulo']); ?>
                        </h2>
                        <div class="space-y-3">
                            <?php foreach ($actividades_por_seccion[$seccion['id']] as $actividad): 
                                $tiene_detalles = !empty($detalles_por_actividad[$actividad['id']]);
                            ?>
                            <div class="flex min-h-[72px] cursor-pointer <?php echo $tiene_detalles ? 'flex-col gap-3' : 'flex-col gap-4'; ?> rounded-lg bg-card-light p-3 shadow-sm dark:bg-card-dark item-daily" data-lat="<?php echo $actividad['lat']; ?>" data-lng="<?php echo $actividad['lng']; ?>" data-titulo="<?php echo htmlspecialchars($actividad['titulo']); ?>">
                                <div class="flex items-start gap-4 w-full">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-<?php echo htmlspecialchars($actividad['color_categoria']); ?>/20 text-<?php echo htmlspecialchars($actividad['color_categoria']); ?>">
                                        <span class="material-symbols-outlined text-2xl"><?php echo htmlspecialchars($actividad['icono']); ?></span>
                                    </div>
                                    <div class="flex flex-1 flex-col justify-center">
                                        <p class="font-medium leading-normal text-text-light-primary dark:text-text-dark-primary"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                        <?php if ($actividad['descripcion']): ?>
                                        <p class="text-sm font-normal leading-normal text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($actividad['lat'] && $actividad['lng']): ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $actividad['lat']; ?>,<?php echo $actividad['lng']; ?>" 
                                       target="_blank"
                                       class="text-primary dark:text-primary hover:scale-110 transition-transform">
                                        <span class="material-symbols-outlined">location_on</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($tiene_detalles): ?>
                                <div class="ml-1 flex flex-col gap-2 border-l-2 border-dashed border-<?php echo htmlspecialchars($actividad['color_categoria']); ?>/30 pl-5">
                                    <?php foreach ($detalles_por_actividad[$actividad['id']] as $detalle): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-base text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($detalle['icono']); ?></span>
                                        <p class="text-xs text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($detalle['texto']); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <h2 class="px-4 pb-2 pt-6 text-xl font-bold leading-tight tracking-[-0.015em] text-text-light-primary dark:text-text-dark-primary">
                    <?php echo htmlspecialchars($dia['descripcion'] ?: $dia['titulo']); ?>
                </h2>
                <div class="space-y-3 p-4 pt-2">
                    <?php foreach ($actividades as $actividad): 
                        $tiene_detalles = !empty($detalles_por_actividad[$actividad['id']]);
                    ?>
                    <div class="flex min-h-[72px] cursor-pointer <?php echo $tiene_detalles ? 'flex-col gap-3' : 'flex-col gap-4'; ?> rounded-lg bg-card-light p-3 shadow-sm dark:bg-card-dark item-daily" data-lat="<?php echo $actividad['lat']; ?>" data-lng="<?php echo $actividad['lng']; ?>" data-titulo="<?php echo htmlspecialchars($actividad['titulo']); ?>">
                        <div class="flex items-start gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-<?php echo htmlspecialchars($actividad['color_categoria']); ?>/20 text-<?php echo htmlspecialchars($actividad['color_categoria']); ?>">
                                <span class="material-symbols-outlined text-2xl"><?php echo htmlspecialchars($actividad['icono']); ?></span>
                            </div>
                            <div class="flex flex-1 flex-col justify-center">
                                <p class="font-medium leading-normal text-text-light-primary dark:text-text-dark-primary"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                <?php if ($actividad['descripcion']): ?>
                                <p class="text-sm font-normal leading-normal text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($actividad['lat'] && $actividad['lng']): ?>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $actividad['lat']; ?>,<?php echo $actividad['lng']; ?>" 
                               target="_blank"
                               class="text-primary dark:text-primary hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined">location_on</span>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php if ($tiene_detalles): ?>
                        <div class="ml-1 flex flex-col gap-2 border-l-2 border-dashed border-<?php echo htmlspecialchars($actividad['color_categoria']); ?>/30 pl-5">
                            <?php foreach ($detalles_por_actividad[$actividad['id']] as $detalle): ?>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($detalle['icono']); ?></span>
                                <p class="text-xs text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($detalle['texto']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>
    
    <script>
    // Navegación entre días
    document.getElementById("btnBack").addEventListener("click", () => {
        <?php if ($dia_anterior): ?>
        window.location.href = "dia.php?dia=<?php echo $dia_anterior['numero_dia']; ?>";
        <?php else: ?>
        window.location.href = "menu.php";
        <?php endif; ?>
    });
    
    <?php if ($dia_siguiente): ?>
    document.getElementById("btnNext").addEventListener("click", () => {
        window.location.href = "dia.php?dia=<?php echo $dia_siguiente['numero_dia']; ?>";
    });
    <?php endif; ?>
    
    // Inicializar mapa con Leaflet
    let map, markers = {};
    
    // Coordenadas del centro del mapa
    const center = [<?php echo $dia['centro_mapa_lat']; ?>, <?php echo $dia['centro_mapa_lng']; ?>];
    const zoom = <?php echo $dia['zoom_mapa']; ?>;
    
    // Crear el mapa
    map = L.map('map', {
        center: center,
        zoom: zoom,
        zoomControl: true,
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true
    });
    
    // Añadir capa de tiles de OpenStreetMap
    // Puedes cambiar el estilo aquí:
    // - OpenStreetMap estándar (claro)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);
    
    // Estilos alternativos (descomenta el que prefieras):
    
    // CartoDB Dark Matter (oscuro elegante)
    /*
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19
    }).addTo(map);
    */
    
    // CartoDB Positron (claro minimalista)
    /*
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19
    }).addTo(map);
    */
    
    // Crear icono personalizado
    const customIcon = L.icon({
        iconUrl: '<?php echo htmlspecialchars($pin_url); ?>',
        iconSize: [40, 40],        // Tamaño del icono
        iconAnchor: [20, 40],      // Punto del icono que corresponde a la posición del marcador
        popupAnchor: [0, -40]      // Punto desde donde se abre el popup relativo al iconAnchor
    });
    
    // Añadir marcadores desde las actividades
    const items = document.querySelectorAll(".item-daily");
    items.forEach((div, index) => {
        const lat = parseFloat(div.dataset.lat);
        const lng = parseFloat(div.dataset.lng);
        const titulo = div.dataset.titulo || `Punto ${index + 1}`;
        
        if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
            // Crear marcador
            const marker = L.marker([lat, lng], { icon: customIcon })
                .addTo(map)
                .bindPopup(titulo);
            
            markers[index] = marker;
            
            // Al hacer click en una actividad, centrar el mapa
            div.addEventListener("click", () => {
                // Primero cerrar cualquier popup abierto
                map.closePopup();
                
                // Centrar el mapa con animación suave
                map.flyTo([lat, lng], 16, {
                    duration: 0.8,
                    easeLinearity: 0.25
                });
                
                // Abrir popup después de la animación
                setTimeout(() => {
                    marker.openPopup();
                }, 500);
            });
        }
    });
    
    // Ajustar el tamaño del mapa después de cargarlo
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
    </script>
</body>
</html>