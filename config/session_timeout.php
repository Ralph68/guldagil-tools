<?php
/**
 * Titre: Correction configuration sessions - Timeout 9h30
 * Chemin: /config/session_timeout.php 
 * Version: 0.5 beta + build auto
 * 
 * CHANGEMENTS CRITIQUES :
 * 1. Durée session 9h30 au lieu de 8h
 * 2. Configuration PHP ini correcte
 * 3. Cookie lifetime aligné
 */

// =====================================
// 🕘 CONFIGURATION SESSION 9H30
// =====================================

// Durée session 9h30 (34200 secondes) comme demandé
define('SESSION_TIMEOUT', 34200); // 9h30 = 9.5 * 60 * 60

// Configuration PHP pour sessions longues
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);

// Paramètres cookie session sécurisés
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Nom session unique pour éviter conflits
ini_set('session.name', 'GULDAGIL_PORTAL_SESSION');

// Gestion garbage collection adaptée
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Log pour debug
error_log("SESSION CONFIG: Timeout set to " . SESSION_TIMEOUT . " seconds (9h30)");

?>