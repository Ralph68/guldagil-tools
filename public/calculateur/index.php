<!-- 
public/calculateur/index.php - Interface utilisateur améliorée
Respect structure MVC modulaire existante
-->
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/Transport.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur Frais de Port - Guldagil</title>
    
    <!-- CSS modulaires séparés -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/layout.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/form.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/form-improvements.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/results.css">
</head>
<body class="calculateur-app">
    <!-- Header -->
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                    <div>
                        <h1 class="brand-title">Calculateur Frais de Port</h1>
                        <p class="brand-subtitle">Comparaison transporteurs</p>
                    </div>
                </div>
                <div class="version-info">
                    <span>v0.5 beta</span>
                    <span>Build <?= date('Ymd-His') ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="app-main">
        <div class="container">
            <div class="calc-layout">
                <!-- Formulaire principal -->
                <div class="form-panel">
                    <form id="calculateur-form" class="calc-form">
                        <!-- Informations de base -->
                        <section class="form-section">
                            <h2 class="section-title">📍 Informations de base</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="departement">Département destination</label>
                                    <input type="number" 
                                           id="departement" 
                                           name="departement" 
                                           min="1" 
                                           max="99" 
                                           placeholder="Ex: 75" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="poids">Poids total (kg)</label>
                                    <input type="number" 
                                           id="poids" 
                                           name="poids" 
                                           min="0.1" 
                                           step="0.1" 
                                           placeholder="Ex: 25.5" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="type_envoi">Type d'envoi</label>
                                    <select id="type_envoi" name="type_envoi" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="colis">Colis</option>
                                        <option value="palette">Palette</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="nb_palettes">Nombre palettes EUR</label>
                                    <input type="number" 
                                           id="nb_palettes" 
                                           name="nb_palettes" 
                                           min="0" 
                                           value="0" 
                                           placeholder="0">
                                </div>
                            </div>
                        </section>

                        <!-- Section ADR claire - remplace la section ADR actuelle -->
<div class="form-field">
    <label class="field-label">
        ⚠️ Marchandises dangereuses (ADR)
    </label>
    <div class="radio-buttons">
        <label class="radio-btn">
            <input type="radio" name="adr" value="non" checked class="auto-calc">
            <div class="radio-content">
                <span>❌ Non</span>
            </div>
        </label>
        <label class="radio-btn">
            <input type="radio" name="adr" value="oui" class="auto-calc">
            <div class="radio-content">
                <span>⚠️ Oui</span>
                <small>+62€ HT</small>
            </div>
        </label>
    </div>
    <div class="field-help">
        ADR : transport de marchandises dangereuses par route
    </div>
</div>

                       <!-- Section Options transport - remplace la section options actuelle -->
<div class="form-field">
    <label class="field-label">
        🚀 Options de livraison
        <small>(sélection unique)</small>
    </label>
    <div class="options-grid">
        <label class="radio-btn">
            <input type="radio" name="service_livraison" value="standard" checked class="auto-calc">
            <div class="radio-content">
                <strong>Standard</strong>
                <small>24-48h</small>
            </div>
        </label>
        
        <label class="radio-btn">
            <input type="radio" name="service_livraison" value="premium13" class="auto-calc">
            <div class="radio-content">
                <strong>Premium 13h</strong>
                <small>Calculé</small>
            </div>
        </label>
        
        <label class="radio-btn">
            <input type="radio" name="service_livraison" value="premium18" class="auto-calc">
            <div class="radio-content">
                <strong>Premium 18h</strong>
                <small>Calculé</small>
            </div>
        </label>
        
        <label class="radio-btn">
            <input type="radio" name="service_livraison" value="rdv" class="auto-calc">
            <div class="radio-content">
                <strong>RDV</strong>
                <small>Calculé</small>
            </div>
        </label>
    </div>
</div>

<!-- Section Enlèvement - NOUVELLE, séparée des options -->
<div class="form-field enlevement-section" id="enlevement-section">
    <div class="checkbox-container">
        <label class="checkbox-label">
            <input type="checkbox" name="enlevement" value="oui" class="auto-calc" id="enlevement-checkbox">
            <span class="label-text">
                🏭 Enlèvement sur site expéditeur
            </span>
        </label>
    </div>
    <div class="field-help" id="enlevement-help">
        Disponible uniquement avec livraison standard
    </div>
</div>
                    </form>
                </div>

                <!-- Panneau résultats -->
                <div class="results-panel">
                    <div class="results-container" id="results-container">
                        <div class="results-header">
                            <h2>📊 Comparaison des tarifs</h2>
                            <div class="calculation-status" id="calculation-status">
                                Saisissez vos critères pour voir les tarifs
                            </div>
                        </div>
                        
                        <div class="results-content" id="results-content">
                            <!-- Résultats chargés dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>
