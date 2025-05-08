# Calculateur de frais de port Guldagil

Ce calculateur compare automatiquement les tarifs HEPPNER et XPO en fonction :
- du **département de destination**
- du **poids total (en kg)**
- et du **caractère ADR** du produit (matière dangereuse ou non)

🧮 Il repose sur un fichier Excel métier transformé en JSON.  
🌐 Il peut être utilisé :
- **localement**
- ou **intégré à WordPress** via `<iframe>` dans une page dédiée.

---

## 📁 Fichiers inclus

| Fichier                   | Description                            |
|---------------------------|----------------------------------------|
| `index.html`              | Interface utilisateur du calculateur   |
| `script.js`               | Logique de calcul                      |
| `tarifs_guldagil.json`    | Données de tarification extraites d’Excel |
| `.github/workflows/deploy.yml` | Déploiement automatique via GitHub Actions |
| `.gitignore`              | Exclusion des fichiers sensibles       |
| `README.md`               | Ce fichier                             |

---

## 🚀 Déploiement automatique (CI/CD)

Le dépôt GitHub est connecté à un serveur FTP chez **o2switch**, avec deux environnements de déploiement :

| Environnement | Branche Git | URL                            |
|---------------|-------------|---------------------------------|
| Développement | `develop`   | [dev.gul.runser.ovh](https://dev.gul.runser.ovh) |
| Production    | `main`      | [port.gul.runser.ovh](https://port.gul.runser.ovh) |

Chaque `push` sur l'une de ces branches déclenche un déploiement FTP automatisé.

---

## 🔐 Sécurité et logs

- Les identifiants FTP sont stockés en tant que **secrets GitHub** (jamais visibles dans le code).
- Un fichier `deploy-log.txt` est généré à chaque déploiement (localement uniquement).
- Des e-mails de notification sont envoyés après chaque succès ou échec via SMTP.

---

## 🧪 Recommandations d’usage

- Utiliser la branche `develop` pour tous les tests.
- Merger dans `main` uniquement après validation complète.
- Ne jamais versionner :
  - `.env`, `deploy-log.txt`, `ftp-config.json`, ou tout autre fichier local/sensible.

---

## ✉️ Contact

Développé par **Jean-Thomas Runser**  
Responsable Achats & Logistique chez Guldagil  
Apiculteur & créateur du Rucher Ambroise  
📍 Sierentz (Alsace)
