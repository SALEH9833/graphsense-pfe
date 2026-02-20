<?php
namespace Src\Graph;

class CsvParser {
    
    /**
     * Lit un fichier CSV et retourne les noeuds et arêtes.
     * Format attendu : Source,Target (pas d'en-têtes de préférence)
     */
    public static function parse(string $filePath): array {
        $nodes = [];
        $edges = [];
        $nodeMap = []; // Pour éviter les doublons

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // On saute les lignes vides ou incomplètes
                if (count($data) < 2) continue;
                
                $source = trim($data[0]);
                $target = trim($data[1]);
                
                // On saute la ligne d'en-tête si elle existe
                if (strtolower($source) === 'source' && strtolower($target) === 'target') continue;
                if (empty($source) || empty($target)) continue;

                // Ajouter les noeuds
                if (!isset($nodeMap[$source])) {
                    $nodeMap[$source] = count($nodes); // On garde l'index
                    $nodes[] = ['id' => $source, 'label' => $source];
                }
                if (!isset($nodeMap[$target])) {
                    $nodeMap[$target] = count($nodes);
                    $nodes[] = ['id' => $target, 'label' => $target];
                }

                // Ajouter l'arête
                $edges[] = ['from' => $source, 'to' => $target];
            }
            fclose($handle);
        }
        
        return ['nodes' => $nodes, 'edges' => $edges];
    }
    
    /**
     * Convertit le résultat du CSV en Matrice d'Adjacence (JSON)
     * pour le stocker en SESSION comme si l'utilisateur l'avait tapé.
     */
    public static function toMatrixJSON(array $data): string {
        $nodes = $data['nodes'];
        $count = count($nodes);
        if ($count === 0) return '[]';

        // Map ID -> Index (0, 1, 2...)
        $idToIndex = [];
        foreach ($nodes as $index => $node) {
            $idToIndex[$node['id']] = $index;
        }
        
        // Initialiser matrice avec des 0
        $matrix = array_fill(0, $count, array_fill(0, $count, 0));
        
        foreach ($data['edges'] as $edge) {
            $u = $idToIndex[$edge['from']];
            $v = $idToIndex[$edge['to']];
            $matrix[$u][$v] = 1;
        }
        
        return json_encode($matrix);
    }
}