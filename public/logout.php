<?php
/**
 * Page de déconnexion - Gul Calc Frais de port
 * Chemin : /public/logout.php
 * Version : 0.5 beta
 */

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php?logout=1');
exit;
