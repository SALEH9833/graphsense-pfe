document.addEventListener('DOMContentLoaded', () => {
    // Récupération des éléments par leur ID
    const btnVisualize = document.getElementById('btn-visualize');
    const btnAnalyze = document.getElementById('btn-analyze');
    const matrixInput = document.getElementById('matrix-input');
    const algoSelect = document.getElementById('algo-select');
    const resultsBody = document.getElementById('results-body');

    // Fonction commune pour lancer l'analyse
    async function runProcess() {
        const matrix = matrixInput.value;
        const algo = algoSelect.value; // 'degree' ou 'closeness'

        // Feedback visuel
        resultsBody.innerHTML = '<tr><td colspan="2" class="p-3 text-center">Calcul en cours...</td></tr>';

        try {
            const response = await fetch('api/analyze.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    matrix: matrix,
                    algo: algo 
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                // 1. Dessiner le graphe (fonction de graph-visualizer.js)
                // On passe 'graph-container' qui est l'ID de la DIV centrale
                drawGraph('graph-container', data.nodes, data.edges, data.centrality);

                // 2. Mettre à jour le tableau
                updateTable(data.centrality);
            } else {
                alert("Erreur: " + data.message);
                resultsBody.innerHTML = '<tr><td colspan="2" class="p-3 text-center text-red-400">Erreur</td></tr>';
            }

        } catch (error) {
            console.error(error);
            alert("Erreur de connexion serveur.");
        }
    }

    function updateTable(results) {
        resultsBody.innerHTML = '';
        
        if(results.length === 0) {
            resultsBody.innerHTML = '<tr><td colspan="2" class="p-3 text-center">Aucun résultat</td></tr>';
            return;
        }

        results.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = "border-b border-white/5 hover:bg-white/5";
            tr.innerHTML = `
                <td class="p-3 font-bold text-primary">${row.id}</td>
                <td class="p-3 font-mono">${row.score}</td>
            `;
            resultsBody.appendChild(tr);
        });
    }

    // Les deux boutons font la même chose : rafraîchir le graphe et les calculs
    if(btnVisualize) btnVisualize.addEventListener('click', runProcess);
    if(btnAnalyze) btnAnalyze.addEventListener('click', runProcess);
});