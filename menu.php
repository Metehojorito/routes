<?php
require_once 'config/database.php';

$db = getDB();

// Obtener viaje actual (desde parámetro o sesión)
$viaje = getViajeActual();

if (!$viaje) {
    die("No hay viajes disponibles");
}

// Obtener colores personalizados
$colores = getViajeColores($viaje['id']);

// Obtener días del viaje
$stmt = $db->prepare("SELECT * FROM dias_viaje WHERE viaje_id = ? AND visible = 1 ORDER BY orden, numero_dia");
$stmt->execute([$viaje['id']]);
$dias = $stmt->fetchAll();

// Obtener alojamiento
$stmt = $db->prepare("SELECT * FROM alojamientos WHERE viaje_id = ? LIMIT 1");
$stmt->execute([$viaje['id']]);
$alojamiento = $stmt->fetch();

// Obtener contactos de emergencia
$stmt = $db->prepare("SELECT * FROM contactos_emergencia WHERE viaje_id = ? ORDER BY orden");
$stmt->execute([$viaje['id']]);
$contactos = $stmt->fetchAll();

$meses = ['', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo htmlspecialchars($viaje['titulo']); ?> - Plan de Viaje</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body, html { height: 100dvh; margin: 0; padding: 0; overflow: hidden; }
    #drawer { pointer-events: none; }
    #overlay { opacity: 0; transition: opacity 0.25s ease; pointer-events: none; }
    #sidebar { transform: translateX(-100%); transition: transform 0.28s cubic-bezier(.2,.9,.2,1); }
    #drawer.open { pointer-events: auto; }
    #drawer.open #overlay { opacity: 1; pointer-events: auto; }
    #drawer.open #sidebar { transform: translateX(0); }
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
    <div id="drawer" class="fixed inset-0 z-40 flex">
        <aside id="sidebar" class="relative flex w-80 flex-col bg-background-light p-6 dark:bg-background-dark">
            <button id="btnClose" class="absolute right-4 top-4 flex size-8 items-center justify-center rounded-full text-text-light-secondary hover:bg-black/10 dark:text-text-dark-secondary dark:hover:bg-white/10">
                <span class="material-symbols-outlined text-2xl">close</span>
            </button>
            <nav class="flex flex-col gap-2 mt-8">
                <?php if ($alojamiento): ?>
                <div class="flex min-h-[72px] items-start gap-4 rounded-lg bg-card-light p-3 shadow-sm dark:bg-card-dark">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary/20 text-primary">
                        <span class="material-symbols-outlined text-2xl">home</span>
                    </div>
                    <div class="flex flex-1 flex-col justify-center">
                        <p class="font-medium leading-normal text-text-light-primary dark:text-text-dark-primary"><?php echo htmlspecialchars($alojamiento['nombre']); ?></p>
                        <div class="mt-2 flex flex-col gap-1">
                            <?php if ($alojamiento['direccion']): ?>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-text-light-secondary dark:text-text-dark-secondary">location_on</span>
                                <p class="text-xs text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($alojamiento['direccion']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($alojamiento['telefono']): ?>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-text-light-secondary dark:text-text-dark-secondary">call</span>
                                <p class="text-xs text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($alojamiento['telefono']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php foreach ($contactos as $contacto): ?>
                <div class="flex min-h-[72px] items-start gap-4 rounded-lg bg-card-light p-3 shadow-sm dark:bg-card-dark">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary/20 text-primary">
                        <span class="material-symbols-outlined text-2xl"><?php echo htmlspecialchars($contacto['icono']); ?></span>
                    </div>
                    <div class="flex flex-1 flex-col justify-center">
                        <p class="font-medium leading-normal text-text-light-primary dark:text-text-dark-primary"><?php echo htmlspecialchars($contacto['nombre']); ?></p>
                        <?php if ($contacto['descripcion']): ?>
                        <p class="text-sm font-normal leading-normal text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($contacto['descripcion']); ?></p>
                        <?php endif; ?>
                        <div class="mt-2 flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-text-light-secondary dark:text-text-dark-secondary">call</span>
                                <p class="text-xs text-text-light-secondary dark:text-text-dark-secondary"><?php echo htmlspecialchars($contacto['telefono']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </nav>
        </aside>
        <div id="overlay" class="flex-1 bg-black/40"></div>
    </div>
    
    <div class="relative flex h-screen w-full flex-col">
        <header class="sticky top-0 z-10 flex items-center justify-between bg-background-light/80 p-4 pb-2 backdrop-blur-sm dark:bg-background-dark/80">
            <button id="btnMenu" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-text-light-primary dark:text-text-dark-primary">
                <span class="material-symbols-outlined text-2xl">menu</span>
            </button>
            <h1 class="text-lg font-bold leading-tight tracking-[-0.015em] text-text-light-primary dark:text-text-dark-primary">Mi Viaje</h1>
            <div class="size-10"></div>
        </header>
        
        <main class="flex-1 overflow-y-auto p-4">
            <div class="mb-6">
                <h2 class="text-3xl font-bold leading-tight tracking-[-0.015em] text-text-light-primary dark:text-text-dark-primary"><?php echo htmlspecialchars($viaje['titulo']); ?></h2>
                <p class="text-base text-text-light-secondary dark:text-text-dark-secondary">
                    <?php 
                    $fecha_inicio = new DateTime($viaje['fecha_inicio']);
                    $fecha_fin = new DateTime($viaje['fecha_fin']);
                    echo $fecha_inicio->format('d') . ' de ' . $meses[(int)$fecha_inicio->format('n')] . ' - ' . $fecha_fin->format('d') . ' de ' . $meses[(int)$fecha_fin->format('n')];
                    ?>
                </p>
            </div>
            
            <div class="space-y-4">
                <?php foreach ($dias as $dia): 
                    $fecha = new DateTime($dia['fecha']);
                    $mes = $meses[(int)$fecha->format('n')];
                    $dia_num = $fecha->format('d');
                    $dia_semana = $dias_semana[(int)$fecha->format('w')];
                ?>
                <div onclick="window.location.href='dia.php?dia=<?php echo $dia['numero_dia']; ?>'" class="flex cursor-pointer items-center gap-4 rounded-xl bg-card-light p-4 shadow-sm transition-transform duration-200 ease-in-out active:scale-95 dark:bg-card-dark">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/20">
                        <div class="text-center">
                            <p class="text-sm font-semibold leading-none"><?php echo $mes; ?></p>
                            <p class="text-xl font-bold leading-none"><?php echo $dia_num; ?></p>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold leading-normal text-text-light-primary dark:text-text-dark-primary"><?php echo htmlspecialchars($dia['titulo']); ?></p>
                        <p class="text-sm text-text-light-secondary dark:text-text-dark-secondary"><?php echo $dia_semana; ?></p>
                    </div>
                    <span class="material-symbols-outlined text-text-light-secondary dark:text-text-dark-secondary">arrow_forward_ios</span>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <script>
    (function(){
        const btnMenu = document.getElementById('btnMenu');
        const drawer = document.getElementById('drawer');
        const btnClose = document.getElementById('btnClose');
        const overlay = document.getElementById('overlay');
        
        function openDrawer() { drawer.classList.add('open'); btnClose.focus(); }
        function closeDrawer() { drawer.classList.remove('open'); btnMenu.focus(); }
        
        btnMenu.addEventListener('click', (e) => { e.stopPropagation(); openDrawer(); });
        btnClose.addEventListener('click', (e) => { e.stopPropagation(); closeDrawer(); });
        overlay.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer(); });
    })();
    </script>
</body>
</html>