<?php
session_start();

// 1. Sécurité : Redirection si non connecté
if(!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

// 2. Récupération et stockage des données du formulaire (Page 1 -> Page 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['matrix'])) {
    $_SESSION['matrix'] = $_POST['matrix'];
    $_SESSION['graph_type'] = $_POST['graph_type'] ?? 'directed';
}

// 3. Vérification de la présence des données
if (!isset($_SESSION['matrix'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../src/templates/header.php';

// Variables pour le JavaScript
$currentMatrix = $_SESSION['matrix'];
$currentType = $_SESSION['graph_type'] ?? 'directed';
?>

<script>
    // Passage des variables PHP vers le monde JavaScript
    const CURRENT_MATRIX = <?php echo json_encode($currentMatrix); ?>;
    const CURRENT_TYPE = "<?php echo $currentType; ?>";
</script>

<div class="w-full h-full flex flex-col relative bg-[#080808]">
    <!-- Badge de statut flottant -->
    <div class="absolute top-6 left-1/2 -translate-x-1/2 z-10 pointer-events-none">
        <div class="glass-panel backdrop-blur px-6 py-2 rounded-full border border-white/10 flex items-center gap-3 shadow-xl">
            <span class="bg-primary/20 text-primary w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs font-mono">02</span>
            <span class="font-bold text-white text-xs uppercase tracking-widest">
                Exploration : <?php echo ($currentType == 'directed' ? 'Mode Orienté' : 'Mode Non-orienté'); ?>
            </span>
        </div>
    </div>

    <!-- Zone de rendu du Graphe -->
    <div id="graph-container" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

    <!-- Barre d'actions -->
    <div class="absolute bottom-8 right-8 flex gap-4 z-10">
        <a href="index.php" class="px-5 py-3 glass-panel text-white font-bold rounded-xl text-sm hover:bg-white/5 transition">
            Modifier la structure
        </a>
        <a href="metrics.php" class="px-6 py-3 bg-secondary text-white font-bold rounded-xl flex items-center gap-2 text-sm shadow-lg shadow-secondary/20 hover:brightness-110 transition transform hover:-translate-y-1">
            Lancer l'Analyse GDS <span class="material-symbols-rounded">analytics</span>
        </a>
    </div>
</div>

<script src="assets/js/graph-visualizer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Appel à l'API pour parser la matrice
            const res = await fetch('api/analyze.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    matrix: CURRENT_MATRIX, 
                    algo: 'degree', 
                    graph_type: CURRENT_TYPE 
                })
            });
            const data = await res.json();
            
            if(data.status === 'success') {
                // APPEL CORRIGÉ : On passe CURRENT_TYPE à la fonction de dessin
                drawGraph('graph-container', data.nodes, data.edges, [], CURRENT_TYPE);
            } else {
                console.error("Erreur API:", data.message);
            }
        } catch(e) { console.error("Erreur de connexion:", e); }
    });
</script>

<?php require_once __DIR__ . '/../src/templates/footer.php'; ?>