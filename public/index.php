<?php
/**
 * GULDAGIL PORTAL - Calculateur de frais de port
 * Version production - Focus calculateur
 */

// Configuration
require_once __DIR__ . '/../config/config.php';

// Constantes
define('BUILD_NUMBER', date('Ymd') . '001');
define('BUILD_DATE', date('Y-m-d H:i:s'));

// Récupération des données pour le calculateur
try {
    // Départements disponibles
    $stmt = $db->prepare("SELECT DISTINCT departement FROM gul_taxes_transporteurs ORDER BY departement");
    $stmt->execute();
    $departements = $stmt->fetchAll();
    
    // Options supplémentaires
    $stmt = $db->prepare("SELECT * FROM gul_options_supplementaires WHERE actif = 1 ORDER BY nom");
    $stmt->execute();
    $options = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError('Erreur récupération données', ['error' => $e->getMessage()]);
    $departements = [];
    $options = [];
}

// Traitement calcul
$resultat = null;
$erreur = null;

if ($_POST['calculate'] ?? false) {
    try {
        $departement = clean($_POST['departement'] ?? '');
        $poids = (float)($_POST['poids'] ?? 0);
        $type_envoi = clean($_POST['type_envoi'] ?? 'standard');
        $nb_palettes = (int)($_POST['nb_palettes'] ?? 1);
        
        if (empty($departement) || $poids <= 0) {
            throw new Exception('Veuillez remplir tous les champs obligatoires');
        }
        
        // Calcul simple
        $stmt = $db->prepare("
            SELECT transporteur, prix_kg, prix_fixe 
            FROM gul_taxes_transporteurs 
            WHERE departement = ? 
            ORDER BY transporteur
        ");
        $stmt->execute([$departement]);
        $tarifs = $stmt->fetchAll();
        
        $resultat = [];
        foreach ($tarifs as $tarif) {
            $prixBase = $tarif['prix_fixe'] + ($poids * $tarif['prix_kg']);
            
            // Palettes supplémentaires
            if ($nb_palettes > 1) {
                $prixBase += ($nb_palettes - 1) * 15;
            }
            
            // Type d'envoi
            switch ($type_envoi) {
                case 'express': $prixBase *= 1.3; break;
                case 'urgent': $prixBase *= 1.5; break;
            }
            
            $resultat[] = [
                'transporteur' => $tarif['transporteur'],
                'prix_ht' => round($prixBase, 2),
                'prix_ttc' => round($prixBase * 1.2, 2),
                'delai' => '2-3 jours'
            ];
        }
        
        // Trier par prix
        usort($resultat, fn($a, $b) => $a['prix_ht'] <=> $b['prix_ht']);
        
    } catch (Exception $e) {
        $erreur = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur de frais de port - Guldagil Portal</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: #f8fafc; color: #334155; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #3b82f6, #1e40af); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 2.5rem; text-align: center; }
        .header p { margin: 0.5rem 0 0; text-align: center; opacity: 0.9; }
        
        .calculator { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-label { font-weight: 600; margin-bottom: 0.5rem; color: #374151; }
        .form-control { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #3b82f6; }
        .btn { padding: 1rem 2rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #1e40af; }
        .btn-full { grid-column: 1 / -1; justify-self: center; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        
        .results { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .results h3 { margin: 0 0 1.5rem; color: #374151; }
        .result-grid { display: grid; gap: 1rem; }
        .result-card { border: 2px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; transition: all 0.2s; }
        .result-card:first-child { border-color: #10b981; background: #f0fdf4; }
        .result-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .transporteur { font-size: 1.25rem; font-weight: 700; color: #374151; margin-bottom: 1rem; }
        .prix { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .prix-ttc { font-size: 1.5rem; font-weight: 700; color: #3b82f6; }
        .best-badge { background: #10b981; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem; display: inline-block; }
        
        .footer { text-align: center; padding: 2rem 0; color: #6b7280; border-top: 1px solid #e5e7eb; margin-top: 3rem; }
        .version-info { font-size: 0.875rem; margin-top: 1rem; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 2rem; }
            .container { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🧮 Calculateur de frais de port</h1>
            <p>Comparez instantanément les tarifs de nos transporteurs partenaires</p>
        </div>
    </div>
    
    <div class="container">
        
        <!-- Formulaire de calcul -->
        <div class="calculator">
            <form method="POST">
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label class="form-label">📍 Département de destination</label>
                        <select name="departement" class="form-control" required>
                            <option value="">Sélectionnez un département</option>
                            <?php foreach ($departements as $dept): ?>
                            <option value="<?= clean($dept['departement']) ?>" 
                                    <?= ($_POST['departement'] ?? '') === $dept['departement'] ? 'selected' : '' ?>>
                                <?= clean($dept['departement']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">⚖️ Poids total (kg)</label>
                        <input type="number" name="poids" class="form-control" 
                               min="0.1" max="10000" step="0.1" 
                               value="<?= clean($_POST['poids'] ?? '') ?>" 
                               placeholder="Ex: 25.5" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🚚 Type d'envoi</label>
                        <select name="type_envoi" class="form-control">
                            <option value="standard" <?= ($_POST['type_envoi'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>
                                Standard (2-3 jours)
                            </option>
                            <option value="express" <?= ($_POST['type_envoi'] ?? '') === 'express' ? 'selected' : '' ?>>
                                Express (+30% - 1-2 jours)
                            </option>
                            <option value="urgent" <?= ($_POST['type_envoi'] ?? '') === 'urgent' ? 'selected' : '' ?>>
                                Urgent (+50% - 24h)
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📦 Nombre de palettes</label>
                        <input type="number" name="nb_palettes" class="form-control" 
                               min="1" max="50" value="<?= clean($_POST['nb_palettes'] ?? '1') ?>">
                    </div>
                    
                    <button type="submit" name="calculate" class="btn btn-full">
                        🧮 Calculer les frais de port
                    </button>
                    
                </div>
            </form>
        </div>
        
        <!-- Affichage erreur -->
        <?php if ($erreur): ?>
        <div class="alert alert-error">
            <strong>❌ Erreur :</strong> <?= clean($erreur) ?>
        </div>
        <?php endif; ?>
        
        <!-- Résultats -->
        <?php if ($resultat && !$erreur): ?>
        <div class="results">
            <h3>📊 Résultats du calcul (<?= count($resultat) ?> offres trouvées)</h3>
            <div class="result-grid">
                <?php foreach ($resultat as $index => $r): ?>
                <div class="result-card">
                    <?php if ($index === 0): ?>
                    <div class="best-badge">🏆 Meilleure offre</div>
                    <?php endif; ?>
                    
                    <div class="transporteur"><?= clean($r['transporteur']) ?></div>
                    
                    <div class="prix">
                        <span>Prix HT :</span>
                        <span><?= formatPrice($r['prix_ht']) ?></span>
                    </div>
                    
                    <div class="prix">
                        <span>Prix TTC :</span>
                        <span class="prix-ttc"><?= formatPrice($r['prix_ttc']) ?></span>
                    </div>
                    
                    <div class="prix">
                        <span>Délai :</span>
                        <span><?= clean($r['delai']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <div class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Guldagil Portal - Solutions de transport</p>
            <div class="version-info">
                Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?> - <?= formatDate(BUILD_DATE) ?>
            </div>
        </div>
    </div>
    
</body>
</html>
