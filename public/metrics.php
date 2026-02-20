<?php
session_start();
if (!isset($_SESSION['matrix'])) { header('Location: index.php'); exit; }
require_once __DIR__ . '/../src/templates/header.php';
?>
<!-- Transmission de la matrice au JS -->
<script>const CURRENT_MATRIX = <?php echo json_encode($_SESSION['matrix']); ?>;</script>

<div class="flex flex-col lg:flex-row w-full h-full gap-6">
    
    <!-- GAUCHE : VISUALISATION DU GRAPHE (Miniature Pro) -->
    <div class="lg:w-2/3 flex flex-col gap-4">
        <!-- Carte Graphique -->
        <div class="glass-panel flex-1 rounded-2xl p-1 relative overflow-hidden animate-pulse-border group">
            <!-- Label flottant -->
            <div class="absolute top-4 left-4 z-10 bg-black/60 backdrop-blur px-3 py-1 rounded border border-primary/20 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-xs font-mono text-primary uppercase">Live Preview</span>
            </div>
            
            <div id="graph-container-mini" class="w-full h-full bg-[#080c10] rounded-xl cursor-crosshair"></div>
            
            <!-- Overlay décoratif -->
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-cyber-black to-transparent pointer-events-none"></div>
        </div>
    </div>

    <!-- DROITE : PANNEAU DE CONTRÔLE ET RÉSULTATS -->
    <div class="lg:w-1/3 flex flex-col gap-4 h-full">
        
        <!-- Carte de Contrôle -->
        <div class="glass-panel p-6 rounded-2xl flex flex-col gap-4">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded text-primary">settings_suggest</span>
                Algorithme GDS
            </h2>
            
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <select id="algo-select" class="w-full bg-cyber-dark border border-white/10 rounded-lg text-sm text-white px-4 py-3 appearance-none focus:border-primary focus:ring-1 focus:ring-primary outline-none transition font-mono cursor-pointer">
                        <option value="degree">Degree Centrality</option>
                        <option value="closeness">Closeness Centrality</option>
                        <option value="betweenness">Betweenness Centrality</option>
                        <option value="pagerank">PageRank Algorithm</option>
                    </select>
                    <span class="material-symbols-rounded absolute right-3 top-3 text-white/30 pointer-events-none">expand_more</span>
                </div>
                
                <button id="btn-calculate" class="w-14 bg-primary hover:bg-white text-black rounded-lg flex items-center justify-center transition-all shadow-[0_0_15px_rgba(0,240,255,0.4)] hover:shadow-[0_0_25px_rgba(0,240,255,0.6)]">
                    <!-- Icône pleine (Filled) -->
                    <span class="material-symbols-rounded text-3xl" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                </button>
            </div>
        </div>

        <!-- Carte de Résultats -->
        <div class="glass-panel flex-1 rounded-2xl flex flex-col overflow-hidden relative">
            <div class="p-4 border-b border-white/5 bg-white/5 flex justify-between items-center">
                <h3 class="font-bold text-white text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-rounded text-secondary text-base">leaderboard</span>
                    Classement
                </h3>
                <span class="text-[10px] font-mono text-white/30" id="result-count">0 NOEUDS</span>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2 space-y-1" id="results-body">
                <!-- État Vide -->
                <div class="h-full flex flex-col items-center justify-center text-white/20 gap-2">
                    <span class="material-symbols-rounded text-4xl">analytics</span>
                    <span class="text-xs uppercase tracking-widest">En attente du calcul...</span>
                </div>
            </div>
            
            <!-- Dégradé bas -->
            <div class="absolute bottom-0 w-full h-8 bg-gradient-to-t from-cyber-dark to-transparent pointer-events-none"></div>
        </div>

        <!-- Actions Rapides -->
        <div class="flex justify-between items-center text-xs font-mono px-2">
            <a href="visualize.php" class="text-white/40 hover:text-white transition flex items-center gap-1">
                <span class="material-symbols-rounded text-sm">arrow_back</span> Retour Visu
            </a>
            <a href="index.php" class="text-primary hover:text-white transition flex items-center gap-1">
                <span class="material-symbols-rounded text-sm">add_circle</span> Nouveau
            </a>
        </div>
    </div>
</div>

<script src="assets/js/graph-visualizer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('btn-calculate');
        const select = document.getElementById('algo-select');
        const container = document.getElementById('results-body');
        const countLabel = document.getElementById('result-count');

        async function run() {
            container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center gap-3">
                    <div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-mono text-primary animate-pulse">TRAITEMENT GDS...</span>
                </div>`;
            
            try {
                const res = await fetch('api/analyze.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ matrix: CURRENT_MATRIX, algo: select.value })
                });
                const data = await res.json();
                
                if(data.status === 'success') {
                    drawGraph('graph-container-mini', data.nodes, data.edges, data.centrality);
                    
                    container.innerHTML = '';
                    countLabel.innerText = `${data.centrality.length} NOEUDS`;

                    const maxScore = Math.max(...data.centrality.map(n => parseFloat(n.score)));

                    data.centrality.forEach((r, index) => {
                        const percent = (parseFloat(r.score) / maxScore) * 100;
                        const rankColor = index === 0 ? 'text-primary' : (index === 1 ? 'text-white' : 'text-white/60');
                        const barColor = index === 0 ? 'bg-gradient-to-r from-primary to-secondary' : 'bg-white/10';

                        const item = `
                        <div class="group flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 transition border border-transparent hover:border-white/5">
                            <div class="font-mono text-xs font-bold w-6 text-right text-white/30">#${index + 1}</div>
                            
                            <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-primary/50 transition">
                                <span class="font-bold text-sm ${rankColor}">${r.id}</span>
                            </div>
                            
                            <div class="flex-1 flex flex-col gap-1">
                                <div class="flex justify-between items-end">
                                    <span class="text-[10px] text-white/40 font-mono uppercase">Score</span>
                                    <span class="text-xs font-mono font-bold text-white">${r.score}</span>
                                </div>
                                <div class="w-full h-1.5 bg-black rounded-full overflow-hidden">
                                    <div class="h-full ${barColor} rounded-full" style="width: ${percent}%"></div>
                                </div>
                            </div>
                        </div>`;
                        container.innerHTML += item;
                    });
                }
            } catch(e) { 
                console.error(e);
                container.innerHTML = `<div class="p-4 text-accent text-xs font-mono text-center">ERREUR API</div>`;
            }
        }
        
        btn.addEventListener('click', run);
        run(); 
    });
</script>

<?php require_once __DIR__ . '/../src/templates/footer.php'; ?>