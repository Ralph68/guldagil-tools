# Guldagil Port Calculator

**Projet**: Calculateur et comparateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel

**Version**: 1.0 (Debug)

## 📜 Description

Une application web PHP simple et modulaire permettant de :

* **Saisir** des critères d’envoi (département, poids, type d’envoi, ADR, options, nombre de palettes, enlèvement)
* **Comparer** les tarifs de plusieurs transporteurs issus d’une base de données MySQL (`gul_taxes_transporteurs`)
* **Mettre en œuvre** des options supplémentaires (prise de RDV, livraison date fixe, premium avant 13h/18h, enlèvement, frais par palette)
* **Afficher** dynamiquement le meilleur tarif, la comparaison complète, et des détails techniques pour le débogage
* **Gérer** ces options via une interface d’administration CRUD

L’interface est **responsive** (mobile et desktop) et dispose d’un **mode debug** pour inspecter les calculs.

---

## 📁 Arborescence du projet

```
/
├── README.md                   # Documentation du projet
├── config.php                  # Connexion à la BDD (PDO)
├── lib/
│   └── Transport.php           # Classe de calcul (avec debug)
├── public/
│   ├── index.php               # Interface principale
│   ├── assets/
│   │   ├── css/style.css       # Styles
│   │   ├── js/script.js        # Logiciel JS (focus, auto-submit debug)
│   │   └── img/logo_guldagil.png
│   └── admin/
│       ├── rates.php           # Interface tarifs (existant)
│       ├── admin-options.php   # Liste des options supplémentaires
│       └── admin-options-edit.php # Édition/ajout d’options
└── .env                        # Variables d'environnement (BDD, FTP…)
```

---

## ⚙️ Prérequis

* PHP **≥ 7.4**
* Extension **PDO MySQL**
* Serveur web (Apache/Nginx) configuré pour pointer vers `public/`
* Base de données MySQL avec les tables :

  * **`gul_taxes_transporteurs`** (frais de port)
  * **`gul_options_supplementaires`** (options additionnelles)

---

## 🔧 Installation

1. **Cloner** ce dépôt puis positionner la racine sur votre serveur :

   ```bash
   ```

git clone [https://github.com/votre-repo/gul-port-calculator.git](https://github.com/votre-repo/gul-port-calculator.git)
deployer/gul-port-calculator/public /var/www/port.gul.runser.ovh

````

2. **Configurer** la connexion MySQL dans `config.php` ou via `.env` :
```php
// config.php
$db = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
    DB_USER,
    DB_PASS
);
````

3. **Importer** les tables SQL :

   ```sql
   -- Tarifs
   SOURCE taxes_transporteurs.sql;

   -- Options supplémentaires
   CREATE TABLE gul_options_supplementaires (
     id INT AUTO_INCREMENT PRIMARY KEY,
     transporteur VARCHAR(50) NOT NULL,
     code_option VARCHAR(50) NOT NULL,
     libelle VARCHAR(255) NOT NULL,
     montant DECIMAL(8,2) NOT NULL DEFAULT 0.00,
     unite ENUM('forfait','palette') DEFAULT 'forfait',
     actif BOOLEAN DEFAULT TRUE,
     UNIQUE KEY (transporteur, code_option)
   );

   -- Destinataires (clients)
   CREATE TABLE gul_clients (
     id INT AUTO_INCREMENT PRIMARY KEY,
     nom VARCHAR(255) NOT NULL,
     adresse_complete TEXT DEFAULT NULL,
     code_postal VARCHAR(10) NOT NULL,
     ville VARCHAR(100) NOT NULL,
     pays VARCHAR(50) DEFAULT 'France',
     telephone VARCHAR(50) DEFAULT NULL,
     email VARCHAR(100) DEFAULT NULL,
     actif TINYINT(1) DEFAULT 1,
     cree_par VARCHAR(50) DEFAULT 'system',
     date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );

   -- Mise à jour des déclarations ADR
   ALTER TABLE gul_adr_declarations
     ADD COLUMN destinataire_id INT DEFAULT NULL,
     ADD CONSTRAINT fk_declaration_destinataire FOREIGN KEY (destinataire_id)
       REFERENCES gul_clients(id);
   ```

4. **Insérer** quelques données de test dans `gul_options_supplementaires` :

   ```sql
   INSERT INTO gul_options_supplementaires (transporteur, code_option, libelle, montant, unite) VALUES
     ('xpo','rdv','Prise de RDV',15.00,'forfait'),
     ('heppner','datefixe','Date à prendre',18.00,'forfait'),
     ('kn','premium13','Premium avant 13h',22.00,'forfait'),
     ('kn','palette','Frais par palette EUR',8.00,'palette');
   ```

5. **Attribuer** au dossier `public/` un hôte virtuel (VirtualHost) et assurez-vous que `index.php` est pointé par défaut.

---

## 🚀 Utilisation

1. **Accéder** à l’URL de l’application (ex. `https://port.gul.runser.ovh/index.php`).
2. **Remplir** les champs :

   * **Département** (2 chiffres)
   * **Poids** (kg)
   * **Type d’envoi** (Colis/Palette)
   * **ADR** (Oui/Non)
   * **Options supplémentaires** (Aucune, RDV, Date fixe, Premium 13h/18h)
   * **Enlèvement** (case à cocher)
   * **Nombre de palettes EUR**
3. **Cliquer** sur **Calculer** pour afficher :

   * **Meilleur tarif**
   * **Tableau complet** de comparaison
   * **Détails techniques** (mode debug)

---

## 🛠️ Administration

* **Tarifs** : `public/admin/rates.php` (lecture / suppression)
* **Options** :

  * Liste : `public/admin/admin-options.php`
  * Ajout / édition : `public/admin/admin-options-edit.php`

---

## 🐞 Debug & Logs

* Par défaut, le calcul affiche un **dump** de `$_POST`, de `$results` et de `Transport::$debug`.
* Pour passer en production, retirez les `var_dump` et la section `<pre> … </pre>`.

---

## 📅 Feuille de route

1. **Intégrer** le calcul des options supplémentaires dans `Transport.php`.
2. **Nettoyer** le mode debug et valider la version finale.
3. **Améliorer** l’UI (CSS & animations).
4. **Ajouter** des tests unitaires pour `Transport`.
5. **Mettre** en place la CI/CD (déploiement sur o2switch).

---

*Pour toute question ou suggestion, contacter l’équipe technique Guldagil.*
