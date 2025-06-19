<?php
/**
 * Module Calculateur de frais de port
 * public/modules/calculator.php
 */

// Sécurité : vérifier que ce fichier est bien inclus
if (!defined('APP_VERSION')) {
    die('Accès direct interdit');
}

// Inclusion de la classe Transport si disponible
if (file_exists(ROOT_PATH . '/lib/Transport.php')) {
    require_once ROOT_PATH . '/lib/Transport.php';
}

// Récupération des données pour le formulaire
try {
    // Récupérer les départements disponibles
    $stmt = $db->prepare("SELECT DISTINCT departement FROM gul_taxes_transporteurs ORDER BY departement");
    $stmt->execute();
    $departements = $stmt->fetchAll();
    
    // Récupérer les options supplémentaires
    $stmt = $db->prepare("SELECT * FROM gul_options_supplementaires WHERE actif = 1 ORDER BY nom");
    $stmt->execute();
    $options = $stmt->fetchAll();
    
    // Récupérer les transporteurs disponibles
    $stmt = $db->prepare("SELECT DISTINCT transporteur FROM gul_taxes_transporteurs ORDER BY transporteur");
    $stmt->execute();
    $transporteurs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError('Erreur récupération données calculateur', ['error' => $e->getMessage()]);
    $departements = [];
    $options = [];
    $transporteurs = [];
}

// Traitement du calcul si formulaire soumis
$resultatCalcul = null;
$erreurCalcul = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])) {
    try {
        // Validation des données
        $departement = clean($_POST['departement'] ?? '');
        $poids = (float)($_POST['poids'] ?? 0);
        $type_envoi = clean($_POST['type_envoi'] ?? 'standard');
        $nb_palettes = (int)($_POST['nb_palettes'] ?? 1);
        $optionsSelectionnees = $_POST['options'] ?? [];
        
        // Validation basique
        if (empty($departement) || $poids <= 0) {
            throw new Exception('Veuillez remplir tous les champs obligatoires');
        }
        
        if ($poids > 10000) {
            throw new Exception('Poids maximum autorisé : 10 000 kg');
        }
        
        // Calcul des frais avec la classe Transport si disponible
        if (class_exists('Transport')) {
            $transport = new Transport($db);
            $resultatCalcul = $transport->calculerFrais($departement, $poids, $type_envoi, $nb_palettes, $optionsSelectionnees);
        } else {
            // Calcul simple sans classe
            $resultatCalcul = calculerFraisSimple($db, $departement, $poids, $type_envoi, $nb_palettes, $optionsSelectionnees);
        }
        
    } catch (Exception $e) {
        $erreurCalcul = $e->getMessage();
        logError('Erreur calcul frais', [
            'error' => $e->getMessage(),
            'data' => $_POST
        ]);
    }
}

/**
 * Calcul simple sans classe (fallback)
 */
