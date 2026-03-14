<?php
session_start();
if(isset($_SESSION['user'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <title>GraphSense - Identification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600&display=swap" rel="stylesheet">
    <style>
        body { 
            background: #050505; 
            background-image: radial-gradient(circle at 50% 50%, #1a2c38 0%, #050505 100%);
            font-family: 'Outfit', sans-serif;
        }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-4">

    <div class="glass w-full max-w-md p-8 rounded-3xl shadow-2xl">
        <div class="text-center mb-10">
            <div class="inline-flex p-3 rounded-2xl bg-cyan-500/10 mb-4 border border-cyan-500/20">
                <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Graph<span class="text-cyan-400">Sense</span></h1>
            <p class="text-gray-400 text-sm mt-2">Accès sécurisé à l'infrastructure GDS</p>
        </div>

        <div id="alert" class="hidden mb-6 p-4 rounded-xl text-sm border"></div>

        <form id="loginForm" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Identifiant</label>
                <input type="text" id="username" required class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-cyan-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Mot de passe</label>
                <input type="password" id="password" required class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-cyan-500 transition-all">
            </div>
            
            <button type="submit" id="btnSubmit" class="w-full bg-cyan-500 hover:bg-white text-black font-bold py-4 rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-95">
                IDENTIFICATION
            </button>
        </form>

        <p class="text-center text-gray-500 text-xs mt-8">
            &copy; 2026 GraphSense GDS Analytics - PFE Cyber
        </p>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const alert = document.getElementById('alert');
        const btn = document.getElementById('btnSubmit');

        form.onsubmit = async (e) => {
            e.preventDefault();
            btn.disabled = true;
            btn.innerText = "VÉRIFICATION...";

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'login',
                        username: document.getElementById('username').value,
                        password: document.getElementById('password').value
                    })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    window.location.href = 'index.php';
                } else {
                    alert.innerText = data.message;
                    alert.className = "mb-6 p-4 rounded-xl text-sm bg-red-500/10 border-red-500/20 text-red-400";
                    alert.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerText = "IDENTIFICATION";
                }
            } catch (error) {
                console.error(error);
                btn.disabled = false;
                btn.innerText = "ERREUR SYSTÈME";
            }
        };
    </script>
</body>
</html>