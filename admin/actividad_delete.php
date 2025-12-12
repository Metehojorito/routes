<?php
require_once 'auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $db = getDB();
    
    try {
        // Obtener dia_id antes de eliminar
        $stmt = $db->prepare("SELECT dia_id FROM actividades WHERE id = ?");
        $stmt->execute([$id]);
        $actividad = $stmt->fetch();
        
        if ($actividad) {
            $dia_id = $actividad['dia_id'];
            
            // Eliminar actividad (cascade eliminará detalles)
            $stmt = $db->prepare("DELETE FROM actividades WHERE id = ?");
            $stmt->execute([$id]);
            
            header("Location: actividades_list.php?dia_id=$dia_id&deleted=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;