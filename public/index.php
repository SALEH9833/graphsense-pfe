<?php
session_start();

// 1. CHARGEMENT DES CLASSES (Avant tout affichage)
require_once __DIR__ . '/../vendor/autoload.php';
use Src\Graph\CsvParser;

// 2. TRAITEMENT DU FORMULAIRE (Avant tout affichage HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $graphData = CsvParser::parse($_FILES['csv_file']['tmp_name']);
            $_SESSION['matrix'] = CsvParser::toMatrixJSON($graphData);
            // La redirection fonctionnera maintenant car aucun HTML n'a été envoyé
            header('Location: visualize.php');
            exit;
        } catch (Exception $e) {
            $error = "Erreur CSV : " . $e->getMessage();
        }
    }
}

// 3. MAINTENANT SEULEMENT, ON AFFICHE LE HTML
require_once __DIR__ . '/../src/templates/header.php';
?>

<div class="flex flex-col md:flex-row w-full max-w-6xl gap-8 p-4 h-[85vh]">
    
    <!-- COLONNE GAUCHE : SAISIE -->
    <div class="flex-[2] glass-panel p-6 rounded-2xl flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="w-8 h-8 rounded bg-primary/20 text-primary flex items-center justify-center text-sm font-mono">01</span> 
                Définition de la Matrice
            </h2>
        </div>

        <div class="flex items-end gap-4 mb-6 border-b border-white/10 pb-6">
            <div class="flex-1">
                <label class="block text-xs font-bold text-white/50 mb-2 uppercase tracking-widest">Sommets</label>
                <input type="number" id="node-count" min="2" max="20" value="4" class="w-full bg-cyber-dark border border-white/10 text-white rounded-lg p-3 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition font-mono">
            </div>
            <button type="button" id="btn-generate-grid" class="px-6 py-3 bg-white/5 text-white font-bold rounded-lg hover:bg-white/10 transition border border-white/10 uppercase text-xs tracking-wider">
                Générer
            </button>
        </div>

        <div class="flex-1 overflow-auto relative bg-cyber-black rounded-lg border border-white/10 p-4 flex items-center justify-center">
            <div id="matrix-grid-container" class="grid gap-1">
                <p class="text-white/30 text-sm italic font-mono">Initialisation...</p>
            </div>
        </div>

        <form action="visualize.php" method="POST" id="main-form" class="mt-4">
            <textarea name="matrix" id="hidden-matrix" class="hidden"></textarea>
            
            <button type="submit" class="w-full py-4 bg-primary hover:bg-white text-black font-bold rounded-lg transition-all shadow-[0_0_20px_rgba(0,240,255,0.3)] hover:shadow-[0_0_30px_rgba(0,240,255,0.5)] flex justify-center items-center gap-3 text-lg group">
                Visualiser le Graphe 
                <!-- CORRECTION ICÔNE ICI : material-symbols-rounded -->
                <span class="material-symbols-rounded group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
        </form>
    </div>

    <!-- COLONNE DROITE : UPLOAD -->
    <div class="flex-1 flex flex-col gap-6">
        <div class="glass-panel p-6 rounded-2xl">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <span class="w-8 h-8 rounded bg-secondary/20 text-secondary flex items-center justify-center text-sm font-mono">02</span> 
                Import CSV
            </h2>
            <form action="" method="POST" enctype="multipart/form-data" class="h-32 border-2 border-dashed border-white/10 rounded-xl flex flex-col items-center justify-center hover:bg-white/5 hover:border-primary/50 transition relative cursor-pointer group">
                <span class="material-symbols-rounded text-3xl text-white/20 group-hover:text-primary transition mb-2">upload_file</span>
                <p class="text-white/40 text-xs font-mono group-hover:text-white transition">Glisser-déposer .CSV</p>
                <input type="file" name="csv_file" class="absolute inset-0 opacity-0 cursor-pointer" accept=".csv" required onchange="this.form.submit()">
            </form>
            <p class="text-[10px] text-white/30 mt-3 text-center font-mono">Format: Source,Target</p>
            <?php if(isset($error)) echo "<p class='text-accent text-xs mt-2 text-center font-bold'>$error</p>"; ?>
        </div>

        <div class="glass-panel p-6 rounded-2xl flex-1 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 opacity-5">
                <span class="material-symbols-rounded text-[100px]">help</span>
            </div>
            <h3 class="font-bold text-white mb-4 flex items-center gap-2 text-sm uppercase tracking-wider">
                <span class="material-symbols-rounded text-primary">info</span> Guide Rapide
            </h3>
            <ul class="text-xs text-white/60 space-y-3 pl-4 font-mono leading-relaxed">
                <li class="list-disc marker:text-primary">Définissez le nombre de nœuds.</li>
                <li class="list-disc marker:text-primary">Cliquez sur les cases : <br><span class="text-primary">Vert (1)</span> = Connecté<br><span class="text-white/30">Gris (0)</span> = Non connecté.</li>
                <li class="list-disc marker:text-primary">Ou importez un fichier CSV nettoyé (Source, Cible).</li>
            </ul>
        </div>
    </div>
