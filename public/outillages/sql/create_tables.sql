-- Module Outillage - Structure de base de données
-- Tables pour la gestion des outils

-- Table des catégories d'outils
CREATE TABLE IF NOT EXISTS outillage_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    type ENUM('manuel', 'electroportatif') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des profils utilisateurs
CREATE TABLE IF NOT EXISTS outillage_profils (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des agences
CREATE TABLE IF NOT EXISTS outillage_agences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    ville VARCHAR(100),
    code VARCHAR(10) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des modèles d'outils (templates)
CREATE TABLE IF NOT EXISTS outillage_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categorie_id INT,
    designation VARCHAR(200) NOT NULL,
    marque VARCHAR(100),
    modele VARCHAR(100),
    observations TEXT,
    quantite_standard INT DEFAULT 1,
    maintenance_requise BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES outillage_categories(id)
);

-- Table des employés (commune avec EPI)
CREATE TABLE IF NOT EXISTS outillage_employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    profil_id INT,
    agence_id INT,
    date_embauche DATE,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profil_id) REFERENCES outillage_profils(id),
    FOREIGN KEY (agence_id) REFERENCES outillage_agences(id)
);

-- Table des outils individuels
CREATE TABLE IF NOT EXISTS outillage_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT,
    numero_serie VARCHAR(100),
    agence_id INT,
    etat ENUM('neuf', 'bon', 'usage', 'defaillant', 'en_reparation', 'reforme') DEFAULT 'neuf',
    date_mise_service DATE,
    fournisseur VARCHAR(150),
    ref_fournisseur VARCHAR(100),
    prix_achat DECIMAL(10,2),
    date_derniere_maintenance DATE,
    prochaine_maintenance DATE,
    photo_url VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES outillage_templates(id),
    FOREIGN KEY (agence_id) REFERENCES outillage_agences(id)
);

-- Table des attributions
CREATE TABLE IF NOT EXISTS outillage_attributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    item_id INT,
    date_attribution DATE NOT NULL,
    date_retour DATE,
    etat_attribution ENUM('active', 'retournee', 'perdue', 'cassee') DEFAULT 'active',
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES outillage_employees(id),
    FOREIGN KEY (item_id) REFERENCES outillage_items(id)
);

-- Table des demandes
CREATE TABLE IF NOT EXISTS outillage_demandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    template_id INT,
    type_demande ENUM('nouveau', 'remplacement', 'reparation') NOT NULL,
    item_remplace_id INT NULL,
    raison_demande VARCHAR(255),
    statut ENUM('en_attente', 'validee', 'rejetee', 'traitee') DEFAULT 'en_attente',
    validee_par INT NULL,
    date_validation DATE NULL,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES outillage_employees(id),
    FOREIGN KEY (template_id) REFERENCES outillage_templates(id),
    FOREIGN KEY (item_remplace_id) REFERENCES outillage_items(id),
    FOREIGN KEY (validee_par) REFERENCES outillage_employees(id)
);

-- Table des raisons de remplacement
CREATE TABLE IF NOT EXISTS outillage_raisons_remplacement (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des attributions par profil (quels outils pour quel profil)
CREATE TABLE IF NOT EXISTS outillage_profil_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profil_id INT,
    template_id INT,
    quantite INT DEFAULT 1,
    obligatoire BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profil_id) REFERENCES outillage_profils(id),
    FOREIGN KEY (template_id) REFERENCES outillage_templates(id)
);

-- Données initiales

-- Insertion des catégories
INSERT INTO outillage_categories (nom, type, description) VALUES
('Clés', 'manuel', 'Clés de toutes sortes'),
('Tournevis', 'manuel', 'Tournevis isolés et standard'),
('Pinces', 'manuel', 'Pinces diverses'),
('Mesure', 'manuel', 'Outils de mesure et vérification'),
('Perceuses', 'electroportatif', 'Perceuses électriques et pneumatiques'),
('Éclairage', 'electroportatif', 'Éclairage portable'),
('Divers manuel', 'manuel', 'Autres outils manuels'),
('Divers électroportatif', 'electroportatif', 'Autres outils électroportatifs');

-- Insertion des profils
INSERT INTO outillage_profils (nom, description) VALUES
('technicien', 'Technicien de maintenance'),
('monteur', 'Monteur sur site');

-- Insertion des agences (exemple - à adapter)
INSERT INTO outillage_agences (nom, ville, code) VALUES
('Agence Paris', 'Paris', 'PAR'),
('Agence Lyon', 'Lyon', 'LYO'),
('Agence Marseille', 'Marseille', 'MAR'),
('Agence Lille', 'Lille', 'LIL'),
('Agence Toulouse', 'Toulouse', 'TOU'),
('Agence Nantes', 'Nantes', 'NAN'),
('Agence Strasbourg', 'Strasbourg', 'STR');

-- Insertion des raisons de remplacement
INSERT INTO outillage_raisons_remplacement (code, libelle, description) VALUES
('CASSE', 'Cassé', 'Outil cassé ou endommagé'),
('PERTE', 'Perdu', 'Outil perdu sur chantier'),
('USURE', 'Usure', 'Usure normale de l\'outil'),
('ROUILLE', 'Rouille', 'Outil rouillé ou corrodé'),
('VOL', 'Vol', 'Outil volé'),
('OBSOLETE', 'Obsolète', 'Outil obsolète à remplacer'),
('DEFAUT', 'Défaut', 'Défaut de fabrication');