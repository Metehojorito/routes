<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $db = getDB();
    
    try {
        // Obtener título para confirmación
        $stmt = $db->prepare("SELECT titulo FROM viajes WHERE id = ?");
        $stmt->execute([$id]);
        $viaje = $stmt->fetch();
        
        if ($viaje) {
            // Eliminar viaje (cascade eliminará todo lo relacionado)
            $stmt = $db->prepare("DELETE FROM viajes WHERE id = ?");
            $stmt->execute([$id]);
            
            header("Location: index.php?deleted=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;