<?php
require_once 'config/database.php';

// Obtener viaje actual (desde parámetro o sesión)
$viaje = getViajeActual();

// Si no hay viaje seleccionado, mostrar página en blanco
if (!$viaje) {
    http_response_code(200);
    exit;
}

// Obtener colores personalizados del viaje
$colores = getViajeColores($viaje['id']);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo htmlspecialchars($viaje['titulo']); ?> - Bienvenida</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#137fec",
                    "background-light": "#f6f7f8",
                    "background-dark": "#101922",
                },
                fontFamily: {
                    "display": ["Plus Jakarta Sans", "Noto Sans", "sans-serif"]
                },
                borderRadius: {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
            },
        },
    }
    </script>
    <style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    body, html {
        height: 100dvh;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark">
    <div class="relative flex h-[100dvh] w-full flex-col font-display overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" style='background-image: linear-gradient(180deg, rgba(16, 25, 34, 0.6) 0%, rgba(16, 25, 34, 0.4) 30%, rgba(16, 25, 34, 0.8) 100%), url("<?php echo htmlspecialchars($viaje['imagen_portada']); ?>");'></div>
        
        <div class="relative flex flex-1 flex-col justify-end p-6 pb-8 text-center text-white">
            <div class="flex flex-1 flex-col items-center justify-center">
                <h1 class="text-white tracking-tight text-5xl font-bold leading-tight drop-shadow-lg">
                    <?php echo htmlspecialchars($viaje['titulo']); ?>
                </h1>
                <p class="text-white/90 text-lg font-normal leading-normal mt-3 max-w-sm drop-shadow-md">
                    <?php echo htmlspecialchars($viaje['descripcion']); ?>
                </p>
            </div>
            
            <div class="flex w-full pt-8">
                <button onclick="window.location.href='menu.php'" class="flex min-w-[84px] max-w-full cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 px-5 flex-1 bg-[#ffae00] text-gray-900 text-lg font-bold leading-normal tracking-[0.015em] shadow-lg transition-transform active:scale-95">
                    <span class="truncate">Empezar</span>
                </button>
            </div>
        </div>
    </div>
</body>
</html>