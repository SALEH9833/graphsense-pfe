<?php
session_start();

// 1. Sécurité
if(!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../vendor/autoload.php';
use Src\Graph\CsvParser;

// Initialisation des variables de pré-remplissage
// On regarde d'abord si on vient d'un import, sinon on regarde si on a déjà une session en cours
$preFilledMatrix = $_SESSION['matrix'] ?? 'null';
$preFilledCount = (isset($_SESSION['matrix'])) ? count(json_decode($_SESSION['matrix'], true)) : 4;
$preFilledType = $_SESSION['graph_type'] ?? 'directed';

// 2. Traitement Spécifique de l'Upload CSV (écrase la session actuelle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $graphData = CsvParser::parse($_FILES['csv_file']['tmp_name']);
            $preFilledType = $_POST['graph_type'] ?? 'directed';
            $isDirected = ($preFilledType === 'directed');
            
            $preFilledMatrix = CsvParser::toMatrixJSON($graphData, $isDirected);
            $preFilledCount = count($graphData['nodes']);

            $_SESSION['matrix'] = $preFilledMatrix;
            $_SESSION['graph_type'] = $preFilledType;
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
}

require_once __DIR__ . '/../src/templates/header.php';
?>

<div class="flex flex-col md:flex-row w-full max-w-7xl mx-auto gap-8 p-4 h-[85vh]">
    <div class="flex-[2] glass-panel p-6 rounded-2xl flex flex-col">
        <h2 class="text-xl font-bold mb-4 text-primary">01. Définition de la Matrice</h2>
        
        <div class="flex gap-4 mb-4 bg-black/40 p-2 rounded-xl border border-white/5">
            <label class="flex-1 text-center cursor-pointer p-2 rounded-lg transition-all">
                <input type="radio" name="g_type_selector" value="directed" <?php echo ($preFilledType == 'directed') ? 'checked' : ''; ?> class="hidden peer">
                <span class="text-xs font-bold text-white/40 peer-checked:text-primary uppercase tracking-widest">Orienté (→)</span>
            </label>
            <label class="flex-1 text-center cursor-pointer p-2 rounded-lg transition-all">
                <input type="radio" name="g_type_selector" value="undirected" <?php echo ($preFilledType == 'undirected') ? 'checked' : ''; ?> class="hidden peer">
                <span class="text-xs font-bold text-white/40 peer-checked:text-secondary uppercase tracking-widest">Non-orienté (—)</span>
            </label>
        </div>

        <div class="flex gap-4 mb-4">
            <input type="number" id="node-count" min="2" max="25" value="<?php echo $preFilledCount; ?>" class="flex-1 bg-cyber-dark border border-white/10 rounded-lg p-3 text-white outline-none focus:border-primary transition font-mono">
            <button id="btn-generate-grid" class="bg-white/5 px-6 rounded-lg font-bold border border-white/10 hover:bg-white/10 transition uppercase text-xs tracking-widest text-white">Générer</button>
        </div>

        <div class="flex-1 overflow-auto bg-cyber-black rounded-lg border border-white/10 p-6 shadow-inner relative">
            <div id="matrix-grid-container" style="width: max-content; margin: 0 auto; display: grid;"></div>
        </div>

        <form action="visualize.php" method="POST" id="main-form" class="mt-4">
            <input type="hidden" name="graph_type" id="final-g-type" value="<?php echo $preFilledType; ?>">
            <textarea name="matrix" id="hidden-matrix" class="hidden"></textarea>
            <button type="submit" class="w-full py-4 bg-primary hover:bg-cyan-400 text-black font-bold rounded-xl transition-all shadow-[0_0_20px_rgba(0,240,255,0.3)] flex justify-center items-center gap-3 text-lg uppercase tracking-tighter">
                Visualiser <span class="material-symbols-rounded">arrow_forward</span>
            </button>
        </form>
    </div>

    <div class="flex-1 flex flex-col gap-6 overflow-y-auto pr-2">
        <!-- Dashboard Droite (CSV + Aide) reste identique -->
        <div class="glass-panel p-6 rounded-2xl">
            <h2 class="text-lg font-bold text-white mb-4">02. Import CSV</h2>
            <div class="h-32 border-2 border-dashed border-white/10 rounded-xl flex flex-col items-center justify-center hover:bg-white/5 relative cursor-pointer mb-4 group">
                <span class="material-symbols-rounded text-3xl text-white/20 group-hover:text-secondary transition">upload_file</span>
                <p class="text-white/40 text-[10px] font-mono">Cliquer ou Glisser .CSV</p>
                <input type="file" form="upload-form" name="csv_file" class="absolute inset-0 opacity-0 cursor-pointer" accept=".csv" required onchange="document.getElementById('upload-form').submit()">
            </div>
            <form action="" method="POST" enctype="multipart/form-data" id="upload-form" class="hidden">
                 <input type="hidden" name="graph_type" value="<?php echo $preFilledType; ?>">
            </form>
            <!-- Consignes styles Terminal ici... -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('matrix-grid-container');
    const hiddenM = document.getElementById('hidden-matrix');
    const nodeCountInput = document.getElementById('node-count');
    const finalGType = document.getElementById('final-g-type');
    
    // DONNÉES INJECTÉES PAR PHP
    const preFilledMatrix = <?php echo $preFilledMatrix !== 'null' ? $preFilledMatrix : 'null'; ?>;
    const preFilledCount = <?php echo $preFilledCount; ?>;

    document.querySelectorAll('input[name="g_type_selector"]').forEach(r => {
        r.addEventListener('change', e => finalGType.value = e.target.value);
    });

    function createGrid(n, data = null) {
        grid.innerHTML = '';
        let cellSize = n > 12 ? '1.8rem' : '2.8rem';
        grid.style.gridTemplateColumns = `40px repeat(${n}, ${cellSize})`;
        grid.style.gap = '6px';

        grid.appendChild(document.createElement('div')); 
        for(let i=0; i<n; i++) grid.appendChild(createLabel(String.fromCharCode(65 + i)));

        for(let i=0; i<n; i++) {
            grid.appendChild(createLabel(String.fromCharCode(65 + i)));
            for(let j=0; j<n; j++) {
                const inp = document.createElement('input');
                inp.type = 'text'; inp.readOnly = true;
                
                // On remplit si on a des données (depuis session ou CSV)
                const val = (data && data[i]) ? data[i][j] : 0;
                inp.value = val;

                inp.className = "w-full h-full bg-black/40 border border-white/10 text-center text-white font-mono rounded-lg cursor-pointer transition-all text-xs";
                if(val == 1) inp.classList.add('bg-primary/20', 'text-primary', 'border-primary/50', 'font-bold');
                
                inp.style.height = cellSize;
                inp.onclick = () => {
                    inp.value = inp.value == '1' ? '0' : '1';
                    inp.classList.toggle('bg-primary/20'); inp.classList.toggle('text-primary'); inp.classList.toggle('border-primary/50'); inp.classList.toggle('font-bold');
                    updateJSON(n);
                };
                grid.appendChild(inp);
            }
        }
        updateJSON(n);
    }

    function createLabel(t) {
        const d = document.createElement('div');
        d.className = "flex items-center justify-center font-bold text-primary/30 text-[10px] font-mono";
        d.innerText = t; return d;
    }

    function updateJSON(n) {
        const inps = grid.querySelectorAll('input');
        let m = [], r = [];
        inps.forEach((it, idx) => {
            r.push(parseInt(it.value) || 0);
            if((idx + 1) % n === 0) { m.push(r); r = []; }
        });
        hiddenM.value = JSON.stringify(m);
    }

    document.getElementById('btn-generate-grid').onclick = () => createGrid(parseInt(nodeCountInput.value));

    // CHARGEMENT INITIAL : Utilise les données de session si présentes
    createGrid(preFilledCount, preFilledMatrix);
});
</script>

<?php require_once __DIR__ . '/../src/templates/footer.php'; ?>