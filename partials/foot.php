</main>
<?php if ($currentUser ?? null): ?>
<footer class="border-t border-neutral-200 py-6 text-center text-xs text-neutral-400">
    <?= htmlspecialchars(APP_NAME) ?> &middot; Internal order tracking
</footer>
<?php endif; ?>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php foreach ($extraScripts ?? [] as $src): ?>
<script src="<?= asset($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
