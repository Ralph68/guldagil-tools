<?php
/**
 * Titre: Module Matériel - Création de demandes
 * Chemin: /public/materiel/requests/create.php
 * Version: 0.5 beta + build auto
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';
require_once dirname(__DIR__) . '/classes/MaterielManager.php';

// Variables pour template
$page_title = 'Nouvelle Demande';
$page_subtitle = 'Demande d\'équipement ou d\'outillage';
$current_module = 'materiel';
$module_css = true;
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $_SESSION['user'] ?? ['username' => 'Anonyme', 'role' => 'guest'];

// Vérification authentification
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_role = $current_user['role'] ?? 'guest';

// Manager matériel
$materielManager = new MaterielManager();

// Gestion du formulaire
$message = '';
$error = '';
$step = 1; // Étape du formulaire (1: type, 2: détails, 3: justification)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['step'])) {
            $step = (int)$_POST['step'];
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'create_request') {
            // Création de la demande
            $employee_id = $materielManager->getEmployeeIdByUser($current_user);
            if (!$employee_id) {
                throw new Exception('Profil employé introuvable. Contactez l\'administrateur.');
            }
            
            $request_data = [
                'employee_id' => $employee_id,
                'type_demande' => $_POST['type_demande'],
                'template_id' => $_POST['template_id'] ?? null,
                'item_remplace_id' => $_POST['item_remplace_id'] ?? null,
                'quantite_demandee' => $_POST['quantite_demandee'] ?? 1,
                'justification' => $_POST['justification'],
                'urgence' => $_POST['urgence'] ?? 'normale',
                'date_livraison_souhaitee' => $_POST['date_livraison_souhaitee'] ?: null,
                'observations' => $_POST['observations'] ?? null
            ];
            
            $request_id = $materielManager->createDemande($request_data);
            
            if ($request_id) {
                $_SESSION['success_message'] = 'Demande créée avec succès. Numéro de demande : #' . $request_id;
                header('Location: ../index.php?view=my_requests');
                exit;
            } else {
                throw new Exception('Erreur lors de la création de la demande');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des données
$categories = $materielManager->getCategories();
$templates = $materielManager->getTemplatesByCategory();
$my_equipment = $materielManager->getMyEquipment($current_user);

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/'],
    ['icon' => '🔧', 'text' => 'Matériel', 'url' => '../index.php'],
    ['icon' => '📝', 'text' => 'Nouvelle demande', 'url' => '', 'active' => true]
];

include ROOT_PATH . '/templates/header.php';
?>

<div class="request-container">
    <div class="request-header">
        <div class="header-content">
            <div class="header-info">
                <h1>📝 Nouvelle Demande</h1>
                <p class="subtitle">Demander un équipement, un remplacement ou une réparation</p>
            </div>
            
            <div class="progress-steps">
                <div class="step <?= $step >= 1 ? 'active' : '' ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">Type</div>
                </div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">Détails</div>
                </div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                    <div class="step-number">3</div>
                    <div class="step-label">Justification</div>
                </div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                    <div class="step-number">✓</div>
                    <div class="step-label">Validation</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="request-form-container">
        <form method="POST" id="requestForm" class="request-form">
            <input type="hidden" name="step" id="currentStep" value="<?= $step ?>">
            
            <!-- Étape 1: Type de demande -->
            <div class="form-step <?= $step === 1 ? 'active' : '' ?>" id="step1">
                <div class="step-content">
                    <h2>🎯 Type de demande</h2>
                    <p class="step-description">Sélectionnez le type de demande que vous souhaitez effectuer</p>
                    
                    <div class="request-types">
                        <div class="type-card" data-type="nouveau">
                            <div class="type-icon">🆕</div>
                            <div class="type-content">
                                <h3>Nouvel équipement</h3>
                                <p>Demander un nouvel outil ou équipement non disponible actuellement</p>
                                <ul>
                                    <li>Nouvel arrivant</li>
                                    <li>Évolution du poste</li>
                                    <li>Besoin spécifique</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="type-card" data-type="remplacement">
                            <div class="type-icon">🔄</div>
                            <div class="type-content">
                                <h3>Remplacement</h3>
                                <p>Remplacer un équipement défaillant, perdu ou en fin de vie</p>
                                <ul>
                                    <li>Équipement défaillant</li>
                                    <li>Usure normale</li>
                                    <li>Perte ou vol</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="type-card" data-type="reparation">
                            <div class="type-icon">🔧</div>
                            <div class="type-content">
                                <h3>Réparation</h3>
                                <p>Demander la réparation d'un équipement en panne</p>
                                <ul>
                                    <li>Panne temporaire</li>
                                    <li>Maintenance corrective</li>
                                    <li>Réglage technique</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="type-card" data-type="formation">
                            <div class="type-icon">🎓</div>
                            <div class="type-content">
                                <h3>Formation</h3>
                                <p>Demander une formation sur l'utilisation d'un équipement</p>
                                <ul>
                                    <li>Nouvel équipement</li>
                                    <li>Mise à niveau</li>
                                    <li>Sécurité</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="type_demande" id="selectedType" value="">
                </div>
            </div>

            <!-- Étape 2: Détails de la demande -->
            <div class="form-step <?= $step === 2 ? 'active' : '' ?>" id="step2">
                <div class="step-content">
                    <h2>📋 Détails de la demande</h2>
                    <p class="step-description">Précisez les détails de votre demande</p>
                    
                    <!-- Section pour nouveau/remplacement -->
                    <div class="details-section" id="equipmentDetails">
                        <div class="form-group">
                            <label for="category_id">Catégorie d'équipement</label>
                            <select id="category_id" name="category_id" onchange="loadTemplatesByCategory()">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="template_id">Équipement demandé</label>
                            <select id="template_id" name="template_id">
                                <option value="">Choisir d'abord une catégorie</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantite_demandee">Quantité</label>
                            <input type="number" id="quantite_demandee" name="quantite_demandee" min="1" value="1">
                        </div>
                    </div>
                    
                    <!-- Section pour remplacement -->
                    <div class="details-section" id="replacementDetails" style="display: none;">
                        <div class="form-group">
                            <label for="item_remplace_id">Équipement à remplacer</label>
                            <select id="item_remplace_id" name="item_remplace_id">
                                <option value="">Sélectionner l'équipement à remplacer</option>
                                <?php foreach ($my_equipment as $item): ?>
                                    <option value="<?= $item['item_id'] ?>">
                                        <?= htmlspecialchars($item['designation']) ?> 
                                        (<?= htmlspecialchars($item['numero_inventaire']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="raison_remplacement">Raison du remplacement</label>
                            <select id="raison_remplacement" name="raison_remplacement">
                                <option value="">Sélectionner une raison</option>
                                <option value="panne">Panne irréparable</option>
                                <option value="usure">Usure normale</option>
                                <option value="perte">Perte</option>
                                <option value="vol">Vol</option>
                                <option value="obsolescence">Obsolescence</option>
                                <option value="evolution_poste">Évolution du poste</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Section pour formation -->
                    <div class="details-section" id="formationDetails" style="display: none;">
                        <div class="form-group">
                            <label for="formation_type">Type de formation</label>
                            <select id="formation_type" name="formation_type">
                                <option value="">Sélectionner le type</option>
                                <option value="utilisation">Utilisation normale</option>
                                <option value="maintenance">Maintenance préventive</option>
                                <option value="securite">Consignes de sécurité</option>
                                <option value="specialisee">Formation spécialisée</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="niveau_urgence_formation">Niveau d'urgence</label>
                            <select id="niveau_urgence_formation" name="niveau_urgence_formation">
                                <option value="normale">Normal (sous 2 semaines)</option>
                                <option value="urgent">Urgent (sous 1 semaine)</option>
                                <option value="critique">Critique (dans les 3 jours)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="urgence">Niveau d'urgence</label>
                            <select id="urgence" name="urgence">
                                <option value="normale">Normal</option>
                                <option value="urgente">Urgent</option>
                                <option value="critique">Critique</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_livraison_souhaitee">Date de livraison souhaitée</label>
                            <input type="date" id="date_livraison_souhaitee" name="date_livraison_souhaitee" 
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 3: Justification -->
            <div class="form-step <?= $step === 3 ? 'active' : '' ?>" id="step3">
                <div class="step-content">
                    <h2>📄 Justification</h2>
                    <p class="step-description">Expliquez la raison de votre demande</p>
                    
                    <div class="form-group">
                        <label for="justification">Justification de la demande *</label>
                        <textarea id="justification" name="justification" rows="5" required
                                  placeholder="Expliquez pourquoi cet équipement est nécessaire, dans quel contexte il sera utilisé, etc."></textarea>
                        <small class="form-help">Soyez précis sur le besoin et l'utilisation prévue</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="observations">Observations complémentaires</label>
                        <textarea id="observations" name="observations" rows="3"
                                  placeholder="Informations supplémentaires, contraintes particulières, suggestions..."></textarea>
                    </div>
                    
                    <div class="urgence-info">
                        <h4>💡 Niveaux d'urgence</h4>
                        <div class="urgence-grid">
                            <div class="urgence-item normale">
                                <strong>Normal</strong>
                                <span>Traitement sous 5-10 jours ouvrés</span>
                            </div>
                            <div class="urgence-item urgente">
                                <strong>Urgent</strong>
                                <span>Traitement prioritaire sous 2-3 jours</span>
                            </div>
                            <div class="urgence-item critique">
                                <strong>Critique</strong>
                                <span>Traitement immédiat - arrêt d'activité</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 4: Validation -->
            <div class="form-step <?= $step === 4 ? 'active' : '' ?>" id="step4">
                <div class="step-content">
                    <h2>✅ Validation</h2>
                    <p class="step-description">Vérifiez les informations avant soumission</p>
                    
                    <div class="summary-card">
                        <h3>Récapitulatif de votre demande</h3>
                        <div class="summary-content" id="requestSummary">
                            <!-- Contenu généré dynamiquement -->
                        </div>
                    </div>
                    
                    <div class="validation-actions">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="confirmRequest" required>
                                <span class="checkmark"></span>
                                Je confirme que les informations sont exactes et que cette demande est justifiée
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="acceptConditions" required>
                                <span class="checkmark"></span>
                                J'accepte que cette demande soit soumise à validation hiérarchique
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="form-navigation">
                <button type="button" id="prevBtn" class="btn btn-secondary" onclick="changeStep(-1)" style="display: none;">
                    ⬅️ Précédent
                </button>
                
                <div class="nav-spacer"></div>
                
                <button type="button" id="nextBtn" class="btn btn-primary" onclick="changeStep(1)">
                    Suivant ➡️
                </button>
                
                <button type="submit" id="submitBtn" name="action" value="create_request" 
                        class="btn btn-success" style="display: none;">
                    🚀 Soumettre la demande
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Styles spécifiques aux demandes */
.request-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.request-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
}

