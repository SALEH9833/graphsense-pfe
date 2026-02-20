let networkInstance = null;

function drawGraph(containerId, nodesData, edgesData, centralityData = []) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const processedNodes = nodesData.map(node => {
        let size = 20;
        let colorBackground = '#1A1A1A';
        let colorBorder = '#00F0FF'; 

        if (centralityData.length > 0) {
            const nodeResult = centralityData.find(r => r.id === node.id);
            if (nodeResult) {
                size = 20 + (nodeResult.normalized * 30);
                if (nodeResult.normalized > 0.8) colorBackground = '#7000FF'; // Magenta Top
                else if (nodeResult.normalized > 0.3) colorBackground = '#00F0FF'; // Cyan Mid
            }
        }

        return {
            id: node.id,
            label: node.id,
            size: size,
            color: { background: colorBackground, border: colorBorder, highlight: { background: '#fff', border: '#7000FF' } },
            font: { color: '#E0E0E0', face: 'Outfit', size: 12 }
        };
    });

    const data = {
        nodes: new vis.DataSet(processedNodes),
        edges: new vis.DataSet(edgesData.map(e => ({
            from: e.from, to: e.to, arrows: 'to', color: { color: 'rgba(255,255,255,0.1)' }, width: 1
        })))
    };

    const options = {
        physics: {
            enabled: true,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: { gravitationalConstant: -100, springLength: 100 },
            stabilization: {
                enabled: true,
                iterations: 20, // TRÈS RAPIDE : On n'attend plus 150
                updateInterval: 10
            }
        },
        interaction: { hover: true, tooltipDelay: 200 }
    };

    if (networkInstance !== null) { networkInstance.destroy(); }
    networkInstance = new vis.Network(container, data, options);
}