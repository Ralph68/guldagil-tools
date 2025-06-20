<?php
/**
 * Titre: Module Calculateur - Interface pas-à-pas
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta + build
 * 
 * Interface calculateur style pas-à-pas avec calcul dynamique
 * Inspirée de l'ancienne version plus user-friendly
 */

// Configuration et dépendances
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Vérification module activé
if (!hasModuleAccess('calculateur')) {
    header('Location: ../');
    exit('Module calculateur non disponible');
}

// Démarrage session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupération des options réelles de la BDD
$options_disponibles = [];
try {
    $stmt = $db->query("
        SELECT transporteur, code_option, libelle, montant, unite 
        FROM gul_options_supplementaires 
        WHERE actif = 1 
        ORDER BY transporteur, montant
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options_disponibles[] = $row;
    }
} catch (Exception $e) {
    $options_disponibles = [];
}

// Variables d'affichage
$page_title = 'Calculateur de frais';
$version_info = getVersionInfo();

// Mode démo si demandé
$demo_mode = isset($_GET['demo']) && $_GET['demo'] === '1';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Calculateur de frais de port - Interface complète Guldagil">
    <meta name="keywords" content="calculateur, frais de port, transport, Guldagil">
    
    <!-- CSS modulaire -->
    <link rel="stylesheet" href="../assets/css/app.min.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/base.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/layout.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/components.css">
