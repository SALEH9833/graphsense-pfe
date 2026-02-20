<!-- Fichier: src/templates/footer.php -->

</main> <!-- Fermeture du MAIN ouvert dans header.php -->

<footer class="h-10 bg-cyber-dark border-t border-white/5 flex items-center justify-between px-6 text-[10px] font-mono uppercase tracking-widest text-white/30 z-50">
    
    <!-- GAUCHE : INFO COPYRIGHT -->
    <div class="flex items-center gap-4">
        <span class="hover:text-white transition cursor-default">GraphSense © 2026</span>
        <span class="hidden md:inline">|</span>
        <span class="hidden md:inline hover:text-primary transition cursor-pointer">Documentation v2.1</span>
    </div>

    <!-- CENTRE : STATUT -->
    <div class="flex items-center gap-2 group cursor-help">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
        </span>
        <span class="group-hover:text-green-400 transition">Système Opérationnel</span>
    </div>

    <!-- DROITE : INFO TECH -->
    <div class="flex items-center gap-4">
        <span class="text-right">PHP v<?php echo phpversion(); ?></span>
        <span class="hidden md:inline">|</span>
        <span class="text-primary">GDS Library</span>
    </div>

</footer>

</body>
</html>