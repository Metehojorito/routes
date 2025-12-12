<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;

if ($id > 0) {
    $db = getDB();
    
    try {
        // Si no tenemos viaje_id, obtenerlo del alojamiento
        if ($viaje_id == 0) {
            $stmt = $db->prepare("SELECT viaje_id FROM alojamientos WHERE id = ?");
            $stmt->execute([$id]);
            $alojamiento = $stmt->fetch();
            if ($alojamiento) {
                $viaje_id = $alojamiento['viaje_id'];
            }
        }
        
        // Eliminar alojamiento
        $stmt = $db->prepare("DELETE FROM alojamientos WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: alojamiento_form.php?viaje_id=$viaje_id&deleted=1");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;