.header-info h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.subtitle {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.progress-steps {
    display: flex;
    gap: 20px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    opacity: 0.5;
    transition: opacity 0.3s;
}

.step.active {
    opacity: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
}

.step.active .step-number {
    background: rgba(255, 255, 255, 0.9);
    color: #667eea;
}

.step-label {
    font-size: 0.85rem;
    font-weight: 600;
}

.request-form-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.form-step {
    display: none;
    padding: 40px;
}

.form-step.active {
    display: block;
}

.step-content h2 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 1.8rem;
}

.step-description {
    color: #6b7280;
    margin-bottom: 30px;
    font-size: 1.1rem;
}

.request-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.type-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafbfc;
}

.type-card:hover {
    border-color: #3b82f6;
    background: #f0f9ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.type-card.selected {
    border-color: #3b82f6;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.type-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    text-align: center;
}

.type-content h3 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 1.3rem;
    text-align: center;
}

.type-content p {
    color: #6b7280;
    margin-bottom: 15px;
    text-align: center;
    line-height: 1.5;
}

.type-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.type-content li {
    color: #374151;
    padding: 5px 0;
    position: relative;
    padding-left: 20px;
}

.type-content li:before {
    content: "✓";
    color: #10b981;
    font-weight: 700;
    position: absolute;
    left: 0;
}

