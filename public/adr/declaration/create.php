<?php
// public/adr/declaration/create.php - Version simplifiée
session_start();

// Vérification authentification ADR (temporaire)
if (!isset($_SESSION['adr_logged_in']) || $_SESSION['adr_logged_in'] !== true) {
    $_SESSION['adr_logged_in'] = true;
    $_SESSION['adr_user'] = 'demo.user';
    $_SESSION['adr_login_time'] = time();
}

require __DIR__ . '/../../../config.php';

// Configuration
define('GULDAGIL_EXPEDITEUR', [
    'nom' => 'GULDAGIL',
    'adresse' => "4 Rue Robert Schuman\n68170 RIXHEIM",
    'telephone' => '03 89 44 13 17',
    'email' => 'guldagil@guldagil.com'
]);

$transporteurs = [
    'heppner' => 'Heppner',
    'xpo' => 'XPO Logistics', 
    'kn' => 'Kuehne + Nagel'
];

// Traitement du formulaire
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = processExpedition($db, $_POST);
    if ($result['success']) {
        $success = $result['message'];
        $expeditionId = $result['expedition_id'];
    } else {
        $errors = $result['errors'];
    }
}

/**
 * Traitement de l'expédition
 */
function processExpedition($db, $data) {
    try {
        // Validation simple
        $destinataire = $data['destinataire'] ?? '';
        $transporteur = $data['transporteur'] ?? '';
        $date = $data['date_expedition'] ?? '';
        $produits = $data['produits'] ?? '';
        
        if (!$destinataire || !$transporteur || !$date || !$produits) {
            return ['success' => false, 'errors' => ['Tous les champs sont obligatoires']];
        }
        
        // Générer numéro expédition
        $numero = 'ADR-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Insertion simplifiée
        $stmt = $db->prepare("
            INSERT INTO gul_adr_expeditions 
            (numero_expedition, destinataire, transporteur, date_expedition, produits, cree_par, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $numero,
            $destinataire,
            $transporteur,
            $date,
            $produits,
            $_SESSION['adr_user']
        ]);
        
        return [
            'success' => true,
            'message' => "Expédition $numero créée avec succès",
            'expedition_id' => $db->lastInsertId()
        ];
        
    } catch (Exception $e) {
        error_log("Erreur création expédition: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Erreur lors de la création']];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle expédition ADR - Guldagil</title>
    <link rel="stylesheet" href="../../assets/css/adr.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .form-content {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #ff6b35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Nouvelle expédition ADR</h1>
            <p>Déclaration de marchandises dangereuses</p>
        </div>
        
        <div class="form-content">
            <!-- Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    ❌ <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire simplifié -->
            <form method="POST">
                <!-- Informations expéditeur (fixe) -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4 style="color: #ff6b35; margin-bottom: 10px;">📤 Expéditeur</h4>
                    <div>
                        <strong><?= GULDAGIL_EXPEDITEUR['nom'] ?></strong><br>
                        <?= nl2br(htmlspecialchars(GULDAGIL_EXPEDITEUR['adresse'])) ?><br>
                        📞 <?= GULDAGIL_EXPEDITEUR['telephone'] ?>
                    </div>
                </div>
                
                <!-- Destinataire -->
                <div class="form-group">
                    <label for="destinataire">📍 Destinataire *</label>
                    <textarea class="form-control" 
                              id="destinataire" 
                              name="destinataire" 
                              rows="3" 
                              placeholder="Nom, adresse complète du destinataire..."
                              required><?= htmlspecialchars($_POST['destinataire'] ?? '') ?></textarea>
                </div>
                
                <!-- Transport -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="transporteur">🚚 Transporteur *</label>
                        <select class="form-control" id="transporteur" name="transporteur" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($transporteurs as $code => $nom): ?>
                                <option value="<?= $code ?>" <?= ($_POST['transporteur'] ?? '') === $code ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nom) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_expedition">📅 Date d'expédition *</label>
                        <input type="date" 
                               class="form-control" 
                               id="date_expedition" 
                               name="date_expedition"
                               value="<?= $_POST['date_expedition'] ?? date('Y-m-d') ?>"
                               min="<?= date('Y-m-d') ?>"
                               required>
                    </div>
                </div>
                
                <!-- Produits -->
                <div class="form-group">
                    <label for="produits">⚠️ Produits ADR *</label>
                    <textarea class="form-control" 
                              id="produits" 
                              name="produits" 
                              rows="6" 
                              placeholder="Liste des produits avec quantités :&#10;- Code produit 1 : quantité&#10;- Code produit 2 : quantité&#10;..."
                              required><?= htmlspecialchars($_POST['produits'] ?? '') ?></textarea>
                    <small style="color: #666;">
                        💡 Exemple : GULTRAT pH+ : 25L, PERFORMAX : 200L
                    </small>
                </div>
                
                <!-- Observations -->
                <div class="form-group">
                    <label for="observations">📝 Observations (optionnel)</label>
                    <textarea class="form-control" 
                              id="observations" 
                              name="observations" 
                              rows="2" 
                              placeholder="Remarques particulières..."><?= htmlspecialchars($_POST['observations'] ?? '') ?></textarea>
                </div>
                
                <!-- Actions -->
                <div class="actions">
                    <a href="../dashboard.php" class="btn btn-secondary">
                        ⬅️ Retour
                    </a>
                    
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            🔄 Réinitialiser
                        </button>
                        <button type="submit" class="btn btn-primary">
                            🚀 Créer l'expédition
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function resetForm() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
                document.querySelector('form').reset();
            }
        }
        
        // Auto-resize des textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
        
        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const destinataire = document.getElementById('destinataire').value.trim();
            const transporteur = document.getElementById('transporteur').value;
            const produits = document.getElementById('produits').value.trim();
            
            if (!destinataire || !transporteur || !produits) {
                e.preventDefault();
                alert('❌ Veuillez remplir tous les champs obligatoires');
                return false;
            }
            
            if (destinataire.length < 10) {
                e.preventDefault();
                alert('❌ L\'adresse du destinataire semble incomplète');
                document.getElementById('destinataire').focus();
                return false;
            }
            
            if (produits.length < 5) {
                e.preventDefault();
                alert('❌ La liste des produits semble incomplète');
                document.getElementById('produits').focus();
                return false;
            }
            
            return confirm('Confirmer la création de cette expédition ADR ?');
        });
        
        console.log('✅ Formulaire ADR simplifié initialisé');
    </script>
</body>
</html>
