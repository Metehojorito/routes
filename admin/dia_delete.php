<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $db = getDB();
    
    try {
        // Obtener viaje_id antes de eliminar
        $stmt = $db->prepare("SELECT viaje_id FROM dias_viaje WHERE id = ?");
        $stmt->execute([$id]);
        $dia = $stmt->fetch();
        
        if ($dia) {
            $viaje_id = $dia['viaje_id'];
            
            // Eliminar día (cascade eliminará actividades y secciones)
            $stmt = $db->prepare("DELETE FROM dias_viaje WHERE id = ?");
            $stmt->execute([$id]);
            
            header("Location: dias_list.php?viaje_id=$viaje_id&deleted=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;