<?php
/**
 * Configuración de la base de datos
 */

// Configuración de conexión
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestor_viajes');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Clase de conexión a la base de datos
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para obtener configuración
function getConfig($clave, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    $stmt->execute([$clave]);
    $result = $stmt->fetch();
    return $result ? $result['valor'] : $default;
}

// Función para obtener colores del viaje
function getViajeColores($viaje_id = null, $slug = null) {
    $db = getDB();
    
    if ($viaje_id) {
        $stmt = $db->prepare("SELECT color_primary, color_secondary, color_bg_light, color_bg_dark, color_card_light, color_card_dark FROM viajes WHERE id = ?");
        $stmt->execute([$viaje_id]);
    } elseif ($slug) {
        $stmt = $db->prepare("SELECT color_primary, color_secondary, color_bg_light, color_bg_dark, color_card_light, color_card_dark FROM viajes WHERE slug = ?");
        $stmt->execute([$slug]);
    } else {
        // Obtener el primer viaje activo
        $stmt = $db->prepare("SELECT color_primary, color_secondary, color_bg_light, color_bg_dark, color_card_light, color_card_dark FROM viajes WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1");
        $stmt->execute();
    }
    
    $colores = $stmt->fetch();
    
    // Si no se encuentran colores, devolver los por defecto
    if (!$colores) {
        return [
            'color_primary' => '#4A90E2',
            'color_secondary' => '#F5A623',
            'color_bg_light' => '#F4F4F8',
            'color_bg_dark' => '#101922',
            'color_card_light' => '#FFFFFF',
            'color_card_dark' => '#1c2c3a'
        ];
    }
    
    return $colores;
}

// Función para generar CSS dinámico con colores personalizados
function generarCSSColores($colores) {
    return "
    <style>
        :root {
            --color-primary: {$colores['color_primary']};
            --color-secondary: {$colores['color_secondary']};
            --color-bg-light: {$colores['color_bg_light']};
            --color-bg-dark: {$colores['color_bg_dark']};
            --color-card-light: {$colores['color_card_light']};
            --color-card-dark: {$colores['color_card_dark']};
        }
    </style>
    ";
}

// ====================================================
// SISTEMA DE SESIÓN PARA VIAJE ACTUAL
// ====================================================

// Iniciar sesión si no está iniciada
function initPublicSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Establecer viaje actual en sesión
function setViajeActual($viaje_id, $slug) {
    initPublicSession();
    $_SESSION['viaje_actual_id'] = $viaje_id;
    $_SESSION['viaje_actual_slug'] = $slug;
}

// Obtener viaje actual desde sesión o parámetro
function getViajeActual() {
    initPublicSession();
    $db = getDB();
    
    // 1. Verificar si viene por parámetro ?viaje=slug
    $slug_param = $_GET['viaje'] ?? null;
    
    if ($slug_param) {
        // Buscar viaje por slug del parámetro
        $stmt = $db->prepare("SELECT * FROM viajes WHERE slug = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$slug_param]);
        $viaje = $stmt->fetch();
        
        if ($viaje) {
            // Guardar en sesión
            setViajeActual($viaje['id'], $viaje['slug']);
            return $viaje;
        }
    }
    
    // 2. Verificar si hay viaje en sesión
    if (isset($_SESSION['viaje_actual_id'])) {
        $stmt = $db->prepare("SELECT * FROM viajes WHERE id = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$_SESSION['viaje_actual_id']]);
        $viaje = $stmt->fetch();
        
        if ($viaje) {
            return $viaje;
        }
    }
    
    // 3. Obtener el primer viaje activo por defecto
    $stmt = $db->prepare("SELECT * FROM viajes WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1");
    $stmt->execute();
    $viaje = $stmt->fetch();
    
    if ($viaje) {
        setViajeActual($viaje['id'], $viaje['slug']);
        return $viaje;
    }
    
    return null;
}