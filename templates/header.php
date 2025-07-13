<?php
/**
 * Header minimal temporaire - sans erreurs
 * Remplace /templates/header.php
 */

// Variables avec fallbacks sécurisés
$page_title = $page_title ?? 'Portail Guldagil';
$app_name = $app_name ?? 'Guldagil';
$current_user = $current_user ?? ['username' => 'Test', 'role' => 'user'];
$user_authenticated = $user_authenticated ?? false;
$build_number = $build_number ?? '00000000';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($app_name) ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .header { 
            background: #2563eb; 
            color: white; 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .user-info { 
            text-align: right; 
            font-size: 0.9em; 
        }
    </style>
</head>
<body>
    <header class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin: 0;"><?= htmlspecialchars($app_name) ?></h1>
            <div class="user-info">
                <?php if ($user_authenticated): ?>
                    Connecté: <?= htmlspecialchars($current_user['username']) ?> 
                    | <a href="/auth/logout.php" style="color: white;">Déconnexion</a>
                <?php else: ?>
                    <a href="/auth/login.php" style="color: white;">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1><?= htmlspecialchars($page_title) ?></h1>
