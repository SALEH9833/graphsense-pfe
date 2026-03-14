<?php
namespace Src\Graph;

class CsvParser {
    
    /**
     * Lit un fichier CSV et retourne les noeuds et arêtes.
     */
    public static function parse(string $filePath): array {
        $nodes = [];
        $edges = [];
        $nodeMap = []; 

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 2) continue;
                
                $source = trim($data[0]);
                $target = trim($data[1]);
                
                if (strtolower($source) === 'source' || empty($source) || empty($target)) continue;

                if (!isset($nodeMap[$source])) {
                    $nodeMap[$source] = true;
                    $nodes[] = ['id' => $source, 'label' => $source];
                }
                if (!isset($nodeMap[$target])) {
                    $nodeMap[$target] = true;
                    $nodes[] = ['id' => $target, 'label' => $target];
                }

                $edges[] = ['from' => $source, 'to' => $target];
            }
            fclose($handle);
        }

        // Optionnel : Trier les noeuds par nom pour que la matrice soit propre (A, B, C...)
        usort($nodes, function($a, $b) {
            return strcmp($a['id'], $b['id']);
        });
        
        return ['nodes' => $nodes, 'edges' => $edges];
    }
    
    /**
     * Convertit le résultat du CSV en Matrice d'Adjacence (JSON)
     * @param array $data Les données (nodes et edges)
     * @param bool $isDirected Si faux, la matrice sera symétrique
     */
    public static function toMatrixJSON(array $data, bool $isDirected = true): string {
        $nodes = $data['nodes'];
        $count = count($nodes);
        if ($count === 0) return '[]';

        // Création de la map ID -> Index
        $idToIndex = [];
        foreach ($nodes as $index => $node) {
            $idToIndex[$node['id']] = $index;
        }
        
        // Initialiser la matrice avec des 0
        $matrix = array_fill(0, $count, array_fill(0, $count, 0));
        
        foreach ($data['edges'] as $edge) {
            $u = $idToIndex[$edge['from']];
            $v = $idToIndex[$edge['to']];
            
            // On active la liaison
            $matrix[$u][$v] = 1;

            // SI NON-ORIENTÉ : On active aussi la liaison inverse pour la symétrie
            if (!$isDirected) {
                $matrix[$v][$u] = 1;
            }
        }
        
        return json_encode($matrix);
    }
}