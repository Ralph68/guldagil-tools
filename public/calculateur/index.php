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

                        <!-- Section ADR améliorée -->
                        <section class="adr-section">
                            <div class="adr-toggle-container">
                                <div class="adr-label">
                                    ⚠️ Marchandises dangereuses (ADR)
                                    <span class="adr-indicator inactive" id="adr-indicator">NON</span>
                                </div>
                                <div class="adr-toggle" id="adr-toggle"></div>
                            </div>
                            <div class="adr-info" id="adr-info">
                                Aucun supplément matières dangereuses
                            </div>
                            <input type="hidden" id="adr_hidden" name="adr" value="non">
                        </section>

                        <!-- Section Options mutuellement exclusives -->
                        <section class="options-section">
                            <h3 class="options-title">
                                🚀 Options de livraison
                                <span class="options-subtitle">(une seule sélection possible)</span>
                            </h3>
                            <div class="options-grid">
                                <div class="option-card" data-option="standard">
                                    <div class="option-title">Standard</div>
                                    <div class="option-description">Livraison standard 24-48h</div>
                                    <div class="option-impact neutral">Tarif de base</div>
                                </div>

                                <div class="option-card" data-option="priority">
                                    <div class="option-title">Prioritaire</div>
                                    <div class="option-description">Livraison prioritaire sous 24h</div>
                                    <div class="option-impact positive">Impact calculé</div>
                                </div>

                                <div class="option-card" data-option="date">
                                    <div class="option-title">Date fixe</div>
                                    <div class="option-description">Livraison à date imposée</div>
                                    <div class="option-impact positive">Impact calculé</div>
                                </div>

                                <div class="option-card" data-option="rdv">
                                    <div class="option-title">Prise de RDV</div>
                                    <div class="option-description">Rendez-vous obligatoire</div>
                                    <div class="option-impact positive">Impact calculé</div>
                                </div>
                            </div>
                            <input type="hidden" id="option_selected" name="option" value="standard">
                        </section>

                        <!-- Section Enlèvement séparée -->
                        <section class="enlevement-section" id="enlevement-section">
                            <div class="enlevement-toggle">
                                <input type="checkbox" 
                                       id="enlevement" 
                                       name="enlevement" 
                                       class="enlevement-checkbox">
                                <label for="enlevement" class="enlevement-label">
                                    🏭 Enlèvement sur site expéditeur
                                </label>
                            </div>
                            <div class="enlevement-info" id="enlevement-info">
                                Cochez pour ajouter l'enlèvement sur site expéditeur
                            </div>
                        </section>
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
