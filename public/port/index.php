<?php
/**
 * Titre: Calculateur de frais de port - Interface corrigée
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__, 2));

// Chargement configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Authentification simplifiée (désactivée temporairement)
$user_authenticated = true; // À réactiver plus tard
$current_user = ['username' => 'Utilisateur Test'];

$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '01';

// Variables template
$page_title = 'Calculateur de frais de port';
$page_subtitle = 'Comparaison des tarifs transport';
$current_module = 'calculateur';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- CSS intégré pour garantir le fonctionnement -->
    <style>
        /* Variables CSS */
        :root {
            --calc-primary: #3b82f6;
            --calc-primary-dark: #2563eb;
            --calc-success: #10b981;
            --calc-warning: #f59e0b;
            --calc-error: #ef4444;
            --calc-gray: #6b7280;
            --calc-light-gray: #f8fafc;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }
        
        /* Reset et base */
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: var(--calc-light-gray);
            line-height: 1.6;
        }
        
        /* Header */
        .calc-header {
            background: linear-gradient(135deg, var(--calc-primary), var(--calc-primary-dark));
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }
        
        .calc-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .header-title p {
            margin: 0.25rem 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .header-user {
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Breadcrumb */
        .calc-breadcrumb {
            background: white;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .calc-breadcrumb-content {
            max-width: 1200px;
            margin: 0 auto;
            font-size: 0.875rem;
            color: var(--calc-gray);
        }
        
        .calc-breadcrumb a {
            color: var(--calc-primary);
            text-decoration: none;
        }
        
        /* Container principal */
        .calc-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 2rem;
            min-height: calc(100vh - 140px);
        }
        
        /* Panneaux */
        .calc-form, .calc-results {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .calc-form {
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .calc-form-header, .calc-results-header {
            background: linear-gradient(135deg, var(--calc-primary), var(--calc-primary-dark));
            color: white;
            padding: 1.25rem;
        }
        
        .calc-form-header h2, .calc-results-header h2 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .calc-form-header p, .calc-results-header p {
            margin: 0.25rem 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        /* Contenu formulaire */
        .calc-form-content {
            padding: 1.5rem;
        }
        
        .calc-form-group {
            margin-bottom: 1.25rem;
        }
        
        .calc-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .calc-input, .calc-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }
        
        .calc-input:focus, .calc-select:focus {
            outline: none;
            border-color: var(--calc-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .calc-help {
            font-size: 0.75rem;
            color: var(--calc-gray);
            margin-top: 0.25rem;
        }
        
        /* Options */
        .calc-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .calc-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .calc-option:hover {
            background: #f8fafc;
            border-color: var(--calc-primary);
        }
        
        .calc-option input {
            margin: 0;
        }
        
        .calc-option label {
            margin: 0;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        /* Bouton calcul */
        .calc-button {
            width: 100%;
            background: linear-gradient(135deg, var(--calc-primary), var(--calc-primary-dark));
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }
        
        .calc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .calc-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Zone résultats */
        .calc-results-content {
            padding: 1.5rem;
            min-height: 400px;
        }
        
        .calc-empty-state {
            text-align: center;
            color: var(--calc-gray);
            padding: 3rem 1rem;
        }
        
        .calc-empty-state .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .calc-loading {
            text-align: center;
            padding: 2rem;
            color: var(--calc-primary);
        }
        
        .calc-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--calc-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Résultats */
        .calc-result-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .calc-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .calc-transporteur {
            font-weight: 600;
            color: #374151;
        }
        
        .calc-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--calc-primary);
        }
        
        .calc-details {
            font-size: 0.875rem;
            color: var(--calc-gray);
        }
        
        /* Messages d'erreur */
        .calc-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .calc-container {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1rem;
            }
            
            .calc-form {
                position: static;
            }
            
            .calc-header-content {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
        }
        
        /* États du formulaire */
        .calc-form-group.has-error .calc-input,
        .calc-form-group.has-error .calc-select {
            border-color: var(--calc-error);
        }
        
        .calc-form-group.has-error .calc-help {
            color: var(--calc-error);
        }
        
        /* Animation pour les résultats */
        .calc-result-card {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="calc-header">
    <div class="calc-header-content">
        <div class="header-title">
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <p><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
        <div class="header-user">
            👤 <?= htmlspecialchars($current_user['username']) ?>
        </div>
    </div>
</div>

<!-- Fil d'Ariane -->
<div class="calc-breadcrumb">
    <div class="calc-breadcrumb-content">
        🏠 <a href="/">Accueil</a> › 🧮 Calculateur de frais
    </div>
</div>

<!-- Interface principale -->
<div class="calc-container">
    <!-- Formulaire -->
    <div class="calc-form">
        <div class="calc-form-header">
            <h2>📋 Paramètres d'expédition</h2>
            <p>Complétez pour calculer</p>
        </div>
        
        <div class="calc-form-content">
            <form id="calcForm">
                <div class="calc-form-group">
                    <label for="departement" class="calc-label">Département destination *</label>
                    <input type="text" id="departement" class="calc-input" placeholder="Ex: 67" maxlength="2" required>
                    <div class="calc-help">Code département français (01-95)</div>
                </div>
                
                <div class="calc-form-group">
                    <label for="poids" class="calc-label">Poids total (kg) *</label>
                    <input type="number" id="poids" class="calc-input" placeholder="Ex: 25.5" min="0.1" step="0.1" required>
                    <div class="calc-help">Auto-palette si > 60kg</div>
                </div>
                
                <div class="calc-form-group">
                    <label for="type" class="calc-label">Type d'envoi</label>
                    <select id="type" class="calc-select">
                        <option value="colis">Colis standard</option>
                        <option value="palette">Palette</option>
                    </select>
                </div>
                
                <div class="calc-form-group" id="palettesGroup" style="display: none;">
                    <label for="palettes" class="calc-label">Nombre de palettes</label>
                    <input type="number" id="palettes" class="calc-input" placeholder="1" min="1" max="10" value="1">
                    <div class="calc-help">Maximum 10 palettes</div>
                </div>
                
                <div class="calc-form-group">
                    <label class="calc-label">Options spéciales</label>
                    <div class="calc-options">
                        <div class="calc-option">
                            <input type="checkbox" id="adr" name="options">
                            <label for="adr">Matières dangereuses (ADR)</label>
                        </div>
                        <div class="calc-option">
                            <input type="checkbox" id="enlevement" name="options">
                            <label for="enlevement">Enlèvement</label>
                        </div>
                    </div>
                </div>
                
                <div class="calc-form-group">
                    <label for="option_sup" class="calc-label">Service supplémentaire</label>
                    <select id="option_sup" class="calc-select">
                        <option value="standard">Standard</option>
                        <option value="express">Express</option>
                        <option value="urgence">Urgence</option>
                    </select>
                </div>
                
                <button type="submit" class="calc-button" id="calculateBtn">
                    🧮 Calculer les frais
                </button>
            </form>
        </div>
    </div>
    
    <!-- Résultats -->
    <div class="calc-results">
        <div class="calc-results-header">
            <h2>💰 Comparaison des tarifs</h2>
            <p>Résultats par transporteur</p>
        </div>
        
        <div class="calc-results-content" id="resultsContent">
            <div class="calc-empty-state">
                <div class="icon">📊</div>
                <p><strong>Prêt pour le calcul</strong></p>
                <p>Remplissez le formulaire à gauche pour<br>obtenir une comparaison des tarifs.</p>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript intégré -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('calcForm');
    const typeSelect = document.getElementById('type');
    const palettesGroup = document.getElementById('palettesGroup');
    const calculateBtn = document.getElementById('calculateBtn');
    const resultsContent = document.getElementById('resultsContent');
    
    // Gestion affichage nombre de palettes
    typeSelect.addEventListener('change', function() {
        if (this.value === 'palette') {
            palettesGroup.style.display = 'block';
        } else {
            palettesGroup.style.display = 'none';
        }
    });
    
    // Auto-switch vers palette si poids > 60kg
    document.getElementById('poids').addEventListener('input', function() {
        if (parseFloat(this.value) > 60) {
            typeSelect.value = 'palette';
            palettesGroup.style.display = 'block';
        }
    });
    
    // Validation du département
    document.getElementById('departement').addEventListener('input', function() {
        const value = this.value.replace(/\D/g, '');
        this.value = value;
        
        const group = this.closest('.calc-form-group');
        if (value && (parseInt(value) < 1 || parseInt(value) > 95)) {
            group.classList.add('has-error');
        } else {
            group.classList.remove('has-error');
        }
    });
    
    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const departement = document.getElementById('departement').value;
        const poids = parseFloat(document.getElementById('poids').value);
        
        if (!departement || departement.length !== 2) {
            showError('Veuillez saisir un département valide (01-95)');
            return;
        }
        
        if (!poids || poids <= 0) {
            showError('Veuillez saisir un poids valide');
            return;
        }
        
        // Affichage du loading
        showLoading();
        
        // Appel AJAX vers l'API de calcul réelle
        calculateWithAPI();
    });
    
    function showError(message) {
        resultsContent.innerHTML = `
            <div class="calc-error">
                ❌ ${message}
            </div>
        `;
    }
    
    function showLoading() {
        calculateBtn.disabled = true;
        calculateBtn.textContent = 'Calcul en cours...';
        
        resultsContent.innerHTML = `
            <div class="calc-loading">
                <div class="calc-spinner"></div>
                <p><strong>Calcul en cours...</strong></p>
                <p>Comparaison des tarifs transporteurs</p>
            </div>
        `;
    }
    
    function calculateWithAPI() {
        const formData = new FormData();
        formData.append('departement', document.getElementById('departement').value);
        formData.append('poids', document.getElementById('poids').value);
        formData.append('type', document.getElementById('type').value);
        formData.append('adr', document.getElementById('adr').checked ? 'oui' : 'non');
        formData.append('enlevement', document.getElementById('enlevement').checked ? '1' : '0');
        formData.append('option_sup', document.getElementById('option_sup').value);
        
        if (document.getElementById('type').value === 'palette') {
            formData.append('palettes', document.getElementById('palettes').value);
        }
        
        fetch('./api/calculate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = '🧮 Calculer les frais';
            
            if (data.success && data.carriers && data.carriers.length > 0) {
                displayRealResults(data);
            } else {
                showError(data.message || 'Aucun tarif disponible pour ces paramètres');
            }
        })
        .catch(error => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = '🧮 Calculer les frais';
            showError('Erreur de connexion : ' + error.message);
        });
    }
    
    function displayRealResults(data) {
        let html = '';
        
        // Tri par prix croissant
        const carriers = data.carriers.sort((a, b) => a.price - b.price);
        
        carriers.forEach((carrier, index) => {
            const badge = index === 0 ? ' 🏆 Meilleur prix' : '';
            const cardClass = index === 0 ? 'calc-result-card best-price' : 'calc-result-card';
            
            html += `
                <div class="${cardClass}">
                    <div class="calc-result-header">
                        <div class="calc-transporteur">${carrier.carrier_name}${badge}</div>
                        <div class="calc-price">${carrier.price_display}</div>
                    </div>
                    <div class="calc-details">Tarif TTC • Délai standard • Service ${document.getElementById('option_sup').value}</div>
                </div>
            `;
        });
        
        // Métadonnées
        html += `
            <div style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: var(--calc-gray);">
                ⚡ Calcul effectué en ${data.stats?.calculation_time || 0}ms
                ${data.best_rate ? ` • Économie: ${(carriers[carriers.length-1].price - carriers[0].price).toFixed(2)}€` : ''}
            </div>
        `;
        
        // Supprimer toutes les fonctions de simulation
        const results = simulateCalculation({
            departement,
            poids,
            type,
            adr,
            enlevement,
            option_sup,
            palettes
        });
        
        // Affichage des résultats
        displayResults(results);
    }
    
    function simulateCalculation(params) {
        // Tarifs de base simulés
        const baseTariffs = {
            xpo: { base: 15.50, perKg: 0.80, adr: 12.00, enlevement: 8.50 },
            heppner: { base: 14.20, perKg: 0.75, adr: 10.50, enlevement: 7.80 },
            kuehne: { base: 16.80, perKg: 0.85, adr: 13.20, enlevement: 9.20 }
        };
        
        // Multiplicateurs par département (zones)
        const zoneMultipliers = {
            '01': 1.1, '02': 1.05, '03': 1.0, '04': 1.15, '05': 1.2,
            '67': 1.0, '68': 1.05, '69': 1.08, '75': 1.3, '77': 1.25
        };
        
        const zoneMultiplier = zoneMultipliers[params.departement] || 1.1;
        
        // Multiplicateurs par type de service
        const serviceMultipliers = {
            standard: 1.0,
            express: 1.4,
            urgence: 1.8
        };
        
        const serviceMultiplier = serviceMultipliers[params.option_sup] || 1.0;
        
        const results = [];
        
        Object.entries(baseTariffs).forEach(([transporteur, tariff]) => {
            let total = tariff.base + (params.poids * tariff.perKg);
            
            // Ajustements
            if (params.type === 'palette') {
                total += (params.palettes - 1) * 15; // Palette supplémentaire
            }
            
            if (params.adr) {
                total += tariff.adr;
            }
            
            if (params.enlevement) {
                total += tariff.enlevement;
            }
            
            // Application des multiplicateurs
            total *= zoneMultiplier;
            total *= serviceMultiplier;
            
            // Arrondi
            total = Math.round(total * 100) / 100;
            
            results.push({
                transporteur: transporteur.toUpperCase(),
                prix: total,
                details: generateDetails(params, tariff, zoneMultiplier, serviceMultiplier)
            });
        });
        
        // Tri par prix croissant
        return results.sort((a, b) => a.prix - b.prix);
    }
    
    function generateDetails(params, tariff, zoneMultiplier, serviceMultiplier) {
        const details = [];
        
        details.push(`Base: ${tariff.base}€`);
        details.push(`Poids (${params.poids}kg): ${(params.poids * tariff.perKg).toFixed(2)}€`);
        
        if (params.type === 'palette' && params.palettes > 1) {
            details.push(`Palettes sup. (${params.palettes-1}): ${((params.palettes-1) * 15).toFixed(2)}€`);
        }
        
        if (params.adr) {
            details.push(`ADR: ${tariff.adr}€`);
        }
        
        if (params.enlevement) {
            details.push(`Enlèvement: ${tariff.enlevement}€`);
        }
        
        if (zoneMultiplier !== 1) {
            details.push(`Zone (${params.departement}): x${zoneMultiplier}`);
        }
        
        if (serviceMultiplier !== 1) {
            details.push(`Service (${params.option_sup}): x${serviceMultiplier}`);
        }
        
        return details;
    }
    
    function displayResults(results) {
        let html = '';
        
        if (results.length === 0) {
            html = `
                <div class="calc-empty-state">
                    <div class="icon">❌</div>
                    <p><strong>Aucun résultat</strong></p>
                    <p>Impossible de calculer les tarifs<br>avec ces paramètres.</p>
                </div>
            `;
        } else {
            results.forEach((result, index) => {
                const badge = index === 0 ? ' 🏆' : '';
                const cardClass = index === 0 ? 'calc-result-card best-price' : 'calc-result-card';
                
                html += `
                    <div class="${cardClass}">
                        <div class="calc-result-header">
                            <div class="calc-transporteur">${result.transporteur}${badge}</div>
                            <div class="calc-price">${result.prix.toFixed(2)}€</div>
                        </div>
                        <div class="calc-details">${result.details.join(' • ')}</div>
                    </div>
                `;
            });
            
            // Ajout du temps de calcul
            html += `
                <div style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: var(--calc-gray);">
                    ⚡ Calcul effectué en temps réel
                </div>
            `;
        }
        
        resultsContent.innerHTML = html;
    }
    
    function simulateCalculation(params) {
        // Tarifs de base simulés
        const baseTariffs = {
            xpo: { base: 15.50, perKg: 0.80, adr: 12.00, enlevement: 8.50 },
            heppner: { base: 14.20, perKg: 0.75, adr: 10.50, enlevement: 7.80 },
            kuehne: { base: 16.80, perKg: 0.85, adr: 13.20, enlevement: 9.20 }
        };
        
        // Multiplicateurs par département (zones)
        const zoneMultipliers = {
            '01': 1.1, '02': 1.05, '03': 1.0, '04': 1.15, '05': 1.2,
            '67': 1.0, '68': 1.05, '69': 1.08, '75': 1.3, '77': 1.25
        };
        
        const zoneMultiplier = zoneMultipliers[params.departement] || 1.1;
        
        // Multiplicateurs par type de service
        const serviceMultipliers = {
            standard: 1.0,
            express: 1.4,
            urgence: 1.8
        };
        
        const serviceMultiplier = serviceMultipliers[params.option_sup] || 1.0;
        
        const results = [];
        
        Object.entries(baseTariffs).forEach(([transporteur, tariff]) => {
            let total = tariff.base + (params.poids * tariff.perKg);
            
            // Ajustements
            if (params.type === 'palette') {
                total += (params.palettes - 1) * 15; // Palette supplémentaire
            }
            
            if (params.adr) {
                total += tariff.adr;
            }
            
            if (params.enlevement) {
                total += tariff.enlevement;
            }
            
            // Application des multiplicateurs
            total *= zoneMultiplier;
            total *= serviceMultiplier;
            
            // Arrondi
            total = Math.round(total * 100) / 100;
            
            results.push({
                transporteur: transporteur.toUpperCase(),
                prix: total,
                details: generateDetails(params, tariff, zoneMultiplier, serviceMultiplier)
            });
        });
        
        // Tri par prix croissant
        return results.sort((a, b) => a.prix - b.prix);
    }
    
    function generateDetails(params, tariff, zoneMultiplier, serviceMultiplier) {
        const details = [];
        
        details.push(`Base: ${tariff.base}€`);
        details.push(`Poids (${params.poids}kg): ${(params.poids * tariff.perKg).toFixed(2)}€`);
        
        if (params.type === 'palette' && params.palettes > 1) {
            details.push(`Palettes sup. (${params.palettes-1}): ${((params.palettes-1) * 15).toFixed(2)}€`);
        }
        
        if (params.adr) {
            details.push(`ADR: ${tariff.adr}€`);
        }
        
        if (params.enlevement) {
            details.push(`Enlèvement: ${tariff.enlevement}€`);
        }
        
        if (zoneMultiplier !== 1) {
            details.push(`Zone (${params.departement}): x${zoneMultiplier}`);
        }
        
        if (serviceMultiplier !== 1) {
            details.push(`Service (${params.option_sup}): x${serviceMultiplier}`);
        }
        
        return details;
    }
    
    function displayResults(results) {
        let html = '';
        
        if (results.length === 0) {
            html = `
                <div class="calc-empty-state">
                    <div class="icon">❌</div>
                    <p><strong>Aucun résultat</strong></p>
                    <p>Impossible de calculer les tarifs<br>avec ces paramètres.</p>
                </div>
            `;
        } else {
            results.forEach((result, index) => {
                const badge = index === 0 ? ' 🏆' : '';
                const cardClass = index === 0 ? 'calc-result-card best-price' : 'calc-result-card';
                
                html += `
                    <div class="${cardClass}">
                        <div class="calc-result-header">
                            <div class="calc-transporteur">${result.transporteur}${badge}</div>
                            <div class="calc-price">${result.prix.toFixed(2)}€</div>
                        </div>
                        <div class="calc-details">${result.details.join(' • ')}</div>
                    </div>
                `;
            });
            
            // Ajout du temps de calcul
            html += `
                <div style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: var(--calc-gray);">
                    ⚡ Calcul effectué en temps réel
                </div>
            `;
        }
        
        resultsContent.innerHTML = html;
    }
});
</script>

</body>
</html>
