
</main><!-- /home-main -->

<!-- ============================
     الفوتر
============================  -->
<footer class="home-footer">
    <div class="home-footer-inner">
        <div class="home-footer-brand">
            <span>📋</span> <?= clean(APP_NAME) ?>
        </div>
        <div class="home-footer-copy">
            جميع الحقوق محفوظة © <?= date('Y') ?>
        </div>
        <div class="home-footer-links">
            <a href="<?= APP_URL ?>/">الرئيسية</a>
            <a href="<?= APP_URL ?>/login.php">تسجيل الدخول</a>
        </div>
    </div>
</footer>

<!-- JS الرئيسي -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<!-- JS التنقل في الموبايل -->
<script>
(function () {
    const toggle = document.getElementById('homeNavToggle');
    const nav    = document.getElementById('homeNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('home-nav--open');
            this.classList.toggle('active');
        });
    }

    // الهيدر شفاف عند الأعلى
    const header = document.getElementById('homeHeader');
    if (header) {
        window.addEventListener('scroll', function () {
            header.classList.toggle('scrolled', window.scrollY > 40);
        }, { passive: true });
    }
})();
</script>

</body>
</html>