</head>
<body class="calculator-page">

    <!-- Header fixe -->
    <header class="calculator-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-icon">🧮</div>
                <div class="brand-info">
                    <h1><?= htmlspecialchars($page_title) ?></h1>
                    <p>Interface complète</p>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="#" class="header-btn" id="btn-nouveau-calcul">
                    📋 Nouveau calcul
                </a>
                <a href="#" class="header-btn">
                    📊 Historique
                </a>
                <a href="#" class="header-btn">
                    👤
                </a>
            </div>
        </div>
    </header>

    <!-- Container principal -->
    <div class="calculator-container">
        
        <!-- Section formulaire pas-à-pas -->
        <section class="form-section">
            <div class="form-header">
                <h2>📦 Paramètres d'expédition</h2>
                <p>Renseignez vos critères pour comparer les transporteurs</p>
            </div>
            
            <div class="form-content">
                <form id="calculator-form" novalidate>
                    
                    <!-- Étape 1: Destination et poids (toujours visible) -->
                    <div class="form-step" id="step-destination">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <h3 class="step-title">📍 Destination et poids</h3>
                        </div>
                        
                        <div class="field-group">
                            <div class="form-field">
                                <label class="form-label" for="departement">Département de livraison</label>
                                <input type="text" 
                                       id="departement" 
                                       name="departement" 
                                       class="form-input" 
                                       placeholder="Ex: 67" 
                                       maxlength="2"
                                       autocomplete="off">
                                <div class="field-hint">2 chiffres (01 à 95)</div>
                                <div class="field-feedback" id="dept-feedback"></div>
                            </div>
                            
                            <div class="form-field">
                                <label class="form-label" for="poids">Poids total (kg)</label>
                                <input type="number" 
                                       id="poids" 
                                       name="poids" 
                                       class="form-input" 
                                       placeholder="Ex: 25"
                                       min="0.1"
                                       max="3500"
                                       step="1">
                                <div class="field-hint">Maximum 3500 kg (sans virgule)</div>
                                <div class="field-feedback" id="poids-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 2: Type d'expédition (masquée par défaut) -->
                    <div class="form-step hidden" id="step-type">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <h3 class="step-title">📦 Type d'expédition</h3>
                        </div>
                        
                        <div class="type-buttons">
                            <label class="type-btn">
                                <input type="radio" name="type" value="colis">
                                <span class="btn-content">
                                    <span class="btn-icon">📦</span>
                                    <span class="btn-text">Colis</span>
                                </span>
                            </label>
                            
                            <label class="type-btn">
                                <input type="radio" name="type" value="palette">
                                <span class="btn-content">
                                    <span class="btn-icon">🛏️</span>
                                    <span class="btn-text">Palette</span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="poids-alert hidden" id="poids-alert">
                            ⚠️ Pour ce poids, seule la palette est disponible
                        </div>
                    </div>

                    <!-- Étape 3: Marchandises dangereuses (masquée par défaut) -->
                    <div class="form-step hidden" id="step-adr">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <h3 class="step-title">⚠️ Marchandises dangereuses (ADR)</h3>
                        </div>
                        
                        <div class="adr-buttons">
                            <label class="adr-btn">
                                <input type="radio" name="adr" value="non">
                                <span class="btn-content">
                                    <span class="btn-icon">✅</span>
                                    <span class="btn-text">Non ADR</span>
                                </span>
                            </label>
                            
                            <label class="adr-btn">
                                <input type="radio" name="adr" value="oui">
                                <span class="btn-content">
                                    <span class="btn-icon">⚠️</span>
                                    <span class="btn-text">ADR</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Options de livraison (masquées par défaut, apparaissent après calcul) -->
                    <div class="form-step hidden" id="step-options">
                        <div class="step-header">
                            <div class="step-number">4</div>
                            <h3 class="step-title">🚀 Options de livraison</h3>
                        </div>
                        
                        <div class="options-buttons">
                            <label class="option-btn active">
                                <input type="radio" name="service_livraison" value="standard" checked>
                                <span class="btn-content">
                                    <span class="btn-text">Standard</span>
                                    <span class="btn-price">Inclus</span>
                                </span>
                            </label>
                            
                            <label class="option-btn">
                                <input type="radio" name="service_livraison" value="rdv">
                                <span class="btn-content">
                                    <span class="btn-text">Prise de RDV</span>
                                    <span class="btn-price">+15€</span>
                                </span>
                            </label>
                            
                            <label class="option-btn">
                                <input type="radio" name="service_livraison" value="premium">
                                <span class="btn-content">
                                    <span class="btn-text">Premium 13h</span>
                                    <span class="btn-price">+22€</span>
                                </span>
                            </label>
                            
                            <label class="option-btn">
                                <input type="radio" name="service_livraison" value="datefixe">
                                <span class="btn-content">
                                    <span class="btn-text">Date fixe</span>
                                    <span class="btn-price">+18€</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Options palette (masquées par défaut) -->
                    <div class="form-step hidden" id="step-palettes">
                        <div class="step-header">
                            <div class="step-number">5</div>
                            <h3 class="step-title">🛏️ Nombre de palettes EUR</h3>
                        </div>
                        
                        <div class="palette-input">
                            <input type="number" 
                                   id="palettes" 
                                   name="palettes" 
                                   class="form-input" 
                                   min="0" 
                                   max="10" 
                                   value="1"
                                   placeholder="Nombre">
                            <span class="input-suffix">palettes EUR</span>
                        </div>
                    </div>

                    <!-- Enlèvement à part -->
                    <div class="form-step hidden" id="step-enlevement">
                        <div class="step-divider"></div>
                        <label class="enlevement-option">
                            <input type="checkbox" id="enlevement" name="enlevement">
                            <span class="checkbox-content">
                                <span class="checkbox-icon">🚚</span>
                                <span class="checkbox-text">
                                    <span class="checkbox-title">Enlèvement sur site</span>
                                    <span class="checkbox-desc">Collecte à votre adresse</span>
                                </span>
                            </span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="button" id="btn-reset" class="btn btn-secondary">
                            🔄 Réinitialiser
                        </button>
                    </div>
                    
                </form>
            </div>
        </section>

        <!-- Section résultats (toujours visible) -->
        <section class="results-section">
            <div class="results-header">
                <h2>🎯 Votre tarif</h2>
                <div class="results-status" id="results-status">En attente</div>
            </div>
            
            <div class="results-content" id="results-content">
                <div class="results-placeholder">
                    <div class="placeholder-icon">🚀</div>
                    <h4>Prêt à calculer</h4>
                    <p>Renseignez le formulaire pour voir les tarifs de nos transporteurs partenaires</p>
                </div>
            </div>
        </section>

    </div>

    <!-- Conseils d'utilisation -->
    <div class="tips-section">
        <div class="tips-content">
            <h3>💡 Conseils d'utilisation</h3>
            <div class="tips-list">
                <div class="tip-item">
                    <span class="tip-icon">💰</span>
                    <span><strong>Poids > 60kg :</strong> Privilégiez la palette</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🚨</span>
                    <span><strong>Alertes seuils :</strong> Regardez les suggestions "payant pour"</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">⚠️</span>
                    <span><strong>ADR :</strong> Utilisez le module dédié pour les déclarations</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">⚙️</span>
                    <span><strong>Options :</strong> L'enlèvement désactive les options de livraison</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Support -->
    <div class="support-section">
        <div class="support-content">
            <h4>Support :</h4>
            <div class="support-info">
                <div class="support-item">
                    <span>📧</span>
                    <span>achats@guldagil.com</span>
                </div>
                <div class="support-item">
                    <span>📞</span>
                    <span>03 89 65 42 41</span>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript - Ordre modulaire respecté -->
    <script src="../assets/js/app.min.js"></script>
    <script src="../assets/js/modules/calculateur/utils.js"></script>
    <script src="../assets/js/modules/calculateur/ui.js"></script>
    <script src="../assets/js/modules/calculateur/form-handler.js"></script>
    <script src="../assets/js/modules/calculateur/calculs.js"></script>
    <script src="../assets/js/modules/calculateur/resultats-display.js"></script>
    <script src="../assets/js/modules/calculateur/calculateur.js"></script>

    <?php if ($demo_mode): ?>
    <!-- Mode démo -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (window.Calculateur && Calculateur.Form) {
                Calculateur.Form.populateForm({
                    departement: '67',
                    poids: '25.5',
                    type: 'colis',
                    adr: 'non'
                });
            }
        }, 500);
    });
    </script>
    <?php endif; ?>

    <?php if (DEBUG): ?>
    <!-- Debug -->
    <script>
    window.DEBUG_INFO = {
        version: '<?= APP_VERSION ?>',
        build: '<?= BUILD_NUMBER ?>',
        options_count: <?= count($options_disponibles) ?>,
        demo_mode: <?= $demo_mode ? 'true' : 'false' ?>
    };
    console.log('🧮 Calculateur v<?= APP_VERSION ?> - Debug activé');
    </script>
    <?php endif; ?>

</body>
</html>
