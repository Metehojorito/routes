<?php
require_once 'config/database.php';

$numero_dia = isset($_GET['dia']) ? (int)$_GET['dia'] : 1;
$db = getDB();

// Obtener viaje actual (desde parámetro o sesión)
$viaje = getViajeActual();

// Si no hay viaje seleccionado, mostrar página en blanco
if (!$viaje) {
    http_response_code(200);
    exit;
}

// Obtener colores personalizados
$colores = getViajeColores($viaje['id']);

// Obtener día actual
$stmt = $db->prepare("SELECT * FROM dias_viaje WHERE viaje_id = ? AND numero_dia = ? LIMIT 1");
$stmt->execute([$viaje['id'], $numero_dia]);
$dia = $stmt->fetch();

// Si el día no existe o no es visible, redirigir al menú
if (!$dia || !$dia['visible']) {
    header("Location: menu.php");
    exit;
}

// Función para obtener el día visible anterior
function getDiaAnteriorVisible($db, $viaje_id, $numero_dia_actual) {
    $stmt = $db->prepare("
        SELECT numero_dia 
        FROM dias_viaje 
        WHERE viaje_id = ? 
        AND numero_dia < ? 
        AND visible = 1 
        ORDER BY numero_dia DESC 
        LIMIT 1
    ");
    $stmt->execute([$viaje_id, $numero_dia_actual]);
    return $stmt->fetch();
}

// Función para obtener el día visible siguiente
function getDiaSiguienteVisible($db, $viaje_id, $numero_dia_actual) {
    $stmt = $db->prepare("
        SELECT numero_dia 
        FROM dias_viaje 
        WHERE viaje_id = ? 
        AND numero_dia > ? 
        AND visible = 1 
        ORDER BY numero_dia ASC 
        LIMIT 1
    ");
    $stmt->execute([$viaje_id, $numero_dia_actual]);
    return $stmt->fetch();
}

// Obtener días anterior y siguiente visibles
$dia_anterior = getDiaAnteriorVisible($db, $viaje['id'], $numero_dia);
$dia_siguiente = getDiaSiguienteVisible($db, $viaje['id'], $numero_dia);

// Obtener secciones del día
$stmt = $db->prepare("SELECT * FROM secciones_dia WHERE dia_id = ? ORDER BY orden");
$stmt->execute([$dia['id']]);
$secciones = $stmt->fetchAll();

// Obtener actividades visibles del día
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

    /* Botón de audio - animaciones */
    .audio-btn {
        transition: all 0.3s ease;
    }
    .audio-btn:hover {
        transform: scale(1.1);
    }
    .audio-btn.playing {
        animation: pulse-audio 1.5s ease-in-out infinite;
    }
    @keyframes pulse-audio {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.8;
        }
    }

    /* ========================================== */
	/* ESTILOS PARA IMPRESIÓN */
	/* ========================================== */
	@media print {
		/* Ocultar overlay y FAB */
		#fabOverlay,
		#fabMenu,
		#toast {
			display: none !important;
		}
		
		/* Ocultar controles de navegación */
		.sticky.top-0 {
			display: none !important;
		}
		
		/* Ocultar botones de audio */
		.audio-btn {
			display: none !important;
		}
		
		/* Mostrar el mapa en impresión */
		#map {
			display: block !important;
			height: 300px !important;
			width: 100% !important;
			page-break-inside: avoid;
			break-inside: avoid;
			margin-bottom: 20px;
		}
		
		/* Ajustar contenedor del mapa */
		.h-\[40vh\] {
			height: auto !important;
			min-height: 300px !important;
		}
		
		/* Ocultar controles del mapa de Leaflet */
		.leaflet-control-container {
			display: none !important;
		}
		
		/* Ocultar popups del mapa */
		.leaflet-popup {
			display: none !important;
		}
		
		/* Asegurar que los tiles del mapa se impriman */
		.leaflet-tile-container img {
			-webkit-print-color-adjust: exact !important;
			print-color-adjust: exact !important;
		}
		
		/* Resetear estilos para impresión */
		body, html {
			height: auto !important;
			overflow: visible !important;
		}
		
		/* Contenedor principal sin restricciones */
		.relative.flex.h-screen {
			height: auto !important;
		}
		
		/* Área de contenido sin scroll */
		.overflow-y-auto {
			overflow: visible !important;
			max-height: none !important;
			height: auto !important;
		}
		
		/* Asegurar que todas las actividades se impriman */
		.space-y-4,
		.space-y-3 {
			page-break-inside: avoid;
		}
		
		/* Evitar cortes en las actividades */
		.item-daily {
			page-break-inside: avoid;
			break-inside: avoid;
			margin-bottom: 10px;
		}
		
		/* Títulos de sección al inicio de página */
		h2 {
			page-break-after: avoid;
			break-after: avoid;
			margin-top: 20px;
			font-size: 18px;
			font-weight: bold;
		}
		
		/* Colores más oscuros para impresión */
		.text-text-light-primary,
		.dark .text-text-dark-primary {
			color: #000 !important;
		}
		
		.text-text-light-secondary,
		.dark .text-text-dark-secondary {
			color: #555 !important;
		}
		
		/* Fondo blanco para todo */
		body,
		.bg-background-light,
		.dark\:bg-background-dark,
		.bg-card-light,
		.dark\:bg-card-dark {
			background-color: #fff !important;
		}
		
		/* Bordes visibles */
		.rounded-lg,
		.shadow-sm {
			border: 1px solid #ddd !important;
			box-shadow: none !important;
			border-radius: 8px !important;
		}
		
		/* Hacer los iconos de actividades imprimibles */
		.material-symbols-outlined {
			-webkit-print-color-adjust: exact !important;
			print-color-adjust: exact !important;
		}
		
		/* Colores de fondo para iconos */
		[class*="bg-primary"],
		[class*="bg-secondary"] {
			-webkit-print-color-adjust: exact !important;
			print-color-adjust: exact !important;
		}
		
		/* Asegurar que los colores de los iconos se impriman */
		.item-daily > div > div:first-child {
			-webkit-print-color-adjust: exact !important;
			print-color-adjust: exact !important;
		}
		
		/* Encabezado de impresión */
		@page {
			margin: 1.5cm;
			size: A4;
		}
		
		/* Agregar título del viaje al inicio */
		body::before {
			content: '<?php echo htmlspecialchars($viaje['titulo']); ?> - Día <?php echo $numero_dia; ?>: <?php echo $fecha->format('d/m/Y'); ?>';
			display: block;
			font-size: 22px;
			font-weight: bold;
			margin-bottom: 20px;
			padding-bottom: 15px;
			border-bottom: 2px solid #333;
			text-align: center;
		}
		
		/* Espaciado adicional */
		.p-4 {
			padding: 0 !important;
		}
		
		.px-4 {
			padding-left: 0 !important;
			padding-right: 0 !important;
		}
	}

    /* ========================================== */
    /* FAB (Floating Action Button) */
    /* ========================================== */

    /* Overlay de fondo cuando el menú está abierto */
    #fabOverlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0);
        backdrop-filter: blur(0px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
        z-index: 19;
        opacity: 0;
    }

    #fabOverlay.active {
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        pointer-events: auto;
        opacity: 1;
    }

    /* Contenedor del menú FAB */
    #fabMenu {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 30;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 1rem;
        pointer-events: none;
    }

    /* Solo los botones deben recibir clicks */
    #fabMenu button {
        pointer-events: auto;
    }

    /* Animaciones para las opciones del FAB */
    .fab-action {
        opacity: 0;
        transform: translateY(20px) scale(0.8);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
        visibility: hidden;
    }

    .fab-action.visible {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
        visibility: visible;
    }

    .fab-action.delay-0 { transition-delay: 0ms; }
    .fab-action.delay-75 { transition-delay: 75ms; }
    .fab-action.delay-100 { transition-delay: 100ms; }
    .fab-action.delay-150 { transition-delay: 150ms; }

    .fab-action.visible.delay-0 { transition-delay: 150ms; }
    .fab-action.visible.delay-75 { transition-delay: 100ms; }
    .fab-action.visible.delay-100 { transition-delay: 50ms; }
    .fab-action.visible.delay-150 { transition-delay: 0ms; }

    .fab-label {
        white-space: nowrap;
    }

    /* Botón principal - rotación cuando está abierto */
    #fabMain {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }

    #fabMain.open {
        transform: rotate(45deg);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
    }

    /* Botones de acción - más destacados cuando el menú está abierto */
    .fab-action.visible button {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .dark .fab-action.visible button {
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    /* Labels más visibles */
    .fab-action.visible .fab-label {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .dark .fab-action.visible .fab-label {
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    /* Toast de confirmación */
    #toast {
        position: fixed;
        bottom: 100px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 100;
        pointer-events: none;
    }

    #toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
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
            <button id="btnNext" class="flex size-10 shrink-0 items-center justify-center rounded-full text-text-light-primary dark:text-text-dark-primary <?php echo $dia_siguiente ? 'cursor-pointer' : 'opacity-50 cursor-not-allowed'; ?>">
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
                                $tiene_audio = !empty($actividad['descripcion_auditiva']);
                            ?>
                            <div class="flex min-h-[72px] cursor-pointer <?php echo $tiene_detalles ? 'flex-col gap-3' : 'flex-col gap-4'; ?> rounded-lg bg-card-light p-3 shadow-sm dark:bg-card-dark item-daily" 
                                 data-lat="<?php echo $actividad['lat']; ?>" 
                                 data-lng="<?php echo $actividad['lng']; ?>" 
                                 data-titulo="<?php echo htmlspecialchars($actividad['titulo']); ?>"
                                 data-actividad-id="<?php echo $actividad['id']; ?>">
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
                                    <div class="flex items-center gap-2">
                                        <?php if ($tiene_audio): ?>
                                        <button class="audio-btn text-primary dark:text-primary" 
                                                data-audio-text="<?php echo htmlspecialchars($actividad['descripcion_auditiva']); ?>"
                                                data-actividad-id="<?php echo $actividad['id']; ?>"
                                                title="Escuchar descripción">
                                            <span class="material-symbols-outlined play-icon">volume_up</span>
                                            <span class="material-symbols-outlined pause-icon hidden">pause</span>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($actividad['lat'] && $actividad['lng']): ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $actividad['lat']; ?>,<?php echo $actividad['lng']; ?>" 
                                           target="_blank"
                                           class="text-primary dark:text-primary hover:scale-110 transition-transform">
                                            <span class="material-symbols-outlined">location_on</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
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
                        $tiene_audio = !empty($actividad['descripcion_auditiva']);
                    ?>
                    <div class="flex min-h-[72px] cursor-pointer <?php echo $tiene_detalles ? 'flex-col gap-3' : 'flex-col gap-4'; ?> rounded-lg bg-card-light p-3 shadow-sm dark:bg-card-dark item-daily" 
                         data-lat="<?php echo $actividad['lat']; ?>" 
                         data-lng="<?php echo $actividad['lng']; ?>" 
                         data-titulo="<?php echo htmlspecialchars($actividad['titulo']); ?>"
                         data-actividad-id="<?php echo $actividad['id']; ?>">
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
                            <div class="flex items-center gap-2">
                                <?php if ($tiene_audio): ?>
                                <button class="audio-btn text-primary dark:text-primary" 
                                        data-audio-text="<?php echo htmlspecialchars($actividad['descripcion_auditiva']); ?>"
                                        data-actividad-id="<?php echo $actividad['id']; ?>"
                                        title="Escuchar descripción">
                                    <span class="material-symbols-outlined play-icon">volume_up</span>
                                    <span class="material-symbols-outlined pause-icon hidden">pause</span>
                                </button>
                                <?php endif; ?>
                                <?php if ($actividad['lat'] && $actividad['lng']): ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $actividad['lat']; ?>,<?php echo $actividad['lng']; ?>" 
                                   target="_blank"
                                   class="text-primary dark:text-primary hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined">location_on</span>
                                </a>
                                <?php endif; ?>
                            </div>
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
    
    <!-- Floating Action Button (FAB) Container -->
    <div id="fabContainer" class="fixed inset-0 z-20" style="pointer-events: none;">
        <!-- Overlay de fondo -->
        <div id="fabOverlay"></div>

        <!-- Menú FAB -->
        <div id="fabMenu">
            <!-- Opción: Compartir itinerario -->
            <div class="fab-action flex items-center gap-3 delay-150">
                <span class="fab-label rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-lg dark:bg-gray-800 dark:text-white">
                    Compartir itinerario
                </span>
                <button id="btnShare" class="flex size-12 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg hover:bg-primary hover:text-white transition-all dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-primary">
                    <span class="material-symbols-outlined text-xl">share</span>
                </button>
            </div>

            <!-- Opción: Ver todas en Google Maps -->
            <div class="fab-action flex items-center gap-3 delay-100">
                <span class="fab-label rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-lg dark:bg-gray-800 dark:text-white">
                    Ver todas en Google Maps
                </span>
                <button id="btnMaps" class="flex size-12 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg hover:bg-primary hover:text-white transition-all dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-primary">
                    <span class="material-symbols-outlined text-xl">map</span>
                </button>
            </div>

            <!-- Opción: Imprimir itinerario -->
            <div class="fab-action flex items-center gap-3 delay-75">
                <span class="fab-label rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-lg dark:bg-gray-800 dark:text-white">
                    Imprimir itinerario
                </span>
                <button id="btnPrint" class="flex size-12 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg hover:bg-primary hover:text-white transition-all dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-primary">
                    <span class="material-symbols-outlined text-xl">print</span>
                </button>
            </div>

            <!-- Opción: Añadir a calendario -->
            <div class="fab-action flex items-center gap-3 delay-0">
                <span class="fab-label rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-lg dark:bg-gray-800 dark:text-white">
                    Añadir a calendario
                </span>
                <button id="btnCalendar" class="flex size-12 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg hover:bg-primary hover:text-white transition-all dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-primary">
                    <span class="material-symbols-outlined text-xl">event</span>
                </button>
            </div>

            <!-- Botón principal -->
            <button id="fabMain" class="flex size-14 items-center justify-center rounded-full bg-secondary text-white transition-all hover:scale-105 active:scale-95">
                <span class="material-symbols-outlined text-3xl">add</span>
            </button>
        </div>
    </div>
    
    <!-- Toast de confirmación -->
    <div id="toast" class="rounded-lg bg-card-light px-4 py-3 shadow-xl dark:bg-card-dark">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-500">check_circle</span>
            <span id="toastMessage" class="text-sm font-medium text-text-light-primary dark:text-text-dark-primary"></span>
        </div>
    </div>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>
    
    <script>
    // ==========================================
    // NAVEGACIÓN ENTRE DÍAS
    // ==========================================
    document.getElementById("btnBack").addEventListener("click", () => {
        // Detener audio antes de navegar
        stopCurrentAudio();
        
        <?php if ($dia_anterior): ?>
        window.location.href = "dia.php?dia=<?php echo $dia_anterior['numero_dia']; ?>";
        <?php else: ?>
        window.location.href = "menu.php";
        <?php endif; ?>
    });
    
    <?php if ($dia_siguiente): ?>
    document.getElementById("btnNext").addEventListener("click", () => {
        // Detener audio antes de navegar
        stopCurrentAudio();
        
        window.location.href = "dia.php?dia=<?php echo $dia_siguiente['numero_dia']; ?>";
    });
    <?php endif; ?>
    
    // Detener audio también al hacer click en el título (ir al menú)
    document.querySelector('h1 a').addEventListener('click', () => {
        stopCurrentAudio();
    });
    
    // Detener audio al cerrar la pestaña o navegar atrás
    window.addEventListener('beforeunload', () => {
        stopCurrentAudio();
    });
    
    // Detener audio al usar el botón atrás del navegador
    window.addEventListener('pagehide', () => {
        stopCurrentAudio();
    });
    
    // ==========================================
    // SISTEMA DE AUDIO CON WEB SPEECH API
    // ==========================================
    let currentSpeech = null;
    let currentButton = null;
    let currentText = '';
    let isPlaying = false;
    let isPaused = false;
    
    // Variables para simular pause/resume en móviles
    let pausedAtChar = 0;
    let totalChars = 0;
    
    // Inicializar Speech Synthesis API
    const synth = window.speechSynthesis;
    
    // Detectar si estamos en móvil
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    
    // IMPORTANTE: En móviles, necesitamos inicializar el synth con interacción del usuario
    let synthInitialized = false;
    
    // Función para inicializar synth (necesario en móviles)
    function initSynth() {
        if (!synthInitialized) {
            // Crear y cancelar un utterance vacío para "despertar" el synth
            const dummy = new SpeechSynthesisUtterance('');
            synth.speak(dummy);
            synth.cancel();
            synthInitialized = true;
        }
    }
    
    // Función para detener el audio actual de forma segura
    function stopCurrentAudio() {
        if (currentSpeech) {
            // Remover listeners antes de cancelar
            currentSpeech.onstart = null;
            currentSpeech.onend = null;
            currentSpeech.onerror = null;
            currentSpeech.onpause = null;
            currentSpeech.onresume = null;
            currentSpeech.onboundary = null;
            
            synth.cancel();
            
            if (currentButton) {
                resetButton(currentButton);
            }
            
            currentSpeech = null;
            currentButton = null;
            currentText = '';
            isPlaying = false;
            isPaused = false;
            pausedAtChar = 0;
            totalChars = 0;
        }
    }
    
    // Función para reproducir audio
    function playAudio(button, text, startFromChar = 0) {
        // Inicializar synth si es necesario (móviles)
        initSynth();
        
        // Si hay un audio diferente reproduciéndose, detenerlo
        if (currentButton !== button) {
            stopCurrentAudio();
        }
        
        // Guardar el texto actual
        currentText = text;
        totalChars = text.length;
        
        // Si debemos empezar desde un punto específico (resume)
        let textToSpeak = text;
        if (startFromChar > 0) {
            // Calcular aproximadamente desde dónde reanudar (por palabras)
            const progress = startFromChar / totalChars;
            const words = text.split(' ');
            const wordIndex = Math.floor(words.length * progress);
            textToSpeak = words.slice(wordIndex).join(' ');
            console.log(`Reanudando desde palabra ${wordIndex} de ${words.length}`);
        }
        
        // Pequeño delay para asegurar que synth está listo (crítico en móviles)
        setTimeout(() => {
            // Crear nueva instancia
            currentSpeech = new SpeechSynthesisUtterance(textToSpeak);
            currentSpeech.lang = 'es-ES';
            currentSpeech.rate = 0.9;
            currentSpeech.pitch = 1;
            currentSpeech.volume = 1;
            
            // Rastrear el progreso (para móviles)
            currentSpeech.onboundary = (event) => {
                if (event.name === 'word') {
                    pausedAtChar = event.charIndex + (startFromChar > 0 ? startFromChar : 0);
                }
            };
            
            // Event listeners
            currentSpeech.onstart = () => {
                console.log('Audio iniciado');
                isPlaying = true;
                isPaused = false;
                button.classList.add('playing');
                button.querySelector('.play-icon').classList.add('hidden');
                button.querySelector('.pause-icon').classList.remove('hidden');
            };
            
            currentSpeech.onend = () => {
                console.log('Audio finalizado');
                resetButton(button);
                currentSpeech = null;
                currentButton = null;
                currentText = '';
                isPlaying = false;
                isPaused = false;
                pausedAtChar = 0;
                totalChars = 0;
            };
            
            currentSpeech.onerror = (e) => {
                // Solo mostrar error si NO es por interrupción
                if (e.error !== 'interrupted' && e.error !== 'canceled') {
                    console.error('Error en síntesis de voz:', e.error, e);
                }
                resetButton(button);
                currentSpeech = null;
                currentButton = null;
                currentText = '';
                isPlaying = false;
                isPaused = false;
                pausedAtChar = 0;
                totalChars = 0;
            };
            
            currentButton = button;
            
            // Reproducir - CRÍTICO: debe ser síncrono con el click del usuario
            try {
                synth.speak(currentSpeech);
                console.log('synth.speak() llamado');
            } catch (error) {
                console.error('Error al llamar synth.speak():', error);
                resetButton(button);
            }
        }, 50); // Pequeño delay para móviles
    }
    
    // Función para pausar audio
    function pauseAudio(button) {
        console.log('Pausando audio');
        isPaused = true;
        isPlaying = false;
        
        // En desktop, intentar usar pause nativo
        if (!isMobile && synth.speaking) {
            try {
                synth.pause();
                console.log('Usando pause nativo');
            } catch (e) {
                console.log('Pause nativo falló, usando cancel:', e);
                synth.cancel();
            }
        } else {
            // En móviles, cancelar (ya tenemos el progreso guardado)
            synth.cancel();
        }
        
        // Mantener referencia al botón y al texto para poder reanudar
        // Ya están guardados en currentButton y currentText
        
        // Actualizar UI
        button.classList.remove('playing');
        button.querySelector('.play-icon').classList.remove('hidden');
        button.querySelector('.pause-icon').classList.add('hidden');
    }
    
    // Función para reanudar audio
    function resumeAudio(button, text) {
        console.log('Reanudando audio');
        isPaused = false;
        
        // En desktop, intentar resume nativo
        if (!isMobile && synth.paused) {
            try {
                synth.resume();
                isPlaying = true;
                button.classList.add('playing');
                button.querySelector('.play-icon').classList.add('hidden');
                button.querySelector('.pause-icon').classList.remove('hidden');
                console.log('Usando resume nativo');
                return;
            } catch (e) {
                console.log('Resume nativo falló, reproduciendo desde progreso:', e);
            }
        }
        
        // En móviles o si falla el resume, reproducir desde donde se quedó
        if (pausedAtChar > 0) {
            playAudio(button, text, pausedAtChar);
        } else {
            playAudio(button, text);
        }
    }
    
    // Función para resetear botón
    function resetButton(button) {
        button.classList.remove('playing');
        button.querySelector('.play-icon').classList.remove('hidden');
        button.querySelector('.pause-icon').classList.add('hidden');
    }
    
    // Configurar event listeners en botones de audio
    document.querySelectorAll('.audio-btn').forEach(button => {
        // Usar 'touchstart' en móviles para mejor respuesta
        const eventType = 'ontouchstart' in window ? 'touchstart' : 'click';
        
        button.addEventListener(eventType, (e) => {
            e.preventDefault(); // Evitar doble disparo en móviles
            e.stopPropagation(); // Evitar que se active el click del card
            
            console.log('Botón de audio pulsado');
            
            const text = button.dataset.audioText;
            const actividadId = button.dataset.actividadId;
            
            // Si este botón es el que está activo
            if (currentButton === button) {
                if (isPlaying) {
                    // Está reproduciéndose -> pausar
                    console.log('Pausar audio actual');
                    pauseAudio(button);
                } else if (isPaused || (!isPlaying && currentText === text)) {
                    // Está pausado -> reanudar
                    console.log('Reanudar audio');
                    resumeAudio(button, text);
                } else {
                    // No está reproduciendo -> reproducir
                    console.log('Reproducir audio');
                    playAudio(button, text);
                }
            } else {
                // Es un botón diferente -> reproducir nuevo audio
                console.log('Nuevo audio - reproducir');
                playAudio(button, text);
            }
        }, { passive: false });
    });
    
    // ==========================================
    // MAPA CON LEAFLET (CartoDB Tiles)
    // ==========================================
    let map, markers = {};
    
    const center = [<?php echo $dia['centro_mapa_lat']; ?>, <?php echo $dia['centro_mapa_lng']; ?>];
    const zoom = <?php echo $dia['zoom_mapa']; ?>;
    
    map = L.map('map', {
        center: center,
        zoom: zoom,
        zoomControl: true,
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true
    });
    
    // Usar tiles de CartoDB para un diseño más limpio
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);
    
    const customIcon = L.icon({
        iconUrl: '<?php echo htmlspecialchars($pin_url); ?>',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
    });
    
    const items = document.querySelectorAll(".item-daily");
    items.forEach((div, index) => {
        const lat = parseFloat(div.dataset.lat);
        const lng = parseFloat(div.dataset.lng);
        const titulo = div.dataset.titulo || `Punto ${index + 1}`;
        
        if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
            const marker = L.marker([lat, lng], { icon: customIcon })
                .addTo(map)
                .bindPopup(titulo);
            
            markers[index] = marker;
            
            // Variables para detectar drag vs click en móviles
            let touchStartX = 0;
            let touchStartY = 0;
            let touchStartTime = 0;
            let isTouchMove = false;
            
            // Detectar inicio de touch
            div.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                touchStartTime = Date.now();
                isTouchMove = false;
            }, { passive: true });
            
            // Detectar movimiento (drag)
            div.addEventListener('touchmove', (e) => {
                const touchX = e.touches[0].clientX;
                const touchY = e.touches[0].clientY;
                const deltaX = Math.abs(touchX - touchStartX);
                const deltaY = Math.abs(touchY - touchStartY);
                
                // Si se movió más de 10px, es un drag
                if (deltaX > 10 || deltaY > 10) {
                    isTouchMove = true;
                }
            }, { passive: true });
            
            // Detectar fin de touch (click o drag)
            div.addEventListener('touchend', (e) => {
                const touchDuration = Date.now() - touchStartTime;
                
                // Solo activar si:
                // 1. No fue un drag (movimiento)
                // 2. Duró menos de 500ms (no fue un long press)
                // 3. No se hizo click en botones
                if (!isTouchMove && touchDuration < 500) {
                    if (!e.target.closest('.audio-btn') && !e.target.closest('a[href*="google.com/maps"]')) {
                        map.closePopup();
                        map.flyTo([lat, lng], 16, {
                            duration: 0.8,
                            easeLinearity: 0.25
                        });
                        setTimeout(() => {
                            marker.openPopup();
                        }, 500);
                    }
                }
            }, { passive: true });
            
            // Para desktop, usar click normal
            div.addEventListener("click", (e) => {
                // Solo en desktop (si no hay soporte táctil)
                if (!('ontouchstart' in window)) {
                    if (!e.target.closest('.audio-btn') && !e.target.closest('a[href*="google.com/maps"]')) {
                        map.closePopup();
                        map.flyTo([lat, lng], 16, {
                            duration: 0.8,
                            easeLinearity: 0.25
                        });
                        setTimeout(() => {
                            marker.openPopup();
                        }, 500);
                    }
                }
            });
        }
    });
    
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
    
    // ==========================================
    // FLOATING ACTION BUTTON (FAB)
    // ==========================================
    const fabMain = document.getElementById('fabMain');
    const fabOverlay = document.getElementById('fabOverlay');
    const fabActions = document.querySelectorAll('.fab-action');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    let isMenuOpen = false;

    // Toggle del menú FAB
    fabMain.addEventListener('click', (e) => {
        e.stopPropagation();
        isMenuOpen = !isMenuOpen;

        // Toggle clases
        fabMain.classList.toggle('open', isMenuOpen);
        fabOverlay.classList.toggle('active', isMenuOpen);
        fabActions.forEach(action => {
            action.classList.toggle('visible', isMenuOpen);
        });
    });

    // Cerrar menú al hacer click en el overlay
    fabOverlay.addEventListener('click', () => {
        if (isMenuOpen) {
            closeMenu();
        }
    });

    // Función para cerrar el menú
    function closeMenu() {
        isMenuOpen = false;
        fabMain.classList.remove('open');
        fabOverlay.classList.remove('active');
        fabActions.forEach(action => {
            action.classList.remove('visible');
        });
    }

    // Función para mostrar toast
    function showToast(message) {
        toastMessage.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // ==========================================
	// OPCIÓN: COMPARTIR ITINERARIO
	// ==========================================
	document.getElementById('btnShare').addEventListener('click', async (e) => {
		e.stopPropagation();
		const shareUrl = `${window.location.origin}${window.location.pathname}?viaje=<?php echo urlencode($viaje['slug']); ?>&dia=<?php echo $numero_dia; ?>`;

		try {
			// Intentar primero con la API de compartir nativa (funciona sin HTTPS en móviles)
			if (navigator.share) {
				await navigator.share({
					title: '<?php echo htmlspecialchars($dia['titulo']); ?>',
					text: 'Mira el itinerario del día <?php echo $numero_dia; ?> de nuestro viaje',
					url: shareUrl
				});
				showToast('¡Compartido!');
			} 
			// Fallback: copiar con método antiguo (funciona sin HTTPS)
			else {
				// Crear input temporal
				const tempInput = document.createElement('input');
				tempInput.value = shareUrl;
				tempInput.style.position = 'absolute';
				tempInput.style.left = '-9999px';
				tempInput.style.top = '0';
				document.body.appendChild(tempInput);
				
				// Seleccionar y copiar
				tempInput.focus();
				tempInput.select();
				tempInput.setSelectionRange(0, 99999); // Para dispositivos móviles
				
				let successful = false;
				try {
					successful = document.execCommand('copy');
				} catch (err) {
					successful = false;
				}
				
				document.body.removeChild(tempInput);
				
				if (successful) {
					showToast('¡URL copiada al portapapeles!');
				} else {
					// Si falla, mostrar un modal o prompt
					showShareModal(shareUrl);
				}
			}
		} catch (err) {
			console.error('Error al compartir:', err);
			if (err.name !== 'AbortError') {
				// Si no es cancelación del usuario, mostrar modal
				showShareModal(shareUrl);
			}
		}

		closeMenu();
	});

	// Función para mostrar modal de compartir cuando falla todo lo demás
	function showShareModal(url) {
		// Crear modal
		const modal = document.createElement('div');
		modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
		modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
		modal.innerHTML = `
			<div class="bg-white dark:bg-card-dark rounded-lg shadow-xl p-6 max-w-md w-full">
				<h3 class="text-lg font-bold text-text-light-primary dark:text-text-dark-primary mb-4">
					Compartir itinerario
				</h3>
				<p class="text-sm text-text-light-secondary dark:text-text-dark-secondary mb-4">
					Copia este enlace para compartir:
				</p>
				<div class="flex gap-2 mb-4">
					<input type="text" value="${url}" readonly 
						class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-sm text-text-light-primary dark:text-text-dark-primary"
						id="shareUrlInput">
					<button onclick="copyFromModal()" 
						class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 transition-colors">
						Copiar
					</button>
				</div>
				<button onclick="closeShareModal()" 
					class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-text-light-primary dark:text-text-dark-primary rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
					Cerrar
				</button>
			</div>
		`;
		
		document.body.appendChild(modal);
		
		// Seleccionar el texto automáticamente
		setTimeout(() => {
			document.getElementById('shareUrlInput').select();
		}, 100);
		
		// Cerrar al hacer click fuera
		modal.addEventListener('click', (e) => {
			if (e.target === modal) {
				closeShareModal();
			}
		});
	}

	// Función global para copiar desde el modal
	window.copyFromModal = function() {
		const input = document.getElementById('shareUrlInput');
		input.select();
		input.setSelectionRange(0, 99999);
		
		try {
			document.execCommand('copy');
			showToast('¡URL copiada!');
			closeShareModal();
		} catch (err) {
			showToast('Selecciona y copia manualmente (Ctrl+C)');
		}
	};

	// Función global para cerrar el modal
	window.closeShareModal = function() {
		const modal = document.querySelector('.fixed.inset-0.z-50');
		if (modal) {
			modal.remove();
		}
	};
    
    // ==========================================
    // OPCIÓN: VER TODAS EN GOOGLE MAPS
    // ==========================================
    document.getElementById('btnMaps').addEventListener('click', (e) => {
        e.stopPropagation();

        const locations = [];
        items.forEach(item => {
            const lat = parseFloat(item.dataset.lat);
            const lng = parseFloat(item.dataset.lng);
            if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                locations.push(`${lat},${lng}`);
            }
        });

        if (locations.length > 0) {
            const origin = locations[0];
            const destination = locations[locations.length - 1];
            const waypoints = locations.slice(1, -1).join('|');

            let mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}`;
            if (waypoints) {
                mapsUrl += `&waypoints=${waypoints}`;
            }
            mapsUrl += '&travelmode=walking';

            window.open(mapsUrl, '_blank');
            showToast('Abriendo en Google Maps...');
        } else {
            showToast('No hay ubicaciones disponibles');
        }

        closeMenu();
    });
    
    // ==========================================
    // OPCIÓN: IMPRIMIR ITINERARIO
    // ==========================================
    document.getElementById('btnPrint').addEventListener('click', (e) => {
		e.stopPropagation();
		
		// Detener cualquier audio que esté reproduciéndose
		stopCurrentAudio();
		
		// Cerrar cualquier popup del mapa antes de imprimir
		map.closePopup();
		
		// Invalidar el tamaño del mapa para asegurar que se renderiza correctamente
		setTimeout(() => {
			map.invalidateSize();
			// Esperar un poco para que el mapa se actualice antes de imprimir
			setTimeout(() => {
				window.print();
			}, 100);
		}, 100);
		
		closeMenu();
	});
    
    // ==========================================
    // OPCIÓN: AÑADIR A CALENDARIO
    // ==========================================
    document.getElementById('btnCalendar').addEventListener('click', (e) => {
        e.stopPropagation();

        // Crear archivo .ics con formato correcto
        const fecha = '<?php echo $fecha->format('Ymd'); ?>';
        const titulo = '<?php echo str_replace(["'", '"', "\n", "\r"], ["", "", " ", " "], $dia['titulo']); ?>';
        const descripcion = '<?php echo str_replace(["'", '"', "\n", "\r"], ["", "", " ", " "], $dia['descripcion']); ?>';
        const viajeNombre = '<?php echo str_replace(["'", '"', "\n", "\r"], ["", "", " ", " "], $viaje['titulo']); ?>';

        // Generar UID único
        const uid = `${fecha}-${Date.now()}@gestorviajes.com`;

        // Crear descripción con lista de actividades
        let actividadesTexto = 'Actividades del día:\\n\\n';
        <?php 
        $contador = 1;
        foreach ($actividades as $act): 
        ?>
        actividadesTexto += '<?php echo $contador; ?>. <?php echo str_replace(["'", '"', "\n", "\r"], ["", "", " ", " "], $act['titulo']); ?>\\n';
        <?php 
        $contador++;
        endforeach; 
        ?>

        const icsContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Gestor de Viajes//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' + viajeNombre,
            'X-WR-TIMEZONE:Europe/Madrid',
            'BEGIN:VTIMEZONE',
            'TZID:Europe/Madrid',
            'BEGIN:STANDARD',
            'DTSTART:19701025T030000',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0100',
            'END:STANDARD',
            'BEGIN:DAYLIGHT',
            'DTSTART:19700329T020000',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'TZOFFSETFROM:+0100',
            'TZOFFSETTO:+0200',
            'END:DAYLIGHT',
            'END:VTIMEZONE',
            'BEGIN:VEVENT',
            'UID:' + uid,
            'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z',
            'DTSTART;VALUE=DATE:' + fecha,
            'DTEND;VALUE=DATE:' + fecha,
            'SUMMARY:' + titulo,
            'DESCRIPTION:' + actividadesTexto,
            'STATUS:CONFIRMED',
            'TRANSP:TRANSPARENT',
            'SEQUENCE:0',
            'END:VEVENT',
            'END:VCALENDAR'
        ].join('\r\n');

        // Descargar archivo
        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `dia-<?php echo $numero_dia; ?>-<?php echo $viaje['slug']; ?>.ics`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);

        showToast('¡Archivo de calendario descargado!');
        closeMenu();
    });
    </script>
</body>
</html>