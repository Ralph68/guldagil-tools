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
$page_subtitle = 'Demander du matériel ou outillage';
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

// Permissions pour créer des demandes
$can_create_request = true; // Tous les utilisateurs connectés peuvent créer des demandes

// Connexion BDD et Manager matériel - CORRECTION: avec paramètre $db
try {
    $db = function_exists('getDB') ? getDB() : new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $materielManager = new MaterielManager($db); // CORRECTION: paramètre ajouté
} catch (Exception $e) {
    error_log("Erreur BDD create demande: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

// Variables pour le formulaire
$errors = [];
$success = false;
$categories = $materielManager->getCategories();
$employees = $materielManager->getEmployees();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'employee_id' => (int)($_POST['employee_id'] ?? 0),
        'template_id' => (int)($_POST['template_id'] ?? 0),
        'type_demande' => $_POST['type_demande'] ?? 'nouveau',
        'quantite_demandee' => (int)($_POST['quantite_demandee'] ?? 1),
        'justification' => trim($_POST['justification'] ?? ''),
        'urgence' => $_POST['urgence'] ?? 'normale',
        'date_livraison_souhaitee' => $_POST['date_livraison_souhaitee'] ?? null
    ];
    
    // Validation
    if (empty($data['employee_id'])) {
        $errors[] = "Veuillez sélectionner un employé";
    }
    
    if (empty($data['template_id'])) {
        $errors[] = "Veuillez sélectionner un matériel";
    }
    
    if ($data['quantite_demandee'] < 1) {
        $errors[] = "La quantité doit être supérieure à 0";
    }
    
    if (empty($data['justification'])) {
        $errors[] = "Veuillez indiquer une justification";
    }
    
    // Si pas d'erreurs, créer la demande
    if (empty($errors)) {
        if ($materielManager->createDemande($data)) {
            $success = true;
            $_SESSION['materiel_success'] = "Demande créée avec succès";
            
            // Redirection vers la liste des demandes
            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Erreur lors de la création de la demande";
        }
    }
}

// Headers de base
$template_header = ROOT_PATH . '/templates/header.php';
$template_footer = ROOT_PATH . '/templates/footer.php';

if (file_exists($template_header)) {
    include $template_header;
}
?>

