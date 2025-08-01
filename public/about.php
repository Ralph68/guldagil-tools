<?php
/**
 * Titre: Page À propos du Portail Guldagil
 * Chemin: /public/about.php
 * Version: 0.5 beta + build auto
 */

// Configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/version.php';

// Variables pour le header
$page_title = 'À propos du Hub Logistique & Qualité';
$page_subtitle = 'Solutions professionnelles pour l\'industrie';
$page_description = 'Découvrez le portail Guldagil : calculateur de frais de port, gestion ADR, contrôle qualité, achats et administration centralisée.';
$current_module = 'about';

// Informations du portail
$portal_info = [
    'name' => 'Hub Logistique & Qualité Industrielle',
    'full_name' => 'Portail Guldagil - Hub Logistique & Qualité Industrielle',
    'version' => APP_VERSION,
    'build' => BUILD_NUMBER,
    'author' => APP_AUTHOR,
    'company' => 'Guldagil',
    'sector' => 'Traitement de l\'eau et solutions industrielles',
    'release_date' => 'Novembre 2024',
    'description' => 'Portail web professionnel centralisant les outils de gestion des achats, logistique et transport pour optimiser les opérations quotidiennes.',
];

// Modules du portail - LES VRAIS 6 MODULES
$modules_info = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'icon' => '🧮',
        'color' => '#3b82f6',
        'status' => 'Disponible',
        'completion' => '70%',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'features' => [
            'Comparaison multi-transporteurs',
            'Calculs automatisés',
            'Export et historique',
            'Interface responsive',
            'Support transport standard et ADR'
        ],
        'benefits' => [
            'Gain de temps considérable dans les devis',
            'Optimisation des coûts de transport',
            'Centralisation des tarifications'
        ]
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'icon' => '⚠️',
        'color' => '#f59e0b',
        'status' => 'Disponible',
        'completion' => '50%',
        'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
        'features' => [
            'Déclarations ADR',
            'Gestion des quotas',
            'Suivi réglementaire',
            'Base de données produits dangereux',
            'Documents obligatoires automatisés'
        ],
        'benefits' => [
            'Conformité réglementaire assurée',
            'Réduction des risques administratifs',
            'Traçabilité complète des expéditions'
        ]
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'icon' => '✅',
        'color' => '#10b981',
        'status' => 'Disponible',
        'completion' => '10%',
        'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
        'features' => [
            'Tests et validations',
            'Rapports de conformité',
            'Suivi des équipements',
            'Alertes qualité automatiques',
            'Dashboard qualité temps réel'
        ],
        'benefits' => [
            'Amélioration continue de la qualité',
            'Réduction des non-conformités',
            'Satisfaction client renforcée'
        ]
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'icon' => '🛡️',
        'color' => '#8b5cf6',
        'status' => 'En développement',
        'completion' => '01%',
        'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
        'features' => [
            'Inventaire EPI',
            'Suivi des dates d\'expiration',
            'Gestion des commandes',
            'Attribution au personnel',
            'Alertes de renouvellement'
        ],
        'benefits' => [
            'Sécurité du personnel optimisée',
            'Conformité réglementaire EPI',
            'Optimisation des stocks de protection'
        ]
    ],
    'outillages' => [
        'name' => 'Outillages',
        'icon' => '🔧',
        'color' => '#6b7280',
        'status' => 'En développement', 
        'completion' => '01%',
        'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
        'features' => [
            'Inventaire outillage',
            'Planning maintenance',
            'Suivi d\'utilisation',
            'Réservations d\'équipements',
            'Historique des maintenances'
        ],
        'benefits' => [
            'Optimisation de l\'utilisation des outils',
            'Maintenance préventive efficace',
            'Réduction des temps d\'arrêt'
        ]
    ],
    'admin' => [
        'name' => 'Administration',
        'icon' => '⚙️',
        'color' => '#ef4444',
        'status' => 'Administrateurs',
        'completion' => '85%',
        'description' => 'Configuration et gestion globale du portail - Réservé aux administrateurs',
        'features' => [
            'Configuration système',
            'Gestion utilisateurs',
            'Maintenance',
            'Import/Export de données',
            'Logs et monitoring'
        ],
        'benefits' => [
            'Administration centralisée',
            'Sécurité renforcée',
            'Maintenance facilitée'
        ]
    ]
];

