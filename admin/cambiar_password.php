<?php
require_once 'auth.php';
require_once '../config/database.php';

$error = '';
$success = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($password_nueva !== $password_confirmar) {
        $error = 'Las contraseñas nuevas no coinciden';
    } elseif (strlen($password_nueva) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        $db = getDB();
        
        // Verificar contraseña actual
        $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password_actual, $user['password'])) {
            $error = 'La contraseña actual es incorrecta';
        } else {
            // Actualizar contraseña
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $stmt->execute([$password_hash, $_SESSION['admin_user_id']]);
            
            $success = 'Contraseña actualizada correctamente';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
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
                        <h1 class="text-3xl font-bold text-gray-900">Cambiar Contraseña</h1>
                    </div>
                    <span class="text-sm text-gray-600">
                        👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

            <form method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña Actual *</label>
                    <input type="password" name="password_actual" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña *</label>
                    <input type="password" name="password_nueva" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Mínimo 6 caracteres</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nueva Contraseña *</label>
                    <input type="password" name="password_confirmar" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                    <p class="text-sm text-yellow-700">
                        <strong>⚠️ Importante:</strong> Después de cambiar tu contraseña, tendrás que volver a iniciar sesión.
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="index.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition">
                        Cambiar Contraseña
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>