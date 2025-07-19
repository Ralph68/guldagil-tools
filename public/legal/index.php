<?php
/**
 * Titre: Index des documents légaux et politiques
 * Chemin: /public/legal/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration et includes
require_once __DIR__ . '/../../config/version.php';

// Meta données de la page
$page_title = "Documents légaux et politiques";
$page_description = "Centre de documentation légale - RGPD, conditions d'utilisation et sécurité";
$page_type = "legal-index";

// Documents légaux disponibles
$legal_documents = [
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
    <link rel="stylesheet" href="/assets/css/portal.css">
    <link rel="stylesheet" href="/assets/css/legal.css">
    <link rel="canonical" href="/legal/">
</head>
<body class="legal-page">
    <header class="portal-header">
        <div class="header-container">
            <div class="header-brand">
                <h1 class="brand-title"><?= APP_NAME ?></h1>
                <span class="brand-version">v<?= APP_VERSION ?> build <?= BUILD_NUMBER ?></span>
            </div>
            <nav class="header-nav">
                <a href="/index.php" class="nav-link">🏠 Accueil</a>
            </nav>
        </div>
    </header>

    <main class="legal-main">
        <div class="legal-container">
            <div class="legal-header">
                <h1>⚖️ Centre de documentation légale</h1>
                <p class="legal-meta">
                    Mise à jour : <?= $legal_stats['last_update'] ?> - Conformité <?= $legal_stats['compliance_status'] ?><br>
                    Révision : <?= $legal_stats['review_period'] ?> - Version portail : <?= APP_VERSION ?>
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
                        <div class="document-card" onclick="window.location.href='/legal/<?= $document['file'] ?>'">
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
                                📧 <a href="mailto:legal@guldagil.com">legal@guldagil.com</a><br>
                                📧 <a href="mailto:dpo@guldagil.com">dpo@guldagil.com</a> (RGPD)
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
                </section>
            </div>

            <div class="legal-footer">
                <div class="legal-actions">
                    <a href="/index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                    <a href="/legal/privacy.php" class="btn btn-secondary">🔒 Confidentialité</a>
                    <a href="/legal/terms.php" class="btn btn-secondary">📋 CGU</a>
                    <a href="/legal/security.php" class="btn btn-secondary">🔐 Sécurité</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="portal-footer">
        <div class="footer-container">
            <div class="footer-info">
                <p>&copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?> - <?= APP_NAME ?></p>
                <p>Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?> (<?= date('d/m/Y H:i', BUILD_TIMESTAMP) ?>)</p>
            </div>
            <div class="footer-links">
                <a href="/legal/">Mentions légales</a>
                <a href="/legal/privacy.php">Confidentialité</a>
                <a href="/legal/terms.php">CGU</a>
                <a href="/legal/security.php">Sécurité</a>
            </div>
        </div>
    </footer>

    <style>
        /* Styles spécifiques pour l'index légal */
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--spacing-xl);
            margin: var(--spacing-xl) 0;
        }
        
        .document-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: var(--transition-normal);
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        
        .document-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .document-header {
            padding: var(--spacing-lg);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .document-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            background: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
        }
        
        .document-meta {
            flex: 1;
        }
        
        .document-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 var(--spacing-xs) 0;
        }
        
        .document-category {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
            display: block;
            margin-bottom: var(--spacing-xs);
        }
        
        .document-badge {
            font-size: var(--font-size-sm);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
        }
        
        .document-badge.mandatory {
            background: rgba(239, 68, 68, 0.1);
            color: var(--color-danger);
        }
        
        .document-badge.optional {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-600);
        }
        
        .document-body {
            padding: var(--spacing-lg);
            flex: 1;
        }
        
        .document-description {
            color: var(--gray-700);
            line-height: 1.6;
            margin: 0 0 var(--spacing-md) 0;
        }
        
        .document-info {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
        }
        
        .document-footer {
            padding: var(--spacing-lg);
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }
        
        .document-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            font-size: var(--font-size-sm);
        }
        
        .document-link:hover {
            text-decoration: underline;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
        }
        
        .info-card {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary-blue);
        }
        
        .info-card h3 {
            margin: 0 0 var(--spacing-md) 0;
            color: var(--gray-900);
            font-size: var(--font-size-lg);
        }
        
        .info-card p {
            margin: 0;
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        .version-info {
            background: var(--primary-blue-light);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            border: 1px solid var(--primary-blue);
            margin: var(--spacing-lg) 0;
        }
        
        .version-info h4 {
            margin: 0 0 var(--spacing-md) 0;
            color: var(--primary-blue-dark);
        }
        
        .version-info ul {
            margin: 0;
            padding-left: var(--spacing-lg);
        }
        
        .version-info li {
            margin: var(--spacing-sm) 0;
            color: var(--gray-700);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .documents-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