.details-section {
    background: #f8fafc;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-help {
    color: #6b7280;
    font-size: 0.85rem;
    margin-top: 5px;
    display: block;
}

.urgence-info {
    background: #f0f9ff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #bfdbfe;
    margin-top: 25px;
}

.urgence-info h4 {
    margin: 0 0 15px 0;
    color: #1e40af;
}

.urgence-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.urgence-item {
    padding: 15px;
    border-radius: 6px;
    text-align: center;
}

.urgence-item.normale {
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
}

.urgence-item.urgente {
    background: #fef3c7;
    border: 1px solid #fde68a;
}

.urgence-item.critique {
    background: #fee2e2;
    border: 1px solid #fecaca;
}

.urgence-item strong {
    display: block;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.urgence-item span {
    font-size: 0.8rem;
    color: #6b7280;
}

.summary-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
}

.summary-card h3 {
    margin: 0 0 20px 0;
    color: #1f2937;
}

.summary-content {
    display: grid;
    gap: 15px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    font-weight: 600;
    color: #374151;
}

.summary-value {
    color: #1f2937;
    text-align: right;
}

.validation-actions {
    margin-bottom: 30px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 0.95rem;
    color: #374151;
    padding: 10px 0;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.form-navigation {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 25px 40px;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

.nav-spacer {
    flex: 1;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.btn-primary { 
    background: #3b82f6; 
    color: white; 
}

.btn-secondary { 
    background: #6b7280; 
    color: white; 
}

.btn-success { 
    background: #10b981; 
    color: white; 
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 20px;
    }
    
    .progress-steps {
        gap: 10px;
    }
    
    .request-types {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .urgence-grid {
        grid-template-columns: 1fr;
    }
    
    .form-step {
        padding: 25px;
    }
    
    .form-navigation {
        padding: 20px 25px;
        flex-direction: column;
        gap: 15px;
    }
    
    .nav-spacer {
        display: none;
    }
}
</style>

<script>
// Variables globales
let currentStep = 1;
const totalSteps = 4;
let selectedType = '';
let templatesByCategory = <?= json_encode($templates) ?>;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    updateStepDisplay();
    setupTypeSelection();
    updateNavigationButtons();
});

// Gestion des étapes
function changeStep(direction) {
    if (direction === 1 && !validateCurrentStep()) {
        return;
    }
    
    currentStep += direction;
    
    if (currentStep < 1) currentStep = 1;
    if (currentStep > totalSteps) currentStep = totalSteps;
    
    updateStepDisplay();
    updateNavigationButtons();
    
    if (currentStep === 4) {
        generateSummary();
    }
}

function updateStepDisplay() {
    // Masquer toutes les étapes
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Afficher l'étape actuelle
    document.getElementById('step' + currentStep).classList.add('active');
    
    // Mettre à jour les indicateurs de progression
    document.querySelectorAll('.progress-steps .step').forEach((step, index) => {
        if (index < currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Mettre à jour le champ hidden
    document.getElementById('currentStep').value = currentStep;
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Bouton précédent
    prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    
    // Bouton suivant / soumettre
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-flex';
    } else {
        nextBtn.style.display = 'inline-flex';
        submitBtn.style.display = 'none';
    }
}

// Validation des étapes
function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            if (!selectedType) {
                alert('Veuillez sélectionner un type de demande');
                return false;
            }
            break;
            
        case 2:
            if (selectedType !== 'formation') {
                const templateId = document.getElementById('template_id').value;
                if (!templateId) {
                    alert('Veuillez sélectionner un équipement');
                    return false;
                }
            }
            
            if (selectedType === 'remplacement') {
                const itemId = document.getElementById('item_remplace_id').value;
                const raison = document.getElementById('raison_remplacement').value;
                if (!itemId || !raison) {
                    alert('Veuillez sélectionner l\'équipement à remplacer et la raison');
                    return false;
                }
            }
            break;
            
        case 3:
            const justification = document.getElementById('justification').value.trim();
            if (!justification) {
                alert('La justification est obligatoire');
                return false;
            }
            if (justification.length < 20) {
                alert('La justification doit contenir au moins 20 caractères');
                return false;
            }
            break;
            
        case 4:
            const confirmRequest = document.getElementById('confirmRequest').checked;
            const acceptConditions = document.getElementById('acceptConditions').checked;
            if (!confirmRequest || !acceptConditions) {
                alert('Veuillez confirmer les informations et accepter les conditions');
                return false;
            }
            break;
    }
    return true;
}

// Sélection du type de demande
function setupTypeSelection() {
    document.querySelectorAll('.type-card').forEach(card => {
        card.addEventListener('click', function() {
            // Désélectionner toutes les cartes
            document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
            
            // Sélectionner la carte cliquée
            this.classList.add('selected');
            selectedType = this.dataset.type;
            document.getElementById('selectedType').value = selectedType;
            
            // Afficher/masquer les sections appropriées à l'étape 2
            updateDetailsVisibility();
        });
    });
}

function updateDetailsVisibility() {
    const equipmentDetails = document.getElementById('equipmentDetails');
    const replacementDetails = document.getElementById('replacementDetails');
    const formationDetails = document.getElementById('formationDetails');
    
    // Masquer toutes les sections
    equipmentDetails.style.display = 'none';
    replacementDetails.style.display = 'none';
    formationDetails.style.display = 'none';
    
    // Afficher les sections appropriées
    switch (selectedType) {
        case 'nouveau':
            equipmentDetails.style.display = 'block';
            break;
            
        case 'remplacement':
            equipmentDetails.style.display = 'block';
            replacementDetails.style.display = 'block';
            break;
            
        case 'reparation':
            replacementDetails.style.display = 'block';
            break;
            
        case 'formation':
            formationDetails.style.display = 'block';
            break;
    }
}

// Chargement des templates par catégorie
function loadTemplatesByCategory() {
    const categoryId = document.getElementById('category_id').value;
    const templateSelect = document.getElementById('template_id');
    
    // Vider la liste
    templateSelect.innerHTML = '<option value="">Sélectionner un équipement</option>';
    
    if (categoryId) {
        // Filtrer les templates par catégorie
        const filteredTemplates = templatesByCategory.filter(t => t.categorie_id == categoryId);
        
        filteredTemplates.forEach(template => {
            const option = document.createElement('option');
            option.value = template.id;
            option.textContent = template.designation;
            if (template.marque) {
                option.textContent += ' - ' + template.marque;
            }
            templateSelect.appendChild(option);
        });
    }
}

// Génération du résumé
function generateSummary() {
    const summaryContainer = document.getElementById('requestSummary');
    const typeLabels = {
        'nouveau': 'Nouvel équipement',
        'remplacement': 'Remplacement',
        'reparation': 'Réparation',
        'formation': 'Formation'
    };
    
    const urgenceLabels = {
        'normale': 'Normal',
        'urgente': 'Urgent',
        'critique': 'Critique'
    };
    
    let html = '';
    
    // Type de demande
    html += `
        <div class="summary-row">
            <div class="summary-label">Type de demande</div>
            <div class="summary-value">${typeLabels[selectedType] || selectedType}</div>
        </div>
    `;
    
    // Équipement demandé
    if (selectedType !== 'formation') {
        const templateSelect = document.getElementById('template_id');
        const selectedTemplate = templateSelect.options[templateSelect.selectedIndex];
        if (selectedTemplate && selectedTemplate.value) {
            html += `
                <div class="summary-row">
                    <div class="summary-label">Équipement</div>
                    <div class="summary-value">${selectedTemplate.textContent}</div>
                </div>
            `;
        }
        
        const quantite = document.getElementById('quantite_demandee').value;
        if (quantite && quantite > 1) {
            html += `
                <div class="summary-row">
                    <div class="summary-label">Quantité</div>
                    <div class="summary-value">${quantite}</div>
                </div>
            `;
        }
    }
    
    // Urgence
    const urgence = document.getElementById('urgence').value;
    html += `
        <div class="summary-row">
            <div class="summary-label">Urgence</div>
            <div class="summary-value">${urgenceLabels[urgence] || urgence}</div>
        </div>
    `;
    
    // Date de livraison
    const dateLivraison = document.getElementById('date_livraison_souhaitee').value;
    if (dateLivraison) {
        const date = new Date(dateLivraison);
        html += `
            <div class="summary-row">
                <div class="summary-label">Date souhaitée</div>
                <div class="summary-value">${date.toLocaleDateString('fr-FR')}</div>
            </div>
        `;
    }
    
    // Justification
    const justification = document.getElementById('justification').value;
    if (justification) {
        html += `
            <div class="summary-row">
                <div class="summary-label">Justification</div>
                <div class="summary-value" style="max-width: 300px; text-align: left;">
                    ${justification.substring(0, 100)}${justification.length > 100 ? '...' : ''}
                </div>
            </div>
        `;
    }
    
    summaryContainer.innerHTML = html;
}

// Gestion de la soumission
document.getElementById('requestForm').addEventListener('submit', function(e) {
    if (!validateCurrentStep()) {
        e.preventDefault();
        return false;
    }
    
    // Confirmation finale
    if (!confirm('Êtes-vous sûr de vouloir soumettre cette demande ?')) {
        e.preventDefault();
        return false;
    }
    
    // Désactiver le bouton pour éviter les doubles soumissions
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Envoi en cours...';
});
</script>

<?php include ROOT_PATH . '/templates/footer.php'; ?>
