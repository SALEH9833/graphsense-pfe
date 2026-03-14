<?php
session_start();
header('Content-Type: application/json');

try {
    // 1. Récupération des données envoyées par le JavaScript
    $input = json_decode(file_get_contents('php://input'), true);
    $matrixString = $input['matrix'] ?? '';
    $algo = $input['algo'] ?? 'degree';
    $graphType = $input['graph_type'] ?? 'directed'; // 'directed' ou 'undirected'

    if (empty($matrixString)) {
        throw new Exception("Matrice vide.");
    }

    // 2. Préparation de l'appel Python vers neo_manager.py
    $pythonScript = __DIR__ . '/../../src/Graph/neo_manager.py';
    $cleanMatrix = json_encode(json_decode($matrixString));
    
    // Commande avec les 4 arguments requis par ton script Python
    $cmd = "python " . escapeshellarg($pythonScript) . " analyze " . escapeshellarg($cleanMatrix) . " " . escapeshellarg($algo) . " " . escapeshellarg($graphType);
    
    $output = shell_exec($cmd);
    $resultPython = json_decode($output, true);

    if (!$resultPython || $resultPython['status'] === 'error') {
        throw new Exception($resultPython['message'] ?? "Le moteur de calcul Python n'a pas répondu correctement.");
    }

    // 3. Reconstruction des objets pour Vis.js (Nodes et Edges)
    $matrix = json_decode($matrixString, true);
    $nodes = []; 
    $edges = []; 
    $count = count($matrix);

    function getLetter($n) { 
        $n++; $r = ''; 
        while($n > 0){ $n--; $r = chr(65 + ($n % 26)) . $r; $n = floor($n / 26); } 
        return $r; 
    }

    for ($i = 0; $i < $count; $i++) {
        $id = getLetter($i);
        $nodes[] = ['id' => $id, 'label' => $id];
        for ($j = 0; $j < $count; $j++) {
            if ($matrix[$i][$j] == 1) {
                $edges[] = ['from' => $id, 'to' => getLetter($j)];
            }
        }
    }

    // 4. RÉPONSE FINALE : On renvoie TOUT, y compris graph_type
    echo json_encode([
        'status' => 'success',
        'nodes' => $nodes,
        'edges' => $edges,
        'centrality' => $resultPython['centrality'],
        'graph_type' => $graphType // <-- INDISPENSABLE pour que le JS sache quoi dessiner
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}