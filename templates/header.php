<?php
/**
 * Titre: Header modulaire du portail Guldagil
 * Chemin: /templates/header.php
 */

// Définitions par défaut
$page_title         = $page_title         ?? 'Portail Guldagil';
$page_subtitle      = $page_subtitle      ?? 'Portail d\'outils professionnels';
$current_module     = $current_module     ?? 'home';
$user_authenticated = $user_authenticated ?? false;

// Fil d'Ariane
$breadcrumbs        = $breadcrumbs        ?? [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars(
      $page_description ?? 'Portail d\'outils Guldagil - Solutions professionnelles'
  ) ?>">
  <meta name="author" content="Jean-Thomas RUNSER">
  <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="/assets/img/favicon.png">

  <!-- CSS principal -->
  <link rel="stylesheet" href="/assets/css/portal.css" />

  <!-- CSS spécifique au module si besoin -->
  <?php if (!empty($module_css)): ?>
    <link rel="stylesheet" href="/assets/css/modules/<?= htmlspecialchars($current_module) ?>.css">
  <?php endif; ?>
</head>
<body class="portal-body module-<?= htmlspecialchars($current_module) ?>" data-module="<?= htmlspecialchars($current_module) ?>">

  <!-- Header principal -->
  <header class="portal-header">
    <div class="header-container">

      <!-- Logo + titre -->
      <div class="header-brand" onclick="goHome()" role="button" tabindex="0" aria-label="Retour à l'accueil">
        <img src="/assets/img/logo.png" alt="Guldagil" class="portal-logo">
        <div class="brand-info">
          <h1 class="portal-title"><?= htmlspecialchars($page_title) ?></h1>
          <p class="portal-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
      </div>

      <!-- Section utilisateur -->
      <div class="header-actions">
  <div class="user-dropdown-container" style="position: relative;">
    <div class="user-section">
      <?php if (!empty($current_user)): ?>
        <span class="user-icon">👤</span>
        <span class="user-text"><?= htmlspecialchars($current_user['username']) ?></span>
        <span class="user-role">(<?= htmlspecialchars($current_user['role']) ?>)</span>
        <span class="user-dropdown">▼</span>
      <?php else: ?>
        <span class="user-icon">👤</span>
        <span class="user-text">Connexion</span>
      <?php endif; ?>
    </div>
    <?php if (!empty($current_user)): ?>
      <div class="user-dropdown-menu" id="user-menu" style="display: none;">
        <a href="/profile" class="dropdown-item">
          <span class="item-icon">👤</span>
          <span class="item-text">Mon profil</span>
        </a>
        <a href="/settings" class="dropdown-item">
          <span class="item-icon">⚙️</span>
          <span class="item-text">Paramètres</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="/auth/logout.php" class="dropdown-item logout">
          <span class="item-icon">🚪</span>
          <span class="item-text">Déconnexion</span>
        </a>
      </div>
    <?php endif; ?>
  </div>
  <!-- Ici, tu peux mettre d'autres boutons header à droite si besoin -->
</div>

      </div>
    </div>
  </header>

  <!-- Navigation / Breadcrumbs -->
  <nav class="portal-nav" role="navigation" aria-label="Navigation principale">
    <div class="nav-container">
      <div class="nav-breadcrumb" role="breadcrumb" aria-label="Fil d'Ariane">
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
          <?php if ($i > 0): ?><span class="breadcrumb-separator" aria-hidden="true">›</span><?php endif; ?>
          <?php if (!empty($crumb['active'])): ?>
            <span class="breadcrumb-item active" aria-current="page"><?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?></span>
          <?php else: ?>
            <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item"><?= $crumb['icon'] ?? '' ?> <?= htmlspecialchars($crumb['text']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($nav_info)): ?>
        <div class="nav-info"><span class="nav-text"><?= htmlspecialchars($nav_info) ?></span></div>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Contenu principal -->
  <main class="portal-main" role="main">
    <?php if (!empty($_SESSION['flash_messages'])): ?>
      <div class="flash-messages">
        <?php foreach ($_SESSION['flash_messages'] as $type => $msgs): foreach ($msgs as $msg): ?>
          <div class="flash-message flash-<?= htmlspecialchars($type) ?>" role="alert">
            <span class="flash-icon"><?= $type==='success'?'✅':($type==='error'?'❌':($type==='warning'?'⚠️':'ℹ️')) ?></span>
            <span class="flash-text"><?= htmlspecialchars($msg) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()" aria-label="Fermer">×</button>
          </div>
        <?php endforeach; endforeach; unset($_SESSION['flash_messages']); ?>
      </div>
    <?php endif; ?>

    <div class="main-container">

       <script>
// Fix pour templates/header.php - remplacer le script existant par :
document.addEventListener('DOMContentLoaded', function() {
  var userSection = document.querySelector('.user-section');
  var userMenu = document.getElementById('user-menu');
  var dropdownArrow = userSection ? userSection.querySelector('.user-dropdown') : null;

  if(userSection && userMenu) {
    userSection.addEventListener('click', function(e) {
      e.stopPropagation();
      
      // Toggle menu visibility
      var isVisible = userMenu.style.display === 'block';
      userMenu.style.display = isVisible ? 'none' : 'block';
      
      // Toggle arrow
      if(dropdownArrow) {
        dropdownArrow.classList.toggle('open', !isVisible);
      }
    });

    // Fermer le menu en cliquant ailleurs
    document.addEventListener('click', function(e) {
      if (!userSection.contains(e.target) && !userMenu.contains(e.target)) {
        userMenu.style.display = 'none';
        if(dropdownArrow) dropdownArrow.classList.remove('open');
      }
    });
  }
});
</script>
