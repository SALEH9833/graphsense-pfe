<?php
session_start();

// 1. Sécurité
if(!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['matrix'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../src/templates/header.php';

// Récupération de l'orientation stockée en session
$currentType = $_SESSION['graph_type'] ?? 'directed';
?>

<script>
    const CURRENT_MATRIX = <?php echo json_encode($_SESSION['matrix']); ?>;
    const CURRENT_TYPE = "<?php echo $currentType; ?>";
</script>

<div class="flex flex-col lg:flex-row w-full h-full gap-6">
    
    <!-- GAUCHE : VISUALISATION (Miniature) -->
    <div class="lg:w-2/3 flex flex-col">
        <div class="glass-panel flex-1 rounded-3xl p-1 relative overflow-hidden animate-pulse-border">
            <div class="absolute top-4 left-4 z-10 bg-black/60 backdrop-blur px-3 py-1 rounded border border-primary/20 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-[10px] font-mono text-primary uppercase">GDS Live Heatmap</span>
            </div>
            <div id="graph-container-mini" class="w-full h-full bg-[#080c10] rounded-2xl"></div>
        </div>
    </div>

    <!-- DROITE : CONTRÔLES ET LEADERBOARD -->
    <div class="lg:w-1/3 flex flex-col gap-4">
        
        <!-- Sélection de l'algorithme -->
        <div class="glass-panel p-6 rounded-3xl">
            <h2 class="text-xs font-bold text-white/40 uppercase mb-4 tracking-widest flex justify-between">
                <span>Moteur GDS</span>
                <span class="text-secondary"><?php echo strtoupper($currentType); ?></span>
            </h2>
            <div class="flex gap-2">
                <select id="algo-select" class="flex-1 bg-cyber-dark border border-white/10 rounded-xl text-white px-4 py-3 appearance-none focus:border-primary outline-none font-mono text-sm cursor-pointer">
                    <option value="pagerank">PageRank (Influence)</option>
                    <option value="betweenness">Betweenness (Bridges)</option>
                    <option value="closeness">Closeness Centrality</option>
                    <option value="degree">Degree Centrality</option>
                </select>
                <button id="btn-calculate" class="w-14 bg-primary text-black rounded-xl flex items-center justify-center shadow-lg shadow-primary/20 hover:bg-white transition active:scale-95">
                    <span class="material-symbols-rounded text-3xl" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                </button>
            </div>
        </div>

        <!-- Résultats -->
        <div class="glass-panel flex-1 rounded-3xl flex flex-col overflow-hidden">
            <div class="p-4 border-b border-white/5 bg-white/5 flex justify-between items-center text-[10px] font-mono font-bold tracking-widest text-white/40">
                <span>CLASSEMENT DES NOEUDS</span>
                <span id="node-count">0 NODES</span>
            </div>
            <div id="results-body" class="flex-1 overflow-y-auto p-2 custom-scrollbar">
                <!-- Les résultats s'afficheront ici -->
            </div>
        </div>

        <!-- Navigation retour -->
        <div class="flex justify-between px-2">
            <a href="visualize.php" class="text-[10px] font-bold text-white/30 hover:text-white transition uppercase flex items-center gap-1">
                <span class="material-symbols-rounded text-sm">arrow_back</span> Retour
            </a>
            <a href="index.php" class="text-[10px] font-bold text-primary/60 hover:text-primary transition uppercase flex items-center gap-1">
                Nouveau Graphe <span class="material-symbols-rounded text-sm">add_circle</span>
            </a>
        </div>
    </div>
</div>

<script src="assets/js/graph-visualizer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('btn-calculate');
        const container = document.getElementById('results-body');

        async function runAnalytics() {
            container.innerHTML = '<div class="h-full flex items-center justify-center font-mono text-xs text-primary animate-pulse tracking-widest">CALCUL GDS EN COURS...</div>';
            
            try {
                const res = await fetch('api/analyze.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        matrix: CURRENT_MATRIX, 
                        algo: document.getElementById('algo-select').value,
                        graph_type: CURRENT_TYPE // On envoie bien l'orientation
                    })
                });
                const data = await res.json();
                
                if(data.status === 'success') {
                    // MISE À JOUR DU GRAPHE : On passe bien CURRENT_TYPE pour masquer les flèches
                    drawGraph('graph-container-mini', data.nodes, data.edges, data.centrality, CURRENT_TYPE);
                    
                    // MISE À JOUR DU LEADERBOARD
                    container.innerHTML = '';
                    document.getElementById('node-count').innerText = `${data.centrality.length} NODES`;
                    const maxScore = Math.max(...data.centrality.map(n => parseFloat(n.score))) || 1;

                    data.centrality.forEach((r, i) => {
                        const pct = (parseFloat(r.score) / maxScore) * 100;
                        container.innerHTML += `
                        <div class="flex items-center gap-4 p-3 border-b border-white/5 hover:bg-white/5 transition rounded-lg">
                            <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center font-bold text-xs text-primary border border-white/10">${r.id}</div>
                            <div class="flex-1">
                                <div class="flex justify-between text-[9px] font-mono mb-1 text-white/50"><span>#${i+1} SCORE</span><span>${r.score}</span></div>
                                <div class="h-1 bg-black rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-primary to-secondary" style="width:${pct}%"></div></div>
                            </div>
                        </div>`;
                    });
                }
            } catch(e) { 
                container.innerHTML = '<div class="p-10 text-red-500 text-center font-mono">ERREUR SERVEUR</div>';
            }
        }

        btn.onclick = runAnalytics;
        runAnalytics(); // Lancement automatique
    });
</script>

<?php require_once __DIR__ . '/../src/templates/footer.php'; ?>