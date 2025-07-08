<?php
/**
 * Titre: Header standalone pour module EPI
 * Chemin: /features/epi/partials/standalone_header.php
 * Version: 0.5 beta + build auto
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="Guldagil">
    
    <title><?= htmlspecialchars($page_title) ?> - Portail Guldagil</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/public/assets/img/favicon.png">
    
    <!-- CSS du portail -->
    <link rel="stylesheet" href="/public/assets/css/portal.css?v=<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : '1' ?>">
    
    <!-- CSS du module EPI -->
    <link rel="stylesheet" href="/features/epi/assets/css/epi.css?v=<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : '1' ?>">
    
    <!-- CSS spécifique dashboard -->
    <style>
        /* Variables EPI */
        :root {
            --epi-primary: #6B46C1;
            --epi-secondary: #8B5CF6;
            --epi-accent: #A78BFA;
            --epi-success: #10B981;
            --epi-warning: #F59E0B;
            --epi-danger: #EF4444;
            --epi-info: #3B82F6;
            --epi-gray: #6B7280;
            --epi-light-gray: #F3F4F6;
            --shadow-light: 0 4px 6px rgba(107, 70, 193, 0.1);
            --shadow-medium: 0 8px 25px rgba(107, 70, 193, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        /* Layout principal */
        .epi-main {
            background: #f8fafc;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header du module */
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--epi-primary) 0%, var(--epi-secondary) 100%);
            border-radius: var(--border-radius);
            color: white;
            box-shadow: var(--shadow-medium);
        }

        .module-title-section .module-title {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .module-icon {
            font-size: 3rem;
        }

        .module-subtitle {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .module-actions {
            display: flex;
            gap: 1rem;
        }

        /* Messages flash */
        .flash-message {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .flash-success {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }

        .flash-error {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        .flash-info {
            background: #DBEAFE;
            border: 1px solid #BFDBFE;
            color: #1E40AF;
        }

        .flash-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            margin-left: auto;
            opacity: 0.7;
        }

        /* Métriques */
        .metrics-section {
            margin-bottom: 3rem;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .metric-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(107, 70, 193, 0.1);
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--epi-primary);
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .metric-primary::before { background: var(--epi-primary); }
        .metric-danger::before { background: var(--epi-danger); }
        .metric-warning::before { background: var(--epi-warning); }
        .metric-success::before { background: var(--epi-success); }

        .metric-content {
            flex: 1;
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--epi-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: var(--epi-gray);
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .metric-percentage,
        .metric-trend {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--epi-success);
        }

        .metric-icon {
            font-size: 3rem;
            opacity: 0.7;
        }

        /* Layout dashboard */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .main-column {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Cartes dashboard */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(107, 70, 193, 0.1);
            overflow: hidden;
            transition: var(--transition);
        }

        .dashboard-card:hover {
            box-shadow: var(--shadow-medium);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--epi-light-gray);
        }

        .card-title {
            margin: 0;