function calculerFraisSimple($db, $departement, $poids, $type_envoi, $nb_palettes, $options) {
    // Récupérer les tarifs de base
    $stmt = $db->prepare("
        SELECT transporteur, prix_kg, prix_fixe, seuil_poids
        FROM gul_taxes_transporteurs 
        WHERE departement = ? 
        ORDER BY transporteur
    ");
    $stmt->execute([$departement]);
    $tarifs = $stmt->fetchAll();
    
    $resultats = [];
    
    foreach ($tarifs as $tarif) {
        $prixBase = $tarif['prix_fixe'] + ($poids * $tarif['prix_kg']);
        
        // Ajout frais palettes
        if ($nb_palettes > 1) {
            $prixBase += ($nb_palettes - 1) * 15; // 15€ par palette supplémentaire
        }
        
        // Majoration selon type d'envoi
        switch ($type_envoi) {
            case 'express':
                $prixBase *= 1.3;
                break;
            case 'urgent':
                $prixBase *= 1.5;
                break;
        }
        
        $resultats[] = [
            'transporteur' => $tarif['transporteur'],
            'prix_ht' => round($prixBase, 2),
            'prix_ttc' => round($prixBase * 1.2, 2),
            'delai' => '2-3 jours'
        ];
    }
    
    // Trier par prix
    usort($resultats, function($a, $b) {
        return $a['prix_ht'] <=> $b['prix_ht'];
    });
    
    return $resultats;
}
?>

<!-- Interface du calculateur -->
<div class="calculator-module">
    
    <!-- En-tête du module -->
    <div class="module-header">
        <div class="module-title">
            <h2>🧮 Calculateur de frais de port</h2>
            <p class="module-description">
                Comparez instantanément les tarifs de transport de nos partenaires
            </p>
        </div>
        
        <?php if (DEBUG): ?>
        <div class="debug-info">
            <span class="debug-badge">🐛 DEBUG</span>
            <span>Départements: <?= count($departements) ?></span>
            <span>Options: <?= count($options) ?></span>
            <span>Transporteurs: <?= count($transporteurs) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Formulaire de calcul -->
    <div class="calculator-form">
        <form method="POST" id="calculator-form" class="form-grid">
            
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <!-- Département de destination -->
            <div class="form-group">
                <label for="departement" class="form-label required">
                    📍 Département de destination
                </label>
                <select name="departement" id="departement" class="form-control" required>
                    <option value="">Sélectionnez un département</option>
                    <?php foreach ($departements as $dept): ?>
                    <option value="<?= clean($dept['departement']) ?>" 
                            <?= (isset($_POST['departement']) && $_POST['departement'] === $dept['departement']) ? 'selected' : '' ?>>
                        <?= clean($dept['departement']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Poids -->
            <div class="form-group">
                <label for="poids" class="form-label required">
                    ⚖️ Poids total (kg)
                </label>
                <input type="number" 
                       name="poids" 
                       id="poids" 
                       class="form-control" 
                       min="0.1" 
                       max="10000" 
                       step="0.1" 
                       value="<?= clean($_POST['poids'] ?? '') ?>"
                       placeholder="Ex: 25.5"
                       required>
                <small class="form-help">Maximum 10 000 kg</small>
            </div>
            
            <!-- Type d'envoi -->
            <div class="form-group">
                <label for="type_envoi" class="form-label">
                    🚚 Type d'envoi
                </label>
                <select name="type_envoi" id="type_envoi" class="form-control">
                    <option value="standard" <?= (($_POST['type_envoi'] ?? 'standard') === 'standard') ? 'selected' : '' ?>>
                        Standard (2-3 jours)
                    </option>
                    <option value="express" <?= (($_POST['type_envoi'] ?? '') === 'express') ? 'selected' : '' ?>>
                        Express (+30% - 1-2 jours)
                    </option>
                    <option value="urgent" <?= (($_POST['type_envoi'] ?? '') === 'urgent') ? 'selected' : '' ?>>
                        Urgent (+50% - 24h)
                    </option>
                </select>
            </div>
            
            <!-- Nombre de palettes -->
            <div class="form-group">
                <label for="nb_palettes" class="form-label">
                    📦 Nombre de palettes
                </label>
                <input type="number" 
                       name="nb_palettes" 
                       id="nb_palettes" 
                       class="form-control" 
                       min="1" 
                       max="50" 
                       value="<?= clean($_POST['nb_palettes'] ?? '1') ?>">
                <small class="form-help">+15€ par palette supplémentaire</small>
            </div>
            
            <!-- Options supplémentaires -->
            <?php if (!empty($options)): ?>
            <div class="form-group form-group-full">
                <label class="form-label">🎛️ Options supplémentaires</label>
                <div class="options-grid">
                    <?php foreach ($options as $option): ?>
                    <label class="option-checkbox">
                        <input type="checkbox" 
                               name="options[]" 
                               value="<?= $option['id'] ?>"
                               <?= (isset($_POST['options']) && in_array($option['id'], $_POST['options'])) ? 'checked' : '' ?>>
                        <span class="option-label">
                            <?= clean($option['nom']) ?>
                            <?php if ($option['prix'] > 0): ?>
                            <span class="option-price">+<?= formatPrice($option['prix']) ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($option['description'])): ?>
                        <small class="option-description"><?= clean($option['description']) ?></small>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bouton de calcul -->
            <div class="form-group form-group-full">
                <button type="submit" name="calculate" class="btn btn-primary btn-large">
                    <span class="btn-icon">🧮</span>
                    Calculer les frais de port
                    <span class="btn-loading" style="display: none;">⏳</span>
                </button>
            </div>
            
        </form>
    </div>
    
    <!-- Résultats du calcul -->
    <?php if ($erreurCalcul): ?>
    <div class="alert alert-error">
        <div class="alert-icon">❌</div>
        <div class="alert-content">
            <strong>Erreur de calcul</strong>
            <p><?= clean($erreurCalcul) ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($resultatCalcul && !$erreurCalcul): ?>
    <div class="calculator-results">
        
        <div class="results-header">
            <h3>📊 Résultats du calcul</h3>
            <div class="results-summary">
                <span class="summary-item">
                    <strong><?= count($resultatCalcul) ?></strong> offres trouvées
                </span>
                <span class="summary-item">
                    Département : <strong><?= clean($_POST['departement']) ?></strong>
                </span>
                <span class="summary-item">
                    Poids : <strong><?= clean($_POST['poids']) ?> kg</strong>
                </span>
            </div>
        </div>
        
        <div class="results-grid">
            <?php foreach ($resultatCalcul as $index => $resultat): ?>
            <div class="result-card <?= $index === 0 ? 'result-best' : '' ?>">
                
                <?php if ($index === 0): ?>
                <div class="best-offer-badge">🏆 Meilleure offre</div>
                <?php endif; ?>
                
                <div class="result-header">
                    <h4 class="transporteur-name"><?= clean($resultat['transporteur']) ?></h4>
                    <?php if (isset($resultat['logo'])): ?>
                    <img src="<?= clean($resultat['logo']) ?>" alt="<?= clean($resultat['transporteur']) ?>" class="transporteur-logo">
                    <?php endif; ?>
                </div>
                
                <div class="result-pricing">
                    <div class="price-ht">
                        <span class="price-label">Prix HT</span>
                        <span class="price-value"><?= formatPrice($resultat['prix_ht']) ?></span>
                    </div>
                    <div class="price-ttc">
                        <span class="price-label">Prix TTC</span>
                        <span class="price-value main-price"><?= formatPrice($resultat['prix_ttc']) ?></span>
                    </div>
                </div>
                
                <div class="result-details">
                    <?php if (isset($resultat['delai'])): ?>
                    <div class="detail-item">
                        <span class="detail-icon">🕒</span>
                        <span class="detail-text">Délai : <?= clean($resultat['delai']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($resultat['service'])): ?>
                    <div class="detail-item">
                        <span class="detail-icon">🚚</span>
                        <span class="detail-text"><?= clean($resultat['service']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($resultat['suivi']) && $resultat['suivi']): ?>
                    <div class="detail-item">
                        <span class="detail-icon">📱</span>
                        <span class="detail-text">Suivi en ligne</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="result-actions">
                    <button class="btn btn-outline" onclick="showResultDetails(<?= $index ?>)">
                        📋 Détails
                    </button>
                    <?php if ($index === 0): ?>
                    <button class="btn btn-primary" onclick="selectOffer(<?= $index ?>)">
                        ✅ Choisir cette offre
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Détails cachés -->
                <div id="details-<?= $index ?>" class="result-details-full" style="display: none;">
                    <?php if (isset($resultat['details'])): ?>
                    <div class="details-content">
                        <h5>📋 Détail du calcul</h5>
                        <?php foreach ($resultat['details'] as $detail): ?>
                        <div class="calculation-line">
                            <span class="calc-label"><?= clean($detail['label']) ?></span>
                            <span class="calc-value"><?= clean($detail['value']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($resultat['conditions'])): ?>
                    <div class="conditions-content">
                        <h5>📜 Conditions particulières</h5>
                        <ul>
                            <?php foreach ($resultat['conditions'] as $condition): ?>
                            <li><?= clean($condition) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Actions globales -->
        <div class="results-actions">
            <button class="btn btn-secondary" onclick="exportResults()">
                📊 Exporter les résultats
            </button>
            <button class="btn btn-secondary" onclick="emailResults()">
                📧 Envoyer par email
            </button>
            <button class="btn btn-outline" onclick="printResults()">
                🖨️ Imprimer
            </button>
        </div>
        
        <!-- Comparaison détaillée -->
        <div class="comparison-table">
            <h4>📊 Tableau comparatif</h4>
            <div class="table-responsive">
                <table class="comparison-grid">
                    <thead>
                        <tr>
                            <th>Transporteur</th>
                            <th>Prix HT</th>
                            <th>Prix TTC</th>
                            <th>Délai</th>
                            <th>Options</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultatCalcul as $index => $resultat): ?>
                        <tr class="<?= $index === 0 ? 'best-row' : '' ?>">
                            <td class="transporteur-cell">
                                <?php if ($index === 0): ?>
                                <span class="best-badge">🏆</span>
                                <?php endif; ?>
                                <strong><?= clean($resultat['transporteur']) ?></strong>
                            </td>
                            <td class="price-cell"><?= formatPrice($resultat['prix_ht']) ?></td>
                            <td class="price-cell main"><?= formatPrice($resultat['prix_ttc']) ?></td>
                            <td class="delay-cell"><?= clean($resultat['delai'] ?? 'N/A') ?></td>
                            <td class="options-cell">
                                <?php if (isset($resultat['options_incluses'])): ?>
                                <span class="options-count"><?= count($resultat['options_incluses']) ?> options</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <button class="btn btn-small" onclick="selectOffer(<?= $index ?>)">
                                    Choisir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    <?php endif; ?>
    
    <!-- Informations et aide -->
    <div class="calculator-info">
        
        <div class="info-cards">
            
            <div class="info-card">
                <div class="info-icon">🚚</div>
                <div class="info-content">
                    <h4>Transporteurs partenaires</h4>
                    <p>Nous comparons les tarifs de <?= count($transporteurs) ?> transporteurs de confiance pour vous offrir les meilleurs prix.</p>
                    <div class="transporteurs-list">
                        <?php foreach ($transporteurs as $transporteur): ?>
                        <span class="transporteur-tag"><?= clean($transporteur['transporteur']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">⚡</div>
                <div class="info-content">
                    <h4>Calcul instantané</h4>
                    <p>Les tarifs sont calculés en temps réel selon nos grilles tarifaires actualisées quotidiennement.</p>
                    <small class="update-info">
                        Dernière mise à jour : <?= formatDate(time()) ?>
                    </small>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">🎯</div>
                <div class="info-content">
                    <h4>Tarifs garantis</h4>
                    <p>Les prix affichés sont garantis sous réserve des conditions générales de chaque transporteur.</p>
                    <div class="guarantee-badges">
                        <span class="badge">✅ Prix garantis</span>
                        <span class="badge">🔒 Secure</span>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- FAQ rapide -->
        <div class="faq-section">
            <h4>❓ Questions fréquentes</h4>
            <div class="faq-grid">
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(1)">
                        <span>Comment sont calculés les tarifs ?</span>
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div id="faq-1" class="faq-answer" style="display: none;">
                        Les tarifs sont calculés selon le poids, la destination, le type d'envoi et les options choisies. 
                        Chaque transporteur applique sa propre grille tarifaire.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(2)">
                        <span>Les prix incluent-ils la TVA ?</span>
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div id="faq-2" class="faq-answer" style="display: none;">
                        Les prix HT et TTC sont affichés. Le taux de TVA appliqué est de 20%.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(3)">
                        <span>Puis-je modifier ma commande ?</span>
                        <span class="faq-toggle">▼</span>
                    </div>
                    <div id="faq-3" class="faq-answer" style="display: none;">
                        Les modifications dépendent des conditions de chaque transporteur. 
                        Contactez-nous pour plus d'informations.
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
    
</div>

<!-- Styles CSS intégrés pour le module -->
<style>
/* Variables CSS */
:root {
    --primary-color: #3b82f6;
    --primary-dark: #1e40af;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --error-color: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-500: #6b7280;
    --gray-700: #374151;
    --gray-900: #111827;
    --border-radius: 8px;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Module principal */
.calculator-module {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* En-tête du module */
.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--gray-200);
}

.module-title h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 0.5rem 0;
}