// Statistiques du portail
$portal_stats = [
    'modules_total' => 6,
    'modules_actifs' => 3,
    'completion_moyenne' => '36%',
    'utilisateurs' => '15+',
    'transactions_mois' => '500+',
    'uptime' => '99.8%'
];

// Technologies utilisées
$technologies = [
    'backend' => ['PHP 8.1+', 'MySQL/MariaDB', 'Architecture MVC'],
    'frontend' => ['HTML5', 'CSS3 moderne', 'JavaScript ES6+'],
    'design' => ['Responsive Design', 'UI/UX optimisé', 'Thème bleu professionnel'],
    'securite' => ['Authentification sécurisée', 'Validation des données', 'Logs d\'audit']
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($portal_info['name']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    
    <style>
        /* Variables CSS pour cohérence avec le thème */
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --text-color: #374151;
            --border-color: #e5e7eb;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --spacing: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }

        /* Header */
        .about-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .about-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="20" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .about-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .about-header .subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .version-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Sections */
        .about-section {
            padding: 3rem 0;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            color: var(--primary-color);
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--text-color);
            text-align: center;
            margin-bottom: 3rem;
            opacity: 0.8;
        }

        /* Grilles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 4px solid var(--secondary-color);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        /* Modules grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .module-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .module-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--light-color) 0%, #ffffff 100%);
            border-bottom: 1px solid var(--border-color);
        }

        .module-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
        }

        .module-info h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .module-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-disponible {
            background: #dcfdf7;
            color: #065f46;
        }

        .status-en-developpement {
            background: #fef3c7;
            color: #92400e;
        }

        .status-administrateurs {
            background: #fee2e2;
            color: #991b1b;
        }

        .module-body {
            padding: 1.5rem;
        }

        .module-description {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .features-list li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-color);
            font-weight: bold;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), var(--secondary-color));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* Technologies */
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .tech-category {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .tech-category h4 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .tech-list {
            list-style: none;
        }

        .tech-list li {
            padding: 0.5rem 0;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
        }

        .tech-list li:last-child {
            border-bottom: none;
        }

        /* Info box */
        .info-box {
            background: white;
            border: 2px solid var(--secondary-color);
            border-radius: var(--radius);
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
        }

        .info-box h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Footer links */
        .footer-links {
            background: var(--dark-color);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .footer-links a {
            color: var(--accent-color);
            text-decoration: none;
            margin: 0 1rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .about-header h1 {
                font-size: 2rem;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="about-header">
        <div class="header-content">
            <h1><?= htmlspecialchars($portal_info['name']) ?></h1>
            <p class="subtitle"><?= htmlspecialchars($portal_info['full_name']) ?></p>
            <div class="version-badge">
                Version <?= htmlspecialchars($portal_info['version']) ?> | Build #<?= htmlspecialchars($portal_info['build']) ?>
            </div>
        </div>
    </header>

    <!-- Navigation retour -->
    <div class="container" style="padding-top: 2rem;">
        <a href="/" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
            ← Retour à l'accueil
        </a>
    </div>

    <!-- Section Présentation -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title">À propos du portail</h2>
            <p class="section-subtitle">
                <?= htmlspecialchars($portal_info['description']) ?>
            </p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">6</div>
                    <div class="stat-label">Modules disponibles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">3</div>
                    <div class="stat-label">Modules actifs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">36%</div>
                    <div class="stat-label">Avancement global</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $portal_stats['utilisateurs'] ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
            </div>

            <div class="info-box">
                <h3>🎯 Mission</h3>
                <p>
                    Simplifier et optimiser les processus d'achats et de logistique grâce à des outils 
                    professionnels intégrés, favorisant l'efficacité opérationnelle et la réduction des coûts.
                </p>
            </div>
        </div>
    </section>

    <!-- Section Modules -->
    <section class="about-section" style="background: rgba(255, 255, 255, 0.5);">
        <div class="container">
            <h2 class="section-title">Nos modules</h2>
            <p class="section-subtitle">
                Découvrez les 6 modules qui composent le portail Guldagil
            </p>

            <div class="modules-grid">
                <?php foreach ($modules_info as $module_id => $module): ?>
                <article class="module-card">
                    <div class="module-header">
                        <div class="module-meta">
                            <div class="module-icon" style="background: <?= $module['color'] ?>;">
                                <?= $module['icon'] ?>
                            </div>
                            <div class="module-info">
                                <h3><?= htmlspecialchars($module['name']) ?></h3>
                                <span class="module-status status-<?= strtolower(str_replace([' ', 'é'], ['-', 'e'], $module['status'])) ?>">
                                    <?= htmlspecialchars($module['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="module-body">
                        <p class="module-description">
                            <?= htmlspecialchars($module['description']) ?>
                        </p>
                        
                        <h4 style="margin-bottom: 0.75rem; color: var(--primary-color);">Fonctionnalités :</h4>
                        <ul class="features-list">
                            <?php foreach ($module['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h4 style="margin-bottom: 0.75rem; color: var(--primary-color);">Avantages :</h4>
                        <ul class="features-list" style="margin-bottom: 1.5rem;">
                            <?php foreach ($module['benefits'] as $benefit): ?>
                                <li><?= htmlspecialchars($benefit) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; color: var(--text-color);">Progression</span>
                            <span style="font-size: 0.875rem; font-weight: 600; color: var(--primary-color);">
                                <?= htmlspecialchars($module['completion']) ?>
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $module['completion'] ?>"></div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section Technologies -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title">Technologies utilisées</h2>
            <p class="section-subtitle">
                Une stack technique moderne et robuste pour des performances optimales
            </p>

            <div class="tech-grid">
                <?php foreach ($technologies as $category => $techs): ?>
                <div class="tech-category">
                    <h4><?= ucfirst($category) ?></h4>
                    <ul class="tech-list">
                        <?php foreach ($techs as $tech): ?>
                            <li><?= htmlspecialchars($tech) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section Informations -->
    <section class="about-section" style="background: rgba(255, 255, 255, 0.5);">
        <div class="container">
            <h2 class="section-title">Informations projet</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div style="background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow);">
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">📋 Détails du projet</h3>
                    <p><strong>Entreprise :</strong> <?= htmlspecialchars($portal_info['company']) ?></p>
                    <p><strong>Secteur :</strong> <?= htmlspecialchars($portal_info['sector']) ?></p>
                    <p><strong>Mise en production :</strong> <?= htmlspecialchars($portal_info['release_date']) ?></p>
                    <p><strong>Développeur :</strong> <?= htmlspecialchars($portal_info['author']) ?></p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow);">
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">🚀 Performances</h3>
                    <p><strong>Disponibilité :</strong> <?= htmlspecialchars($portal_stats['uptime']) ?></p>
                    <p><strong>Transactions/mois :</strong> <?= htmlspecialchars($portal_stats['transactions_mois']) ?></p>
                    <p><strong>Architecture :</strong> Modulaire et évolutive</p>
                    <p><strong>Sécurité :</strong> Authentification et audit intégrés</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer avec liens -->
    <footer class="footer-links">
        <div class="container">
            <p style="margin-bottom: 1rem;">
                <strong><?= htmlspecialchars($portal_info['name']) ?></strong> - 
                Version <?= htmlspecialchars($portal_info['version']) ?> | 
                Build #<?= htmlspecialchars($portal_info['build']) ?>
            </p>
            <div>
                <a href="/">🏠 Accueil</a>
                <a href="/calculateur/">🧮 Calculateur</a>
                <a href="/admin/">⚙️ Administration</a>
                <a href="/docs/" title="Documentation détaillée">📚 Documentation</a>
                <a href="/help/" title="Aide et support">❓ Aide</a>
            </div>
            <p style="margin-top: 1rem; opacity: 0.7; font-size: 0.875rem;">
                © <?= COPYRIGHT_YEAR ?> <?= htmlspecialchars($portal_info['company']) ?> - 
                Développé par <?= htmlspecialchars($portal_info['author']) ?>
            </p>
        </div>
    </footer>

    <script>
        // Animation d'entrée pour les cartes
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.module-card, .stat-card, .tech-category');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach((card) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>
