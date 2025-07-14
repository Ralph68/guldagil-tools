<?php
/**
 * Titre: Page 404 - Page non trouvée
 * Chemin: /public/errors/404.php
 * Version: 0.5 beta + build auto
 */

http_response_code(404);
header('Location: /error.php?type=404&code=404&message=' . urlencode('La page demandée n\'existe pas'));
exit;
?>
