import sys
import json
import networkx as nx

# Fonction pour convertir l'index (0, 1) en Label (A, B)
def get_label(n):
    label = ""
    n += 1
    while n > 0:
        n -= 1
        label = chr(65 + (n % 26)) + label
        n //= 26
    return label

def main():
    try:
        # 1. Récupération des arguments envoyés par PHP
        # sys.argv[1] = la matrice en JSON string
        # sys.argv[2] = le nom de l'algorithme
        if len(sys.argv) < 3:
            print(json.dumps({"error": "Arguments manquants"}))
            return

        matrix = json.loads(sys.argv[1])
        algo = sys.argv[2]

        # 2. Création du Graphe NetworkX
        # On crée un graphe orienté (DiGraph) à partir de la matrice numpy-style
        G = nx.DiGraph()
        
        num_nodes = len(matrix)
        for i in range(num_nodes):
            G.add_node(i) # On ajoute les noeuds 0, 1, 2...
            for j in range(num_nodes):
                if matrix[i][j] == 1:
                    G.add_edge(i, j)

        # 3. Calculs selon l'algorithme choisi
        scores = {}
        
        if algo == 'degree':
            # Degree centrality in NetworkX is normalized, usually we want simple count
            # But let's stick to NetworkX standard (normalized) or use degree counts
            raw_degrees = dict(G.degree())
            # Normalisation manuelle pour match l'affichage PHP précédent (max degree)
            scores = raw_degrees 

        elif algo == 'closeness':
            scores = nx.closeness_centrality(G)

        elif algo == 'betweenness':
            scores = nx.betweenness_centrality(G)

        elif algo == 'pagerank':
            try:
                scores = nx.pagerank(G, alpha=0.85)
            except nx.PowerIterationFailedConvergence:
                # Si le graphe est vide ou problématique
                scores = {n: 0 for n in G.nodes()}

        else:
            # Fallback
            scores = {n: 0 for n in G.nodes()}

        # 4. Formatage du résultat pour le Front-end
        # On doit renvoyer : [{"id": "A", "score": "0.50", "normalized": 0.5, "description": "..."}]
        results = []
        
        if scores:
            max_score = max(scores.values()) if len(scores) > 0 else 1
            if max_score == 0: max_score = 1

            for node_idx, score in scores.items():
                label = get_label(node_idx)
                normalized = score / max_score
                
                results.append({
                    "id": label,
                    "score": "{:.2f}".format(score),
                    "normalized": normalized,
                    "description": f"Calculé par Python NetworkX ({algo})"
                })

            # Tri décroissant par score
            results.sort(key=lambda x: float(x["score"]), reverse=True)

        # 5. Envoi du JSON final à PHP (via stdout)
        print(json.dumps(results))

    except Exception as e:
        # En cas de crash Python, on renvoie l'erreur en JSON
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()