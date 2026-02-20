<?php
session_start();

// Si aucune matrice en session (ni par POST, ni déjà stockée), retour accueil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['matrix'])) {
    $_SESSION['matrix'] = $_POST['matrix'];
}
if (!isset($_SESSION['matrix'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../src/templates/header.php';
?>

<!-- Passer la matrice PHP vers JS -->
<script>const CURRENT_MATRIX = <?php echo json_encode($_SESSION['matrix']); ?>;</script>

<div class="w-full h-full flex flex-col relative bg-[#111]">
    
    <!-- Badge Flottant -->
    <div class="absolute top-6 left-1/2 -translate-x-1/2 z-10 pointer-events-none">
        <div class="bg-surface/90 backdrop-blur px-6 py-2 rounded-full border border-white/10 flex items-center gap-3 shadow-xl">
            <span class="bg-primary/20 text-primary w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs">2</span>
            <span class="font-bold text-white text-sm">Exploration du Graphe</span>
        </div>
    </div>

    <!-- Zone de dessin Vis.js -->
    <div id="graph-container" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

    <!-- Barre d'actions en bas -->
    <div class="absolute bottom-8 right-8 flex gap-4 z-10">
        <a href="index.php" class="px-5 py-3 bg-surface border border-white/10 text-white font-bold rounded-lg hover:bg-white/5 transition text-sm">
            Modifier
        </a>

        <a href="metrics.php" class="px-6 py-3 bg-secondary text-white font-bold rounded-lg hover:brightness-110 shadow-lg shadow-secondary/20 flex items-center gap-2 text-sm transition transform hover:-translate-y-1">
            Lancer les Algorithmes 
            <span class="material-symbols-rounded text-lg">arrow_forward</span>
        </a>
    </div>
</div>

<script src="assets/js/graph-visualizer.js"></script>
<script>
    // Initialisation automatique
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const response = await fetch('api/analyze.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ matrix: CURRENT_MATRIX, algo: 'degree' })
            });
            const data = await response.json();
            
            if(data.status === 'success') {
                drawGraph('graph-container', data.nodes, data.edges, []);
            } else {
                alert("Erreur de lecture du graphe : " + data.message);
            }
        } catch(e) { console.error(e); }
    });
</script>

<?php require_once __DIR__ . '/../src/templates/footer.php'; ?>