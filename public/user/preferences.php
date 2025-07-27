<?php
/**
 * Titre: Préférences utilisateur
 * Chemin: /public/user/preferences.php
 * Version: 0.5 beta + build auto
 */

// Vérification de l'accès utilisateur
include_once '../includes/access.php';

// Inclusion des fichiers nécessaires
include_once '../config/config.php';
include_once '../includes/functions.php';

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de la connexion utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}

// Récupération des préférences utilisateur
$user_id = $_SESSION['user_id'];
$preferences = getUserPreferences($user_id);

// Mise à jour des préférences si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_preferences = $_POST['preferences'] ?? [];
    updateUserPreferences($user_id, $new_preferences);
    $preferences = array_merge($preferences, $new_preferences);
}

// Inclusion du header
include ROOT_PATH . '/templates/header.php';
?>

<main class="user-preferences">
    <h1>Préférences utilisateur</h1>

    <form id="preferences-form">
        <div class="form-group">
            <label for="language">Langue</label>
            <select id="language" name="language" class="form-control">
                <option value="fr" <?= $preferences['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                <option value="en" <?= $preferences['language'] === 'en' ? 'selected' : '' ?>>English</option>
            </select>
        </div>

        <div class="form-group">
            <label for="timezone">Fuseau horaire</label>
            <select id="timezone" name="timezone" class="form-control">
                <!-- Options de fuseau horaire -->
            </select>
        </div>

        <div class="form-group">
            <label for="notifications">Notifications</label>
            <input type="checkbox" id="notifications" name="notifications" value="1" <?= $preferences['notifications'] ? 'checked' : '' ?>>
        </div>

        <button type="submit" id="save-preferences-btn" class="btn btn-primary">Sauvegarder les préférences</button>
    </form>
</main>

<?php include ROOT_PATH . '/templates/footer.php'; ?>