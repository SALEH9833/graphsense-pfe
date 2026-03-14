/**
 * GraphSense - Module de Visualisation GDS
 * Style: "GraphSense Bold"
 * 
 * Ce script gère l'affichage dynamique du graphe via Vis.js.
 * Il adapte la taille/couleur des sommets selon les scores de centralité
 * et gère l'affichage des flèches selon l'orientation choisie.
 */

let networkInstance = null; // Stocke l'instance actuelle pour pouvoir la détruire proprement

/**
 * @param {string} containerId - ID de la div HTML cible
 * @param {array} nodesData - Liste des sommets [{id: 'A'}, {id: 'B'}...]
 * @param {array} edgesData - Liste des arêtes [{from: 'A', to: 'B'}...]
 * @param {array} centralityData - (Optionnel) Résultats de Neo4j GDS
 * @param {string} graphType - 'directed' ou 'undirected'
 */
function drawGraph(containerId, nodesData, edgesData, centralityData = [], graphType = 'directed') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // 1. CONFIGURATION DES NOEUDS (Gros, gras et lisibles)
    const processedNodes = nodesData.map(node => {
        let size = 35; // Taille par défaut (Robuste)
        let colorBackground = '#1A1A1A'; // Noir Cyber
        let colorBorder = '#66ffcc';     // Vert Menthe
        let fontColor = '#FFFFFF';
        let borderWidth = 3;

        // Si nous avons des données de centralité (Heatmap dynamique)
        if (centralityData && centralityData.length > 0) {
            const nodeResult = centralityData.find(r => r.id === node.id);
            if (nodeResult) {
                // Scaling : On augmente la taille jusqu'à +30px selon l'importance
                size = 35 + (nodeResult.normalized * 30);
                
                // Si le score dépasse 0.5 (Sommet critique)
                if (nodeResult.normalized > 0.5) {
                    colorBackground = '#66ffcc'; // Devient Vert Menthe
                    colorBorder = '#ffffff';     // Bordure Blanche
                    fontColor = '#000000';       // Texte Noir pour le contraste
                    borderWidth = 4;
                }
            }
        }

        return {
            id: node.id,
            label: node.id,
            size: size,
            shape: 'circle',
            color: {
                background: colorBackground,
                border: colorBorder,
                highlight: { background: '#ffffff', border: '#66ffcc' },
                hover: { background: '#333333', border: '#66ffcc' }
            },
            font: { 
                color: fontColor, 
                face: 'Inter', 
                size: 16, 
                weight: 'bold' 
            },
            borderWidth: borderWidth
        };
    });

    // 2. CONFIGURATION DES ARÊTES (Épaisses et gestion des flèches)
    const processedEdges = edgesData.map(edge => ({
        from: edge.from,
        to: edge.to,
        // GESTION DE L'ORIENTATION : flèche seulement si 'directed'
        arrows: graphType === 'directed' ? { to: { enabled: true, scaleFactor: 1.2 } } : '',
        color: { 
            color: '#39564c', 
            opacity: 0.7, 
            highlight: '#66ffcc' 
        },
        width: 3, // Liens épais par défaut
        selectionWidth: 5
    }));

    const data = {
        nodes: new vis.DataSet(processedNodes),
        edges: new vis.DataSet(processedEdges)
    };

    // 3. PARAMÈTRES PHYSIQUES (Optimisés pour les gros sommets)
    const options = {
        physics: {
            enabled: true,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: {
                gravitationalConstant: -250, // Répulsion forte pour éviter le chevauchement
                springLength: 150,           // Distance entre les points
                springConstant: 0.05,
                avoidOverlap: 1              // Protection contre le chevauchement des cercles
            },
            stabilization: {
                enabled: true,
                iterations: 50, // Stabilisation rapide pour un affichage immédiat
                updateInterval: 25
            }
        },
        interaction: {
            hover: true,
            tooltipDelay: 200,
            zoomView: true,
            dragNodes: true
        }
    };

    // 4. GESTION DE L'INSTANCE (Nettoyage pour éviter les bugs visuels)
    if (networkInstance !== null) {
        networkInstance.destroy();
        networkInstance = null;
    }

    // 5. GÉNÉRATION DU GRAPHE
    networkInstance = new vis.Network(container, data, options);
}