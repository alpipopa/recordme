    </div><!-- .content-wrapper -->
</main><!-- .main-content -->

<!-- شريط الجانبي الغامق (overlay) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- تذييل الصفحة - Footer -->
<footer class="admin-footer">
    <div class="footer-inner">
        <div class="footer-logo">
            <?php 
            $logoUrl = getSetting('logo_path');
            if ($logoUrl && file_exists(UPLOAD_PATH . '/' . $logoUrl)): ?>
                <img src="<?= UPLOAD_URL ?>/<?= clean($logoUrl) ?>" alt="Logo">
            <?php else: ?>
                <span class="footer-icon">📋</span>
            <?php endif; ?>
            <span class="footer-text"><?= clean(getSetting('site_name', APP_NAME)) ?></span>
        </div>
        <div class="footer-copyright">
            &copy; <?= date('Y') ?> جميع الحقوق محفوظة - <?= clean(getSetting('site_name', APP_NAME)) ?>
        </div>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/notifications.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
