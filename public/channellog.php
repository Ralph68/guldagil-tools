<?php
// Protection contre l'accès direct si besoin
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Titre et description de la page
$page_title = "Journal des évolutions - Portail Guldagil";
$page_description = "Suivi professionnel des évolutions, nouveautés, corrections de bugs et travaux en cours du portail Guldagil. Les informations sont synchronisées avec le dépôt GitHub privé du projet. Accès au dépôt possible pour les collaborateurs sur demande et validation.";

// TODO: Automatiser la récupération du changelog depuis GitHub (API ou fichier CHANGELOG.md)
// Pour l'instant, les entrées sont saisies manuellement.
$changelog = [
    [
        'date' => '2024-06-10',
        'type' => 'Nouveauté',
        'title' => 'Ajout du formulaire de contact',
        'desc' => 'Un formulaire de contact sécurisé est disponible pour tous les utilisateurs.'
    ],
    [
        'date' => '2024-06-09',
        'type' => 'Correction',
        'title' => 'Correction affichage du logo',
        'desc' => 'Le logo s\'affiche désormais uniquement dans le header, plus dans le footer.'
    ],
    [
        'date' => '2024-06-08',
        'type' => 'Amélioration',
        'title' => 'Header adaptatif',
        'desc' => 'Le header devient compact au scroll et affiche le nom du module.'
    ],
    [
        'date' => '2024-06-07',
        'type' => 'Bug',
        'title' => 'Correction navigation modules',
        'desc' => 'Le menu modules reste visible au chargement et en mode compact.'
    ],
    // ... Ajouter d'autres entrées ici ...
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= time() ?>">
    <style>
        /* Styles spécifiques changelog */
        .changelog-main {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem 1rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        }
        .changelog-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue, #3182ce);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .changelog-desc {
            color: var(--gray-600, #4b5563);
            text-align: center;
            margin-bottom: 2rem;
        }
        .changelog-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .changelog-item {
            border-left: 4px solid var(--primary-blue, #3182ce);
            background: var(--gray-50, #f9fafb);
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            position: relative;
        }
        .changelog-date {
            font-size: 0.95rem;
            color: var(--gray-500, #6b7280);
            margin-bottom: 0.2rem;
        }
        .changelog-type {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.15em 0.7em;
            border-radius: 1em;
            margin-right: 0.7em;
            background: #e0e7ff;
            color: #3730a3;
        }
        .changelog-type.Correction { background: #fee2e2; color: #b91c1c; }
        .changelog-type.Bug { background: #fef3c7; color: #92400e; }
        .changelog-type.Amélioration { background: #d1fae5; color: #065f46; }
        .changelog-type.Nouveauté { background: #dbeafe; color: #1e40af; }
        .changelog-title-item {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        .changelog-desc-item {
            font-size: 1rem;
            color: var(--gray-700, #374151);
        }
        @media (max-width: 600px) {
            .changelog-main { padding: 1rem 0.3rem; }
        }
    </style>
</head>
<body>
    <main class="changelog-main">
        <h1 class="changelog-title"><?= htmlspecialchars($page_title) ?></h1>
        <div class="changelog-desc">
            <?= htmlspecialchars($page_description) ?><br>
            <span style="display:block;margin-top:1rem;font-size:0.98em;color:#2563eb;">
                <strong>Accès au dépôt GitHub :</strong> Le projet est privé.<br>
                Les collaborateurs souhaitant accéder au code source ou suivre les évolutions détaillées peuvent en faire la demande auprès de l’administrateur.<br>
                L’accès sera accordé après validation.
            </span>
        </div>
        <ul class="changelog-list">
            <?php foreach ($changelog as $entry): ?>
                <li class="changelog-item">
                    <div class="changelog-date"><?= htmlspecialchars($entry['date']) ?></div>
                    <span class="changelog-type <?= htmlspecialchars($entry['type']) ?>">
                        <?= htmlspecialchars($entry['type']) ?>
                    </span>
                    <span class="changelog-title-item"><?= htmlspecialchars($entry['title']) ?></span>
                    <div class="changelog-desc-item"><?= htmlspecialchars($entry['desc']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <!-- TODO: Automatiser la synchronisation avec le changelog GitHub (API ou markdown) -->
    </main>
</body>
</html>
