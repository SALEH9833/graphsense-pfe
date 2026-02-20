<?php
namespace Src\Graph;

class MatrixParser {
    public static function parse(string $matrixString): array {
        // Nettoyer les espaces éventuels
        $matrixString = trim($matrixString);
        
        $matrix = json_decode($matrixString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Le format JSON est invalide. Vérifiez les crochets [].");
        }
        if (!is_array($matrix) || empty($matrix)) {
            throw new \Exception("La matrice est vide.");
        }

        $nodeCount = count($matrix);
        $nodes = [];
        $edges = [];

        // Générer les labels A, B, C...
        for ($i = 0; $i < $nodeCount; $i++) {
            $id = self::getNameFromIndex($i);
            $nodes[] = ['id' => $id, 'label' => $id];
        }

        // Parcourir la matrice
        for ($i = 0; $i < $nodeCount; $i++) {
            if (!is_array($matrix[$i]) || count($matrix[$i]) !== $nodeCount) {
                throw new \Exception("Erreur ligne $i : La matrice doit être carrée.");
            }
            for ($j = 0; $j < $nodeCount; $j++) {
                if ($matrix[$i][$j] == 1) {
                    $edges[] = [
                        'from' => $nodes[$i]['id'],
                        'to' => $nodes[$j]['id']
                    ];
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    // Petit utilitaire pour gérer plus de 26 noeuds (A..Z, AA, AB...)
    private static function getNameFromIndex($n) {
        $n += 1;
        $r = '';
        while ($n > 0) {
            $n--;
            $r = chr(65 + ($n % 26)) . $r;
            $n = floor($n / 26);
        }
        return $r;
    }
}