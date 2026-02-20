# 🌐 GraphSense - Neo4j GDS Analytics

**Projet de Fin d'Études (PFE) - 2ème Année Génie Informatique**  
**Spécialité :** Cybersécurité  
**École :** EST Safi

---

## 📝 À propos du projet
GraphSense est une application web d'analyse de réseaux. Elle permet de transformer une matrice de données ou un fichier CSV en un graphe interactif et d'exécuter des algorithmes complexes de la bibliothèque **Neo4j Graph Data Science (GDS)**.

### Pourquoi ce projet ?
Dans le domaine de la cybersécurité, l'analyse de graphes permet de détecter des points critiques dans un réseau, d'identifier des acteurs influents ou de repérer des anomalies de structure.

---

## 🛠️ Technologies utilisées
L'application utilise une architecture hybride :

*   **Interface (Frontend) :** HTML5, Tailwind CSS (Design Pro), Vis.js (Visualisation dynamique).
*   **Serveur (Backend) :** PHP 8 (via XAMPP).
*   **Base de données administrative :** MySQL (Gestion des utilisateurs).
*   **Moteur de calcul :** Python 3 avec le driver Neo4j.
*   **Base de données Graphe :** **Neo4j Desktop + Plugin GDS**.

---

## 🚀 Guide d'installation (Pas à pas)

### 1. Pré-requis système
Assure-toi d'avoir installé les logiciels suivants :
*   [XAMPP](https://www.apachefriends.org/) (Apache & MySQL).
*   [Python 3.x](https://www.python.org/).
*   [Neo4j Desktop](https://neo4j.com/download/).

### 2. Téléchargement du projet
Ouvre ton terminal dans le dossier `htdocs` de XAMPP et tape :
```bash
git clone https://github.com/SALEH9833/graphsense-pfe.git
cd graphsense-pfe