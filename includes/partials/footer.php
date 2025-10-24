<?php
/**
 * Ortak Footer Partial
 * Değişkenler (opsiyonel):
 *  - $showFooter : Basit bir footer alanı gösterilsin mi (default: false)
 */

$showFooter = array_key_exists('showFooter', get_defined_vars()) ? (bool)$showFooter : false;
?>

<?php if ($showFooter): ?>
    <footer class="mt-5 py-4 text-center text-muted border-top">
        <div class="container">
            © <?= date('Y') ?> Diyetlenio. Tüm hakları saklıdır.
        </div>
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

