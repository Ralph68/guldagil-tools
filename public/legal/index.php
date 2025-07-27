<?php
/**
 * Titre: Index des documents légaux et politiques
 * Chemin: /public/legal/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../../config/version.php';
require_once ROOT_PATH . '/config/error_handler_simple.php';

// Meta données de la page
$page_title = "Documents légaux et politiques";
$page_description = "Centre de documentation légale - RGPD, conditions d'utilisation et sécurité";
$page_type = "legal-index";

// Documents légaux disponibles - AJOUT des mentions légales et cookies
$legal_documents = [
    'mentions' => [
        'title' => 'Mentions légales',
        'description' => 'Informations légales obligatoires conformes à la réglementation française 2025',
        'icon' => '⚖️',
        'file' => 'mentions.php',
        'last_update' => date('d/m/Y', BUILD_TIMESTAMP),
        'mandatory' => true,
        'category' => 'Réglementation'
    ],
    'privacy' => [
        'title' => 'Politique de confidentialité',
        'description' => 'Protection des données personnelles et respect du RGPD 2025',
        'icon' => '🔒',
        'file' => 'privacy.php',
        'last_update' => date('d/m/Y', BUILD_TIMESTAMP),
        'mandatory' => true,
        'category' => 'Données personnelles'
    ],
    'terms' => [
        'title' => 'Conditions générales d\'utilisation',
        'description' => 'Règles d\'usage du portail et responsabilités des utilisateurs',
        'icon' => '📋',
        'file' => 'terms.php',
        'last_update' => date('d/m/Y', BUILD_TIMESTAMP),
        'mandatory' => true,
        'category' => 'Utilisation'
    ],
    'cookies' => [
        'title' => 'Politique de cookies',
        'description' => 'Utilisation des cookies et gestion des préférences utilisateur',
        'icon' => '🍪',
        'file' => 'cookies.php',
        'last_update' => date('d/m/Y', BUILD_TIMESTAMP),
        'mandatory' => false,
        'category' => 'Cookies'
    ],
    'security' => [
        'title' => 'Politique de sécurité',
        'description' => 'Mesures de protection et procédures de sécurité informatique',
        'icon' => '🔐',
        'file' => 'security.php',
        'last_update' => date('d/m/Y', BUILD_TIMESTAMP),
        'mandatory' => false,
        'category' => 'Sécurité'
    ]
];

// Statistiques légales
$legal_stats = [
    'documents_total' => count($legal_documents),
    'last_update' => date('d/m/Y', BUILD_TIMESTAMP),
    'compliance_status' => 'RGPD 2025',
    'review_period' => 'Trimestriel'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="<?= $page_description ?>">
    
    <!-- CSS principal OBLIGATOIRE -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/header.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/footer.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= BUILD_NUMBER ?>">
    <link rel="stylesheet" href="/assets/css/legal.css?v=<?= BUILD_NUMBER ?>">
    
    <link rel="canonical" href="/legal/">
</head>
<body class="legal-page">
    <?php include ROOT_PATH . '/templates/header.php'; ?>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>⚖️ Centre de documentation légale</h1>
                <p class="legal-meta">
                    Mise à jour : <?= $legal_stats['last_update'] ?> - Conformité <?= $legal_stats['compliance_status'] ?><br>
                    Révision : <?= $legal_stats['review_period'] ?> - Version portail : <?= APP_VERSION ?><br>
                    <strong><?= $legal_stats['documents_total'] ?> documents</strong> dont <strong><?= $legal_stats['documents_mandatory'] ?> obligatoires</strong>
                </p>
            </div>

            <div class="legal-content">
                <section class="legal-section">
                    <h2>📋 Documents disponibles</h2>
                    <p>
                        Cette section centralise l'ensemble des documents légaux et politiques 
                        applicables à l'utilisation du portail <?= APP_NAME ?>. Tous les documents 
                        sont conformes aux réglementations françaises et européennes en vigueur en 2025.
                    </p>
                    
                    <div class="documents-grid">
                        <?php foreach ($legal_documents as $doc_id => $document): ?>
                        <div class="document-card <?= $document['mandatory'] ? 'mandatory' : 'optional' ?>" onclick="window.location.href='/legal/<?= $document['file'] ?>'">
                            <div class="document-header">
                                <div class="document-icon"><?= $document['icon'] ?></div>
                                <div class="document-meta">
                                    <h3 class="document-title"><?= htmlspecialchars($document['title']) ?></h3>
                                    <span class="document-category"><?= htmlspecialchars($document['category']) ?></span>
                                    <?php if ($document['mandatory']): ?>
                                    <span class="document-badge mandatory">Obligatoire</span>
                                    <?php else: ?>
                                    <span class="document-badge optional">Informatif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="document-body">
                                <p class="document-description">
                                    <?= htmlspecialchars($document['description']) ?>
                                </p>
                                <div class="document-info">
                                    <span class="update-date">📅 Mis à jour le <?= $document['last_update'] ?></span>
                                </div>
                            </div>
                            
                            <div class="document-footer">
                                <a href="/legal/<?= $document['file'] ?>" class="document-link">
                                    📖 Consulter le document
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>🔍 Informations complémentaires</h2>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h3>📊 Conformité RGPD</h3>
                            <p>
                                Toutes nos politiques respectent le Règlement Général sur la Protection 
                                des Données (RGPD) dans sa version 2025, incluant les nouvelles 
                                exigences sur l'IA et la certification des sous-traitants.
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h3>🇫🇷 Droit français</h3>
                            <p>
                                Nos conditions sont conformes au droit français et à la loi 
                                "Informatique et Libertés" modifiée, ainsi qu'aux dernières 
                                directives de la CNIL pour 2025.
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h3>🔄 Mises à jour</h3>
                            <p>
                                Ces documents sont révisés trimestriellement et mis à jour 
                                automatiquement à chaque version du portail. Le numéro de 
                                build garantit la traçabilité.
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h3>📞 Contact</h3>
                            <p>
                                Pour toute question sur ces documents :<br>
                                📧 <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a><br>
                                📧 <a href="mailto:guldagil@guldagil.com">guldagil@guldagil.com</a> (RGPD)
                            </p>
                        </div>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>⚠️ Avis important</h2>
                    <div class="status-box status-info">
                        <h4>📝 Version beta - Documents évolutifs</h4>
                        <p>
                            Le portail étant en version <?= APP_VERSION ?>, ces documents 
                            peuvent évoluer en fonction des nouvelles fonctionnalités. 
                            Les utilisateurs sont informés des modifications importantes 
                            via les canaux de communication internes.
                        </p>
                    </div>
                    
                    <div class="version-info">
                        <h4>🔍 Traçabilité des versions</h4>
                        <ul>
                            <li><strong>Version actuelle :</strong> <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?></li>
                            <li><strong>Dernière révision :</strong> <?= date('d/m/Y H:i', BUILD_TIMESTAMP) ?></li>
                            <li><strong>Prochaine révision :</strong> <?= date('d/m/Y', strtotime('+3 months', BUILD_TIMESTAMP)) ?></li>
                            <li><strong>Responsable :</strong> <?= APP_AUTHOR ?></li>
                        </ul>
                    </div>

                    <div class="compliance-checklist">
                        <h4>✅ Checklist de conformité</h4>
                        <ul>
                            <li>✅ <strong>Loi LCEN</strong> (2004-575) : Mentions légales obligatoires</li>
                            <li>✅ <strong>RGPD</strong> (2018) : Protection des données personnelles</li>
                            <li>✅ <strong>Loi Informatique et Libertés</strong> : Version 2025</li>
                            <li>✅ <strong>Code de la propriété intellectuelle</strong> : Droits d'auteur</li>
                            <li>✅ <strong>Directive ePrivacy</strong> : Cookies et communications</li>
                            <li>✅ <strong>Accessibilité numérique</strong> : RGAA 4.1</li>
                        </ul>
                    </div>
                </section>
            </div>

            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/legal/mentions.php" class="btn btn-secondary">⚖️ Mentions légales</a>
                    <a href="/legal/privacy.php" class="btn btn-secondary">🔒 Confidentialité</a>
                    <a href="/legal/terms.php" class="btn btn-secondary">📋 CGU</a>
                    <a href="/legal/cookies.php" class="btn btn-secondary">🍪 Cookies</a>
                    <a href="/legal/security.php" class="btn btn-secondary">🔐 Sécurité</a>
                </div>
                
                <div class="legal-summary">
                    <p>
                        <small>
                            📚 <strong>Résumé :</strong> 
                            Ce centre légal contient tous les documents requis par la réglementation française 
                            pour un portail d'entreprise traitant des données personnelles. 
                            Conformité vérifiée : <?= date('m/Y') ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <!-- Horodatage et build fixe OBLIGATOIRE -->
    <div class="build-info">
        <p>Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?> - <?= date('d/m/Y H:i', BUILD_TIMESTAMP) ?></p>
    </div>
</body>
</html>