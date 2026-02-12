# 🎓 EduPlatform - Gestion de Classe & IA

Une plateforme d'apprentissage moderne développée avec **Symfony**, inspirée de Google Classroom, intégrant l'intelligence artificielle pour la génération de QCM.

## 🚀 Fonctionnalités

### 👨‍🏫 Espace Enseignant
* **Gestion de cours :** Déposez vos supports pédagogiques en quelques clics.
* **Génération de QCM par IA :** Créez automatiquement des questionnaires à partir de vos contenus grâce aux APIs **Mistral AI** et **Groq**.
* **Suivi :** Visualisez les notes et la progression des étudiants.

### 👨‍🎓 Espace Étudiant
* **Consultation :** Accédez aux cours mis en ligne par vos professeurs.
* **Évaluation :** Passez les QCM en ligne.
* **Résultats :** Recevez vos notes instantanément après validation.

---

## 🛠️ Prérequis

Avant de commencer, assurez-vous d'avoir installé :
* **PHP 8.2** ou supérieur
* **Composer**
* **Symfony CLI**
* **Docker** (ou un serveur MySQL local)
* **NVM** (Node Version Manager)

---

# ⚙️ Installation & Configuration

### 1. Cloner le projet
```bash
git clone <votre-repo-url>
cd <nom-du-projet>
```

## 2. Configuration de l'environnement (.env)

Copiez le fichier .env en .env.local et configurez vos accès :

### Connexion à la base de données
```bash
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0.32&charset=utf8mb4"
```

### Clés API pour l'IA
```bash
MISTRAL_API_KEY=votre_cle_mistral
GROQ_API_KEY=votre_cle_groq
```

## 3. Installation des dépendances PHP & JS
```bash
# PHP
composer install
```
```bash
# JavaScript (Node v20 recommandé)
nvm use 20
npm install
npm run build
```
## 4. Base de données & Données de test

Exécutez les commandes suivantes pour préparer votre base de données et charger les comptes par défaut (Profs/Élèves) :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Chargement des données initiales (Obligatoire pour les comptes de test)
php bin/console doctrine:fixtures:load
```

# ⚠️ Gestion des documents
**Suite à la fixtures, il faut créer un document dans le /public/assets/document/ nommé "cours_demo.pdf".** 

Les autres documents se téléchargeront automatiquement dans les dossiers suivants :

    Documents (PDF, cours) : /public/assets/document/

    Vidéos : /public/assets/video/

# Démarrage

Pour lancer le serveur symfony localement :
```bash
symfony serve -d
```
L'application sera disponible sur http://127.0.0.1:8000.

L'API du projet est accessible via l'URL suivante : 👉 http://127.0.0.1:8000/api

Dans un autre **terminal** Pour lancer le serveur react localement :
```bash
cd .\edulearn-frontend\
npm run dev
```
L'application react sera disponible sur http://localhost:5173/student/