.module-description {
    font-size: 1.125rem;
    color: var(--gray-500);
    margin: 0;
}

.debug-info {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    font-size: 0.875rem;
}

.debug-badge {
    background: var(--warning-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
}

/* Formulaire */
.calculator-form {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group-full {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-label.required::after {
    content: " *";
    color: var(--error-color);
}

.form-control {
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

/* Options supplémentaires */
.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 0.5rem;
}

.option-checkbox {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.option-checkbox:hover {
    border-color: var(--primary-color);
    background: var(--gray-50);
}

.option-checkbox input[type="checkbox"] {
    margin-bottom: 0.5rem;
}

.option-label {
    font-weight: 500;
    color: var(--gray-900);
}

.option-price {
    color: var(--primary-color);
    font-weight: 600;
}

.option-description {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

/* Boutons */
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-300);
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1rem;
}

/* Alertes */
.alert {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.alert-content strong {
    display: block;
    margin-bottom: 0.25rem;
}

/* Résultats */
.calculator-results {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    padding: 2rem;
    margin-bottom: 2rem;
}

.results-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.results-header h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 1rem 0;
}

.results-summary {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.summary-item {
    padding: 0.5rem 1rem;
    background: var(--gray-50);
    border-radius: 20px;
}

/* Grille des résultats */
.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.result-card {
    position: relative;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.result-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.result-best {
    border-color: var(--success-color);
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
}

.best-offer-badge {
    position: absolute;
    top: -0.5rem;
    right: 1rem;
    background: var(--success-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.transporteur-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.transporteur-logo {
    height: 32px;
    width: auto;
}

/* Pricing */
.result-pricing {
    margin-bottom: 1rem;
}

.price-ht, .price-ttc {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.price-label {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.price-value {
    font-weight: 600;
    color: var(--gray-900);
}

.main-price {
    font-size: 1.25rem;
    color: var(--primary-color);
}

/* Détails */
.result-details {
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.detail-icon {
    font-size: 1rem;
}

/* Actions */
.result-actions {
    display: flex;
    gap: 0.5rem;
}

.results-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

/* Tableau de comparaison */
.comparison-table {
    margin-top: 2rem;
}

.comparison-table h4 {
    margin-bottom: 1rem;
    color: var(--gray-900);
}

.table-responsive {
    overflow-x: auto;
}

.comparison-grid {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.comparison-grid th,
.comparison-grid td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
}

.comparison-grid th {
    background: var(--gray-50);
    font-weight: 600;
    color: var(--gray-700);
}

.best-row {
    background: rgba(16, 185, 129, 0.05);
}

.best-badge {
    font-size: 0.75rem;
    margin-right: 0.5rem;
}

/* Informations */
.calculator-info {
    margin-top: 3rem;
}

.info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-card {
    display: flex;
    gap: 1rem;
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.info-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.info-content h4 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-900);
    font-size: 1rem;
}

.info-content p {
    margin: 0 0 1rem 0;
    color: var(--gray-600);
    font-size: 0.875rem;
    line-height: 1.5;
}

.transporteurs-list {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.transporteur-tag {
    background: var(--gray-100);
    color: var(--gray-700);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

.update-info {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.guarantee-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.badge {
    background: var(--success-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* FAQ */
.faq-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.faq-section h4 {
    margin: 0 0 1rem 0;
    color: var(--gray-900);
}

.faq-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.faq-item {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--gray-50);
    cursor: pointer;
    font-weight: 500;
    color: var(--gray-900);
    transition: background 0.2s ease;
}

.faq-question:hover {
    background: var(--gray-100);
}

.faq-toggle {
    transition: transform 0.2s ease;
}

.faq-answer {
    padding: 1rem;
    background: white;
    color: var(--gray-600);
    font-size: 0.875rem;
    line-height: 1.5;
}

/* Mode debug */
.debug-mode .calculator-form {
    border: 2px dashed var(--warning-color);
}

.debug-mode .result-card {
    border-left: 4px solid var(--warning-color);
}

/* Responsive */
@media (max-width: 768px) {
    .calculator-module {
        padding: 1rem;
    }
    
    .module-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .results-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .results-summary {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .info-cards {
        grid-template-columns: 1fr;
    }
    
    .info-card {
        flex-direction: column;
        text-align: center;
    }
}

/* Animations */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.result-card {
    animation: slideInUp 0.3s ease forwards;
}

.result-card:nth-child(1) { animation-delay: 0.1s; }
.result-card:nth-child(2) { animation-delay: 0.2s; }
.result-card:nth-child(3) { animation-delay: 0.3s; }
</style>

<!-- JavaScript pour l'interactivité -->
<script>
// Variables globales
let calculatorResults = <?= $resultatCalcul ? json_encode($resultatCalcul) : 'null' ?>;
let isCalculating = false;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧮 Calculateur initialisé');
    
    // Auto-focus sur le premier champ
    const firstInput = document.querySelector('#departement');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Validation temps réel
    setupRealtimeValidation();
    
    // Sauvegarde automatique des données
    setupAutoSave();
    
    console.log('✅ Calculateur prêt');
});

// Validation en temps réel
function setupRealtimeValidation() {
    const form = document.getElementById('calculator-form');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateField(this);
        });
    });
}

function validateField(field) {
    const value = field.value;
    const type = field.type;
    const name = field.name;
    
    // Retirer les classes d'erreur précédentes
    field.classList.remove('error', 'valid');
    
    // Validation selon le type
    switch (name) {
        case 'poids':
            if (value && (isNaN(value) || value <= 0 || value > 10000)) {
                field.classList.add('error');
                showFieldError(field, 'Poids invalide (0.1 - 10000 kg)');
            } else if (value) {
                field.classList.add('valid');
            }
            break;
            
        case 'nb_palettes':
            if (value && (isNaN(value) || value < 1 || value > 50)) {
                field.classList.add('error');
                showFieldError(field, 'Nombre de palettes invalide (1-50)');
            } else if (value) {
                field.classList.add('valid');
            }
            break;
    }
}

function showFieldError(field, message) {
    // Supprimer le message d'erreur existant
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Ajouter le nouveau message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = 'var(--error-color)';
    errorDiv.style.fontSize = '0.75rem';
    errorDiv.style.marginTop = '0.25rem';
    
    field.parentNode.appendChild(errorDiv);
}

// Sauvegarde automatique
function setupAutoSave() {
    const form = document.getElementById('calculator-form');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            saveFormData();
        });
    });
    
    // Restaurer les données sauvegardées
    restoreFormData();
}

function saveFormData() {
    const form = document.getElementById('calculator-form');
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    localStorage.setItem('calculator_form_data', JSON.stringify(data));
}

function restoreFormData() {
    const savedData = localStorage.getItem('calculator_form_data');
    if (!savedData) return;
    
    try {
        const data = JSON.parse(savedData);
        
        Object.keys(data).forEach(key => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = Array.isArray(data[key]) ? 
                        data[key].includes(field.value) : 
                        data[key] === field.value;
                } else {
                    field.value = Array.isArray(data[key]) ? data[key][0] : data[key];
                }
            }
        });
    } catch (e) {
        console.log('Erreur restauration données form:', e);
    }
}

// Gestion des résultats
function showResultDetails(index) {
    const detailsDiv = document.getElementById(`details-${index}`);
    if (detailsDiv) {
        const isVisible = detailsDiv.style.display !== 'none';
        detailsDiv.style.display = isVisible ? 'none' : 'block';
        
        // Mettre à jour le bouton
        const button = event.target;
        button.textContent = isVisible ? '📋 Détails' : '📋 Masquer';
    }
}

function selectOffer(index) {
    if (!calculatorResults || !calculatorResults[index]) {
        alert('Erreur : offre non disponible');
        return;
    }
    
    const offer = calculatorResults[index];
    const confirmed = confirm(
        `Confirmer la sélection de l'offre ${offer.transporteur} ?\n\n` +
        `Prix TTC : ${offer.prix_ttc}€\n` +
        `Délai : ${offer.delai || 'N/A'}`
    );
    
    if (confirmed) {
        // Ici vous pouvez ajouter la logique pour traiter la sélection
        console.log('Offre sélectionnée:', offer);
        
        // Sauvegarder la sélection
        localStorage.setItem('selected_offer', JSON.stringify(offer));
        
        // Redirection vers la page de commande (à implémenter)
        // window.location.href = '/commande/';
        
        alert('Offre sélectionnée et sauvegardée !');
    }
}

function exportResults() {
    if (!calculatorResults) {
        alert('Aucun résultat à exporter');
        return;
    }
    
    // Créer les données CSV
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Transporteur,Prix HT,Prix TTC,Delai,Service\n";
    
    calculatorResults.forEach(result => {
        const row = [
            result.transporteur,
            result.prix_ht,
            result.prix_ttc,
            result.delai || 'N/A',
            result.service || 'Standard'
        ].join(',');
        csvContent += row + "\n";
    });
    
    // Télécharger le fichier
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `frais_port_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function emailResults() {
    if (!calculatorResults) {
        alert('Aucun résultat à envoyer');
        return;
    }
    
    // Créer le contenu de l'email
    let emailBody = "Résultats du calcul de frais de port:\n\n";
    
    calculatorResults.forEach((result, index) => {
        emailBody += `${index + 1}. ${result.transporteur}\n`;
        emailBody += `   Prix HT: ${result.prix_ht}€\n`;
        emailBody += `   Prix TTC: ${result.prix_ttc}€\n`;
        emailBody += `   Délai: ${result.delai || 'N/A'}\n\n`;
    });
    
    emailBody += "Généré par Guldagil Portal\n";
    emailBody += `Le ${new Date().toLocaleString('fr-FR')}`;
    
    // Ouvrir le client email
    const subject = encodeURIComponent('Résultats calcul frais de port');
    const body = encodeURIComponent(emailBody);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

function printResults() {
    if (!calculatorResults) {
        alert('Aucun résultat à imprimer');
        return;
    }
    
    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank');
    
    let printContent = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Résultats calcul frais de port</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .result { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; }
            .best { background-color: #f0f9ff; }
            .price { font-weight: bold; font-size: 18px; color: #3b82f6; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>🧮 Résultats calcul frais de port</h1>
            <p>Généré le ${new Date().toLocaleString('fr-FR')}</p>
        </div>
        
        <div class="results">
    `;
    
    calculatorResults.forEach((result, index) => {
        printContent += `
        <div class="result ${index === 0 ? 'best' : ''}">
            ${index === 0 ? '<strong>🏆 MEILLEURE OFFRE</strong><br>' : ''}
            <h3>${result.transporteur}</h3>
            <div class="price">Prix TTC: ${result.prix_ttc}€</div>
            <div>Prix HT: ${result.prix_ht}€</div>
            <div>Délai: ${result.delai || 'N/A'}</div>
        </div>
        `;
    });
    
    printContent += `
        </div>
        <div class="footer">
            <p>Guldagil Portal - Solutions de transport</p>
        </div>
    </body>
    </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// FAQ Toggle
function toggleFAQ(id) {
    const answer = document.getElementById(`faq-${id}`);
    const toggle = event.target.closest('.faq-question').querySelector('.faq-toggle');
    
    if (answer.style.display === 'none' || !answer.style.display) {
        answer.style.display = 'block';
        toggle.textContent = '▲';
        toggle.style.transform = 'rotate(180deg)';
    } else {
        answer.style.display = 'none';
        toggle.textContent = '▼';
        toggle.style.transform = 'rotate(0deg)';
    }
}

// Soumission du formulaire avec loading
document.getElementById('calculator-form').addEventListener('submit', function(e) {
    if (isCalculating) {
        e.preventDefault();
        return;
    }
    
    isCalculating = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-icon').nextSibling;
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    // Afficher le loading
    btnText.textContent = ' Calcul en cours...';
    btnLoading.style.display = 'inline';
    submitBtn.disabled = true;
    
    // Le formulaire sera soumis normalement
    // Le loading sera supprimé au rechargement de la page
});

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + Enter pour calculer
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('calculator-form').submit();
    }
    
    // Echap pour fermer les détails
    if (e.key === 'Escape') {
        const openDetails = document.querySelectorAll('.result-details-full[style*="block"]');
        openDetails.forEach(detail => {
            detail.style.display = 'none';
        });
    }
});

// Mode développement - Fonctions debug
<?php if (DEBUG): ?>
function debugCalculator() {
    console.group('🐛 Debug Calculateur');
    console.log('Départements disponibles:', <?= json_encode(array_column($departements, 'departement')) ?>);
    console.log('Options disponibles:', <?= json_encode($options) ?>);
    console.log('Transporteurs:', <?= json_encode(array_column($transporteurs, 'transporteur')) ?>);
    console.log('Résultats actuels:', calculatorResults);
    console.log('Données form sauvegardées:', localStorage.getItem('calculator_form_data'));
    console.groupEnd();
}

// Auto-debug si paramètre debug=verbose
if (new URLSearchParams(window.location.search).get('debug') === 'verbose') {
    setTimeout(debugCalculator, 1000);
}

// Fonction pour simuler des données de test
function fillTestData() {
    document.getElementById('departement').value = '<?= $departements[0]['departement'] ?? '' ?>';
    document.getElementById('poids').value = '25.5';
    document.getElementById('type_envoi').value = 'standard';
    document.getElementById('nb_palettes').value = '2';
    
    console.log('✅ Données de test remplies');
}

// Raccourci pour remplir les données de test (Ctrl+Shift+T)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'T') {
        e.preventDefault();
        fillTestData();
    }
});

console.log('🐛 Mode debug actif - Utilisez debugCalculator() ou Ctrl+Shift+T pour les données test');
<?php endif; ?>

</script>
