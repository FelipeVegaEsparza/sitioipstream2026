    </main>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="flash-msg" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-xl text-sm font-medium z-50 animate-bounce">
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
        <script>setTimeout(() => { const f=document.getElementById('flash-msg'); if(f) f.remove(); }, 4000);</script>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div id="flash-msg" class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-xl text-sm font-medium z-50">
            <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
        <script>setTimeout(() => { const f=document.getElementById('flash-msg'); if(f) f.remove(); }, 4000);</script>
    <?php endif; ?>
</body>
</html>