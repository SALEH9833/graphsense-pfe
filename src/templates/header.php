<!DOCTYPE html>
<html class="dark" lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>GraphSense GDS - Analytics</title>
    
    <!-- Tailwind & Vis.js -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <link href="https://unpkg.com/vis-network/styles/vis-network.min.css" rel="stylesheet" type="text/css" />

    <!-- Fonts Pro -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "cyber-black": "#050505",
                        "cyber-dark": "#0A0F14",
                        "cyber-gray": "#1B232D",
                        "primary": "#00F0FF",    /* Cyan Cyberpunk */
                        "primary-dim": "rgba(0, 240, 255, 0.1)",
                        "secondary": "#7000FF",  /* Violet Néon */
                        "accent": "#FF003C"      /* Rouge Alerte */
                    },
                    fontFamily: {
                        "display": ["Outfit", "sans-serif"],
                        "mono": ["JetBrains Mono", "monospace"]
                    }
                }
            }
        }
    </script>
    <style>
        select::-ms-expand { display: none; }
/* Force la suppression de l'apparence */
select { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
        body { 
            background-color: #050505; 
            background-image: radial-gradient(circle at 50% 0%, #1a2c38 0%, #050505 60%);
            color: #E0E0E0; 
            font-family: 'Outfit', sans-serif; 
        }

        /* Effet Verre (Glassmorphism) */
        .glass-panel {
            background: rgba(27, 35, 45, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }

        /* Scrollbar invisible mais fonctionnelle */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #00F0FF; }

        /* Effet de lueur sur les textes */
        .glow-text { text-shadow: 0 0 10px rgba(0, 240, 255, 0.5); }
        
        /* Animation subtile */
        @keyframes pulse-border {
            0% { border-color: rgba(255,255,255,0.05); }
            50% { border-color: rgba(0, 240, 255, 0.3); }
            100% { border-color: rgba(255,255,255,0.05); }
        }
        .animate-pulse-border { animation: pulse-border 3s infinite; }
    </style>
</head>
<body class="flex flex-col h-screen overflow-hidden selection:bg-primary selection:text-black">

<!-- HEADER PRO -->
<header class="h-16 flex items-center justify-between px-6 border-b border-white/5 bg-cyber-dark/80 backdrop-blur z-50">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-[0_0_15px_rgba(0,240,255,0.3)]">
            <span class="material-symbols-rounded text-black font-bold text-lg">hub</span>
        </div>
        <div class="flex flex-col">
            <h1 class="font-extrabold tracking-widest uppercase text-sm text-white">Graph<span class="text-primary glow-text">Sense</span></h1>
            <span class="text-[10px] font-mono text-white/40 tracking-[0.2em] uppercase">Powered by GDS</span>
        </div>
    </div>
    
    <nav class="flex p-1 bg-white/5 rounded-lg border border-white/5">
        <?php $page = basename($_SERVER['PHP_SELF']); ?>
        <a href="index.php" class="px-4 py-1.5 rounded text-xs font-bold uppercase tracking-wider transition-all <?php echo $page=='index.php' ? 'bg-primary text-black shadow-lg shadow-primary/20' : 'text-white/40 hover:text-white'; ?>">1. Data</a>
        <a href="visualize.php" class="px-4 py-1.5 rounded text-xs font-bold uppercase tracking-wider transition-all <?php echo $page=='visualize.php' ? 'bg-primary text-black shadow-lg shadow-primary/20' : 'text-white/40 hover:text-white'; ?>">2. Graph</a>
        <a href="metrics.php" class="px-4 py-1.5 rounded text-xs font-bold uppercase tracking-wider transition-all <?php echo $page=='metrics.php' ? 'bg-primary text-black shadow-lg shadow-primary/20' : 'text-white/40 hover:text-white'; ?>">3. Analytics</a>
    </nav>
</header>

<main class="flex-1 flex flex-col relative overflow-hidden p-4 md:p-6">