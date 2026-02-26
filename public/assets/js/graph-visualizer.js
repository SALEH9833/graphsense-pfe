/**
 * Style "GraphSense Bold" - Sommets imposants et liens épais
 */
let networkInstance = null;

function drawGraph(containerId, nodesData, edgesData, centralityData = []) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // 1. Configuration des NOEUDS (Gros et lisibles)
    const processedNodes = nodesData.map(node => {
        let size = 35; // Taille de base beaucoup plus grande (avant c'était 25)
        let colorBackground = '#1A1A1A'; 
        let colorBorder = '#66ffcc';     
        let fontColor = '#FFFFFF';
        let borderWidth = 3; // Bordure plus épaisse

        if (centralityData.length > 0) {
            const nodeResult = centralityData.find(r => r.id === node.id);
            if (nodeResult) {
                // Taille augmentée massivement pour les noeuds importants (jusqu'à 65)
                size = 35 + (nodeResult.normalized * 30);
                
                if (nodeResult.normalized > 0.5) {
                    colorBackground = '#66ffcc'; // Vert Menthe
                    colorBorder = '#ffffff';     // Bordure blanche pour faire ressortir
                    fontColor = '#000000';      // Texte noir pour contraste
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
                highlight: { background: '#ffffff', border: '#66ffcc' }
            },
            font: { 
                color: fontColor, 
                face: 'Inter', 
                size: 16, // Police plus grande
                weight: 'bold' // Texte en gras
            },
            borderWidth: borderWidth
        };
    });

    // 2. Configuration des ARÊTES (Épaisses et marquées)
    const processedEdges = edgesData.map(edge => ({
        from: edge.from,
        to: edge.to,
        arrows: {
            to: { enabled: true, scaleFactor: 1.2 } // Flèche plus grosse
        },
        color: { 
            color: '#39564c', 
            opacity: 0.7, // Plus opaque pour être bien visible
            highlight: '#66ffcc' 
        },
        width: 3, // ÉPAISSEUR augmentée (avant c'était 1)
        selectionWidth: 5
    }));

    const data = {
        nodes: new vis.DataSet(processedNodes),
        edges: new vis.DataSet(processedEdges)
    };

    // 3. PHYSIQUE (Adaptée aux gros noeuds)
    const options = {
        physics: {
            enabled: true,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: {
                gravitationalConstant: -250, // Répulsion plus forte pour écarter les gros cercles
                springLength: 150,           // Liens plus longs pour aérer
                springConstant: 0.05,
                avoidOverlap: 1              // PRIORITÉ : ne pas se chevaucher
            },
            stabilization: {
                enabled: true,
                iterations: 100
            }
        },
        interaction: {
            hover: true,
            tooltipDelay: 200,
            zoomView: true
        }
    };

    if (networkInstance !== null) {
        networkInstance.destroy();
    }
    networkInstance = new vis.Network(container, data, options);
}