<!-- CRÉATION DEMANDE MATÉRIEL -->
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">📝 Nouvelle Demande</h1>
                            <p class="text-muted mb-0">Demander du matériel ou outillage</p>
                        </div>
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary">
                                ← Retour aux demandes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (!empty($errors)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger">
                <h6 class="alert-heading">Erreurs détectées :</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success">
                ✅ Demande créée avec succès !
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulaire de demande -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations de la demande</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="demandeForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="employee_id" class="form-label">Employé demandeur *</label>
                                <select name="employee_id" id="employee_id" class="form-select" required>
                                    <option value="">Sélectionnez un employé</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>" 
                                                <?= ($_POST['employee_id'] ?? '') == $employee['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($employee['nom'] . ' ' . $employee['prenom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="type_demande" class="form-label">Type de demande *</label>
                                <select name="type_demande" id="type_demande" class="form-select" required>
                                    <option value="nouveau" <?= ($_POST['type_demande'] ?? 'nouveau') === 'nouveau' ? 'selected' : '' ?>>
                                        Nouveau matériel
                                    </option>
                                    <option value="remplacement" <?= ($_POST['type_demande'] ?? '') === 'remplacement' ? 'selected' : '' ?>>
                                        Remplacement
                                    </option>
                                    <option value="reparation" <?= ($_POST['type_demande'] ?? '') === 'reparation' ? 'selected' : '' ?>>
                                        Réparation
                                    </option>
                                    <option value="formation" <?= ($_POST['type_demande'] ?? '') === 'formation' ? 'selected' : '' ?>>
                                        Formation
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Catégorie *</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"
                                                data-type="<?= $category['type'] ?>">
                                            <?= htmlspecialchars($category['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="template_id" class="form-label">Matériel demandé *</label>
                                <select name="template_id" id="template_id" class="form-select" required disabled>
                                    <option value="">Sélectionnez d'abord une catégorie</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="quantite_demandee" class="form-label">Quantité *</label>
                                <input type="number" name="quantite_demandee" id="quantite_demandee" 
                                       class="form-control" min="1" max="99" 
                                       value="<?= $_POST['quantite_demandee'] ?? 1 ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="urgence" class="form-label">Urgence</label>
                                <select name="urgence" id="urgence" class="form-select">
                                    <option value="normale" <?= ($_POST['urgence'] ?? 'normale') === 'normale' ? 'selected' : '' ?>>
                                        🟢 Normale
                                    </option>
                                    <option value="urgente" <?= ($_POST['urgence'] ?? '') === 'urgente' ? 'selected' : '' ?>>
                                        🟠 Urgente
                                    </option>
                                    <option value="critique" <?= ($_POST['urgence'] ?? '') === 'critique' ? 'selected' : '' ?>>
                                        🔴 Critique
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date_livraison_souhaitee" class="form-label">Date souhaitée</label>
                                <input type="date" name="date_livraison_souhaitee" id="date_livraison_souhaitee" 
                                       class="form-control" min="<?= date('Y-m-d') ?>"
                                       value="<?= $_POST['date_livraison_souhaitee'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="justification" class="form-label">Justification *</label>
                            <textarea name="justification" id="justification" class="form-control" 
                                      rows="4" required placeholder="Expliquez pourquoi vous avez besoin de ce matériel..."><?= htmlspecialchars($_POST['justification'] ?? '') ?></textarea>
                            <div class="form-text">
                                Décrivez précisément l'usage prévu, la nécessité, ou le problème à résoudre.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                📝 Créer la demande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Aide et informations -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">💡 Aide</h6>
                </div>
                <div class="card-body">
                    <h6>Types de demandes :</h6>
                    <ul class="small">
                        <li><strong>Nouveau :</strong> Premier équipement</li>
                        <li><strong>Remplacement :</strong> Matériel défaillant/perdu</li>
                        <li><strong>Réparation :</strong> Maintenance/révision</li>
                        <li><strong>Formation :</strong> Besoin de formation</li>
                    </ul>

                    <h6 class="mt-3">Niveaux d'urgence :</h6>
                    <ul class="small">
                        <li><span class="text-success">🟢 Normale :</span> Traitement standard</li>
                        <li><span class="text-warning">🟠 Urgente :</span> Priorité élevée</li>
                        <li><span class="text-danger">🔴 Critique :</span> Arrêt de travail</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <small>
                            <strong>Info :</strong> Les demandes sont traitées par ordre de priorité et de date de création.
                            Une justification claire accélère le traitement.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">📊 Vos demandes</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">En attente :</span>
                        <span class="badge bg-warning">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Validées :</span>
                        <span class="badge bg-success">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Ce mois :</span>
                        <span class="badge bg-info">0</span>
                    </div>
                    <hr>
                    <a href="index.php" class="btn btn-outline-primary btn-sm w-100">
                        Voir toutes mes demandes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour chargement dynamique des templates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const templateSelect = document.getElementById('template_id');
    
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        // Reset template select
        templateSelect.innerHTML = '<option value="">Chargement...</option>';
        templateSelect.disabled = true;
        
        if (categoryId) {
            // AJAX call pour récupérer les templates
            fetch(`../api/get_templates.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    templateSelect.innerHTML = '<option value="">Sélectionnez un matériel</option>';
                    
                    if (data.success && data.templates) {
                        data.templates.forEach(template => {
                            const option = document.createElement('option');
                            option.value = template.id;
                            option.textContent = `${template.designation}${template.marque ? ' - ' + template.marque : ''}${template.modele ? ' ' + template.modele : ''}`;
                            templateSelect.appendChild(option);
                        });
                        templateSelect.disabled = false;
                    } else {
                        templateSelect.innerHTML = '<option value="">Aucun matériel trouvé</option>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    templateSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        } else {
            templateSelect.innerHTML = '<option value="">Sélectionnez d\'abord une catégorie</option>';
            templateSelect.disabled = true;
        }
    });
    
    // Validation du formulaire
    document.getElementById('demandeForm').addEventListener('submit', function(e) {
        const justification = document.getElementById('justification').value.trim();
        
        if (justification.length < 10) {
            e.preventDefault();
            alert('La justification doit contenir au moins 10 caractères.');
            return false;
        }
    });
});
</script>

<?php
if (file_exists($template_footer)) {
    include $template_footer;
}
?>
