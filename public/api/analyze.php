<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Pas besoin de l'autoloader si on n'utilise plus les classes PHP pour le calcul
// Mais on garde pour le parsing initial si besoin

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $matrixString = $input['matrix'] ?? '';
    $algo = $input['algo'] ?? 'degree';

    if (empty($matrixString)) {
        throw new \Exception("Aucune matrice fournie.");
    }

    // 1. Appel au script Python Neo4j
    // ----------------------------------------------------
    $pythonScript = __DIR__ . '/../../src/Graph/gds_analytics.py';
    
    // Nettoyage de la matrice
    $cleanMatrix = json_encode(json_decode($matrixString));
    
    // Commande : python script.py "[[matrix]]" "algo"
    // Utilisez "python" ou "python3" selon votre installation
    $cmd = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($cleanMatrix) . " " . escapeshellarg($algo);
    
    $output = shell_exec($cmd);
    // ----------------------------------------------------

    if ($output === null) {
        throw new \Exception("Python ne répond pas. Vérifiez l'installation de 'pip install neo4j'.");
    }

    $centralityResults = json_decode($output, true);

    // Vérification des erreurs Python
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("Erreur Sortie Python (Raw): " . $output);
    }
    if (isset($centralityResults['error'])) {
        throw new \Exception("Erreur Neo4j/Python: " . $centralityResults['error']);
    }

    // 2. Reconstruire nodes/edges pour l'affichage JS (car Python ne renvoie que les scores)
    // On réutilise une logique simple PHP juste pour le dessin du graphe
    $matrix = json_decode($matrixString, true);
    $nodes = [];
    $edges = [];
    $count = count($matrix);
    
    // Fonction helper locale pour les lettres
    function getLabel($n) {
        $n += 1; $r = '';
        while ($n > 0) { $n--; $r = chr(65 + ($n % 26)) . $r; $n = floor($n / 26); }
        return $r;
    }

    for ($i = 0; $i < $count; $i++) {
        $id = getLabel($i);
        $nodes[] = ['id' => $id, 'label' => $id];
        for ($j = 0; $j < $count; $j++) {
            if ($matrix[$i][$j] == 1) {
                $edges[] = ['from' => $id, 'to' => getLabel($j)];
            }
        }
    }

    // 3. Réponse Finale
    echo json_encode([
        'status' => 'success',
        'nodes' => $nodes,
        'edges' => $edges,
        'centrality' => $centralityResults
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}