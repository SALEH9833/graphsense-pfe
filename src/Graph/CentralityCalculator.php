<?php
namespace Src\Graph;

class CentralityCalculator {

    // 1. DEGRÉ
    public static function calculateDegree(array $nodes, array $edges): array {
        $scores = [];
        foreach ($nodes as $node) $scores[$node['id']] = 0;

        foreach ($edges as $edge) {
            if (isset($scores[$edge['from']])) $scores[$edge['from']]++;
            // Décommenter pour graphe non-orienté :
            // if (isset($scores[$edge['to']])) $scores[$edge['to']]++;
        }

        return self::formatResult($scores, "connexion(s) directe(s).");
    }

    // 2. PROXIMITÉ (Closeness)
    public static function calculateCloseness(array $nodes, array $edges): array {
        $scores = [];
        foreach ($nodes as $startNode) {
            $distances = [];
            $queue = [];
            foreach ($nodes as $n) $distances[$n['id']] = -1;
            
            $distances[$startNode['id']] = 0;
            $queue[] = $startNode['id'];
            
            while (!empty($queue)) {
                $u = array_shift($queue);
                foreach ($edges as $edge) {
                    if ($edge['from'] == $u && $distances[$edge['to']] == -1) {
                        $distances[$edge['to']] = $distances[$u] + 1;
                        $queue[] = $edge['to'];
                    }
                }
            }

            $sumDistance = 0;
            foreach ($distances as $dist) if ($dist > 0) $sumDistance += $dist;
            
            $scores[$startNode['id']] = ($sumDistance > 0) ? (count($nodes) - 1) / $sumDistance : 0;
        }
        return self::formatResult($scores, "de proximité.");
    }

    // 3. PAGERANK
    public static function calculatePageRank(array $nodes, array $edges, $d = 0.85, $iter = 20): array {
        $scores = [];
        $N = count($nodes);
        if ($N === 0) return [];

        foreach ($nodes as $n) $scores[$n['id']] = 1 / $N; // Init

        // Pré-calcul des degrés sortants
        $outDegree = [];
        foreach ($nodes as $n) $outDegree[$n['id']] = 0;
        foreach ($edges as $e) if(isset($outDegree[$e['from']])) $outDegree[$e['from']]++;

        // Itérations
        for ($i = 0; $i < $iter; $i++) {
            $newScores = [];
            foreach ($nodes as $node) {
                $sum = 0;
                // Trouver qui pointe vers moi
                foreach ($edges as $e) {
                    if ($e['to'] == $node['id']) {
                        $deg = $outDegree[$e['from']];
                        if ($deg > 0) $sum += $scores[$e['from']] / $deg;
                    }
                }
                $newScores[$node['id']] = (1 - $d) + ($d * $sum);
            }
            $scores = $newScores;
        }
        return self::formatResult($scores, "Score d'influence (PageRank).");
    }

    // 4. INTERMÉDIARITÉ (Betweenness) - Algorithme de Brandes simplifié
    public static function calculateBetweenness(array $nodes, array $edges): array {
        $CB = [];
        foreach ($nodes as $n) $CB[$n['id']] = 0;

        // Liste d'adjacence
        $adj = [];
        foreach ($nodes as $n) $adj[$n['id']] = [];
        foreach ($edges as $e) $adj[$e['from']][] = $e['to'];

        foreach ($nodes as $sNode) {
            $s = $sNode['id'];
            $stack = [];
            $P = []; 
            $sigma = []; 
            $d = []; 
            
            foreach ($nodes as $n) {
                $P[$n['id']] = [];
                $sigma[$n['id']] = 0;
                $d[$n['id']] = -1;
            }
            $sigma[$s] = 1;
            $d[$s] = 0;
            $queue = [$s];

            while (!empty($queue)) {
                $v = array_shift($queue);
                $stack[] = $v;
                if(isset($adj[$v])) {
                    foreach ($adj[$v] as $w) {
                        if ($d[$w] < 0) {
                            $queue[] = $w;
                            $d[$w] = $d[$v] + 1;
                        }
                        if ($d[$w] == $d[$v] + 1) {
                            $sigma[$w] += $sigma[$v];
                            $P[$w][] = $v;
                        }
                    }
                }
            }

            $delta = [];
            foreach ($nodes as $n) $delta[$n['id']] = 0;
            
            while (!empty($stack)) {
                $w = array_pop($stack);
                foreach ($P[$w] as $v) {
                    $delta[$v] += ($sigma[$v] / $sigma[$w]) * (1 + $delta[$w]);
                }
                if ($w != $s) $CB[$w] += $delta[$w];
            }
        }
        return self::formatResult($CB, "chemins les plus courts.");
    }

    private static function formatResult($scores, $descSuffix) {
        $results = [];
        $maxScore = max($scores) ?: 1;
        foreach ($scores as $id => $score) {
            $results[] = [
                'id' => $id,
                'score' => number_format($score, 2),
                'normalized' => ($maxScore > 0) ? round($score / $maxScore, 2) : 0,
                'description' => "Noeud $id : $descSuffix"
            ];
        }
        usort($results, function($a, $b) { return $b['normalized'] <=> $a['normalized']; });
        return $results;
    }
}