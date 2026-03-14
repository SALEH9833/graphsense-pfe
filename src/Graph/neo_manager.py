import sys
import json
import bcrypt
from neo4j import GraphDatabase

# --- CONFIGURATION ---
URI = "neo4j://127.0.0.1:7687"
AUTH = ("neo4j", "98339866")

class NeoManager:
    def __init__(self):
        self.driver = GraphDatabase.driver(URI, auth=AUTH)

    def close(self):
        self.driver.close()

    # --- GESTION DES UTILISATEURS AVEC RÔLES ---
    def login(self, username, password):
        try:
            with self.driver.session() as session:
                # On récupère le mot de passe ET le rôle
                result = session.run(
                    "MATCH (u:User {username: $u}) RETURN u.password AS p, u.role AS r", 
                    u=username
                )
                record = result.single()
                
                if record and bcrypt.checkpw(password.encode('utf-8'), record['p'].encode('utf-8')):
                    return {
                        "status": "success", 
                        "username": username, 
                        "role": record.get('r', 'USER') # 'USER' par défaut si vide
                    }
                return {"status": "error", "message": "Identifiants invalides"}
        except Exception as e:
            return {"status": "error", "message": str(e)}

    def register(self, username, password, role='USER'):
        hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        try:
            with self.driver.session() as session:
                # Vérifier si l'utilisateur existe déjà
                check = session.run("MATCH (u:User {username: $u}) RETURN u", u=username)
                if check.single():
                    return {"status": "error", "message": "Utilisateur déjà existant"}
                
                # Création avec le rôle
                session.run("CREATE (u:User {username: $u, password: $p, role: $r})", 
                            u=username, p=hashed, r=role)
                return {"status": "success", "message": "Utilisateur créé"}
        except Exception as e:
            return {"status": "error", "message": str(e)}

    # --- ANALYTICS GDS ---
    def run_gds_analysis(self, matrix, algo, graph_type='directed'):
        orientation = "NATURAL" if graph_type == 'directed' else "UNDIRECTED"
        try:
            with self.driver.session() as session:
                # 1. Nettoyage (On ne supprime pas les :User !)
                session.run("MATCH (n:Node) DETACH DELETE n")
                session.run("CALL gds.graph.drop('myGraph', false)")
                
                # 2. Préparation des données
                num_nodes = len(matrix)
                nodes_data = [{"id": self._get_label(i)} for i in range(num_nodes)]
                edges_data = []
                for i in range(num_nodes):
                    for j in range(num_nodes):
                        if matrix[i][j] == 1:
                            edges_data.append({"from": self._get_label(i), "to": self._get_label(j)})

                # 3. Injection Batch
                session.run("UNWIND $nodes AS n CREATE (:Node {name: n.id})", nodes=nodes_data)
                session.run("""
                    UNWIND $edges AS e 
                    MATCH (a:Node {name: e.from}), (b:Node {name: e.to}) 
                    CREATE (a)-[:LINK]->(b)
                """, edges=edges_data)

                # 4. Projection GDS avec orientation
                session.run("""
                    CALL gds.graph.project('myGraph', 'Node', {LINK: {orientation: $ori}})
                """, ori=orientation)
                
                # 5. Détection des composants (pour savoir qui est avec qui)
                wcc = session.run("CALL gds.wcc.stream('myGraph') YIELD nodeId, componentId RETURN gds.util.asNode(nodeId).name AS name, componentId")
                comp_map = {r['name']: r['componentId'] for r in wcc}

                # 6. Calcul de l'algorithme
                algos = {
                    'pagerank': "gds.pageRank.stream",
                    'betweenness': "gds.betweenness.stream",
                    'closeness': "gds.beta.closeness.stream",
                    'degree': "gds.degree.stream"
                }
                proc = algos.get(algo, "gds.degree.stream")
                
                records = session.run(f"CALL {proc}('myGraph') YIELD nodeId, score RETURN gds.util.asNode(nodeId).name AS name, score ORDER BY score DESC")
                
                data = [r for r in records]
                max_s = max([r['score'] for r in data]) if data else 1
                
                # 7. Formatage avec le numéro de composant (groupe)
                res = []
                for r in data:
                    res.append({
                        "id": r['name'],
                        "score": "{:.4f}".format(r['score']),
                        "normalized": r['score'] / (max_s if max_s > 0 else 1),
                        "component": comp_map.get(r['name'], 0)
                    })
                
                session.run("CALL gds.graph.drop('myGraph', false)")
                return {"status": "success", "centrality": res}

        except Exception as e:
            return {"status": "error", "message": str(e)}

    def _get_label(self, n):
        n += 1
        r = ''
        while n > 0:
            n -= 1
            r = chr(65 + (n % 26)) + r
            n //= 26
        return r

if __name__ == "__main__":
    mgr = NeoManager()
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No action"}))
    else:
        action = sys.argv[1]
        if action == "analyze":
            # Analyse nécessite : matrice (argv[2]), algo (argv[3]), type (argv[4])
            print(json.dumps(mgr.run_gds_analysis(json.loads(sys.argv[2]), sys.argv[3], sys.argv[4])))
        elif action == "login":
            print(json.dumps(mgr.login(sys.argv[2], sys.argv[3])))
        elif action == "register":
            # Par défaut, on peut enregistrer comme 'USER' ou 'ADMIN'
            role = sys.argv[4] if len(sys.argv) > 4 else 'USER'
            print(json.dumps(mgr.register(sys.argv[2], sys.argv[3], role)))
    mgr.close()