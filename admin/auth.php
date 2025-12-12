<?php
/**
 * Sistema de Autenticación para Admin
 * 
 * INCLUIR AL INICIO DE CADA ARCHIVO DEL ADMIN (excepto login.php y logout.php):
 * require_once 'auth.php';
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Verificar autenticación y redirigir si no está logueado
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Obtener datos del usuario logueado
function getLoggedUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_user_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null
    ];
}

// Cerrar sesión
function logout() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// PROTEGER LA PÁGINA ACTUAL
// Esta línea debe estar en todos los archivos admin excepto login.php y logout.php
requireAuth();