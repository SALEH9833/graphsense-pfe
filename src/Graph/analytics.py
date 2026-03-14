import json
import subprocess
import sys
import os

def get_column_label(n):
    n += 1
    result = ''
    while n > 0:
        n -= 1
        result = chr(65 + (n % 26)) + result
        n = n // 26
    return result

def analyze_graph(matrix_string, algo='degree', graph_type='directed'):
    try:
        if not matrix_string:
            return {'status': 'error', 'message': 'Matrice vide.'}
        
        # Chemin vers neo_manager.py
        neo_manager_script = os.path.join(os.path.dirname(__file__), '../../src/Graph/neo_manager.py')
        
        # CORRECTION : On ajoute le 4ème argument 'graph_type'
        cmd = [sys.executable, neo_manager_script, 'analyze', matrix_string, algo, graph_type]
        
        # Exécution
        result = subprocess.run(cmd, capture_output=True, text=True, encoding='utf-8')
        
        if result.returncode != 0:
            return {'status': 'error', 'message': f'Python Crash: {result.stderr}'}
        
        # On essaie de parser la sortie de neo_manager
        try:
            result_python = json.loads(result.stdout)
        except json.JSONDecodeError:
            return {'status': 'error', 'message': f'Sortie invalide de neo_manager: {result.stdout}'}
        
        if result_python.get('status') == 'error':
            return {'status': 'error', 'message': result_python.get('message')}
        
        # Reconstruction nodes/edges pour Vis.js
        matrix = json.loads(matrix_string)
        nodes = []
        edges = []
        count = len(matrix)
        
        for i in range(count):
            node_id = get_column_label(i)
            nodes.append({'id': node_id, 'label': node_id})
            for j in range(count):
                if matrix[i][j] == 1:
                    edges.append({'from': node_id, 'to': get_column_label(j)})
        
        return {
            'status': 'success',
            'nodes': nodes,
            'edges': edges,
            'centrality': result_python.get('centrality')
        }
    
    except Exception as e:
        return {'status': 'error', 'message': str(e)}

if __name__ == '__main__':
    # CORRECTION : On lit les arguments passés par PHP (sys.argv) 
    # au lieu de sys.stdin.read()
    if len(sys.argv) < 3:
        print(json.dumps({'status': 'error', 'message': 'Arguments insuffisants'}))
        sys.exit(1)

    matrix_string = sys.argv[1]
    algo = sys.argv[2]
    # On gère le 3ème argument optionnel pour le type de graphe
    graph_type = sys.argv[3] if len(sys.argv) > 3 else 'directed'
    
    result = analyze_graph(matrix_string, algo, graph_type)
    print(json.dumps(result))