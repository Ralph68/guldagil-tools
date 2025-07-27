<?php
// Protection contre l'accès direct si besoin
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Traitement du formulaire
$success = false;
$error = '';
$name = '';
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $error = "Merci de préciser votre demande.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        // Préparation de l'email
        $to = 'runser.jean.thomas@guldagil.com';
        $subject = 'Contact portail Guldagil';
        $headers = "From: portail@guldagil.com\r\n";
        if (!empty($email)) {
            $headers .= "Reply-To: " . $email . "\r\n";
        }
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        $body = "Nom: $name\nEmail: $email\n\nMessage:\n$message";

        // Envoi de l'email
        if (mail($to, $subject, $body, $headers)) {
            $success = true;
            $name = $email = $message = '';
        } else {
            $error = "Erreur lors de l'envoi. Merci de réessayer plus tard.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contact - Portail Guldagil</title>
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/css/contact.css?v=<?= time() ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <main class="contact-main">
        <section class="contact-section">
            <h1>Contactez-nous</h1>
            <?php if ($success): ?>
                <div class="contact-success">Votre message a bien été envoyé. Merci !</div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="contact-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" class="contact-form" novalidate>
                    <div class="form-group">
                        <label for="name">Nom</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email (optionnel)</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="message">Votre demande <span class="required">*</span></label>
                        <textarea id="message" name="message" required rows="5"><?= htmlspecialchars($message) ?></textarea>
                    </div>
                    <button type="submit" class="contact-btn">Envoyer</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