</div>

<!-- SCRIPT IDENTIQUE MAIS DANS LA PAGE -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const nodeCountInput = document.getElementById('node-count');
    const btnGenerate = document.getElementById('btn-generate-grid');
    const gridContainer = document.getElementById('matrix-grid-container');
    const hiddenMatrix = document.getElementById('hidden-matrix');

    btnGenerate.addEventListener('click', () => {
        const count = parseInt(nodeCountInput.value);
        if(count < 2 || count > 20) { alert("Entre 2 et 20 sommets SVP"); return; }
        createGrid(count);
    });

    function createGrid(n) {
        gridContainer.innerHTML = '';
        let cellSize = n > 12 ? '2rem' : (n > 8 ? '2.5rem' : '3.5rem');
        let textSize = n > 12 ? 'text-xs' : 'text-base';

        gridContainer.className = "grid gap-1 place-content-center"; 
        gridContainer.style.gridTemplateColumns = `auto repeat(${n}, ${cellSize})`;
        
        gridContainer.appendChild(createLabel('')); 
        for(let i=0; i<n; i++) gridContainer.appendChild(createLabel(String.fromCharCode(65 + i), textSize));

        for(let i=0; i<n; i++) {
            gridContainer.appendChild(createLabel(String.fromCharCode(65 + i), textSize));
            for(let j=0; j<n; j++) {
                const input = document.createElement('input');
                input.type = 'text'; input.readOnly = true; input.value = 0;
                input.className = `w-full h-full bg-cyber-dark border border-white/10 text-center text-white font-mono rounded focus:border-primary outline-none cursor-pointer hover:bg-white/10 transition select-none ${textSize}`;
                
                input.addEventListener('click', () => {
                    input.value = input.value === '1' ? '0' : '1';
                    if(input.value === '1') {
                        input.classList.add('bg-primary/20', 'text-primary', 'font-bold', 'border-primary');
                        input.classList.remove('bg-cyber-dark', 'text-white', 'border-white/10');
                    } else {
                        input.classList.remove('bg-primary/20', 'text-primary', 'font-bold', 'border-primary');
                        input.classList.add('bg-cyber-dark', 'text-white', 'border-white/10');
                    }
                    updateHiddenJSON(n);
                });
                gridContainer.appendChild(input);
            }
        }
        updateHiddenJSON(n);
    }

    function createLabel(text, ts='text-sm') {
        const d = document.createElement('div'); d.className = `flex items-center justify-center font-bold text-primary/50 ${ts}`; d.innerText = text; return d;
    }

    function updateHiddenJSON(n) {
        const inputs = gridContainer.querySelectorAll('input');
        let m = [], r = [];
        inputs.forEach((inp, idx) => {
            r.push(parseInt(inp.value)||0);
            if((idx+1)%n===0) { m.push(r); r=[]; }
        });
        if(hiddenMatrix) hiddenMatrix.value = JSON.stringify(m);
    }
    createGrid(4);
});
</script>
</body>
</html>