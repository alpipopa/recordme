<?php
/**
 * الصفحة الرئيسية العامة
 * Public Homepage with Login Block + Dynamic Slider
 */
$pageTitle = 'مرحباً بك';
require_once __DIR__ . '/includes/home_header.php';

// ============================
// معالجة نموذج تسجيل الدخول
// ============================
$loginError   = '';
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['home_login'])) {
    requireCsrf();
    $username = post('username');
    $password = post('password');

    if (empty($username) || empty($password)) {
        $loginError = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $result = attemptLogin($username, $password);
        if ($result === true) {
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            exit;
        } else {
            $loginError = $result; // رسالة الخطأ
        }
    }
}

// ============================
// جلب السلايدات من قاعدة البيانات
// ============================
$sliders = [];
try {
    $sliders = dbQuery(
        "SELECT * FROM sliders WHERE status='active' ORDER BY sort_order ASC, id ASC"
    );
} catch (Exception $e) {
    // الجدول غير موجود بعد? نستمر بدون سلايدر
    $sliders = [];
}

// بيانات احتياطية إذا كانت القاعدة فارغة
if (empty($sliders)) {
    $sliders = [
        [
            'id' => 0, 'title' => 'حفظ البيانات بأمان واحترافية',
            'description' => 'نظام متكامل لحفظ وإدارة بيانات الأشخاص بأعلى معايير الأمان والخصوصية.',
            'image' => null,
        ],
        [
            'id' => 0, 'title' => 'نظام إدارة الموظفين والفئات',
            'description' => 'تصنيف الأشخاص ضمن فئات مرنة مع حقول مخصصة ديناميكية.',
            'image' => null,
        ],
        [
            'id' => 0, 'title' => 'تقارير وطباعة احترافية',
            'description' => 'أنشئ تقارير مفصلة وطابعها بضغطة واحدة أو صدّرها بصيغة Excel.',
            'image' => null,
        ],
    ];
}

// ألوان تدرجية للسلايدات بدون صور
$gradients = [
    'linear-gradient(135deg,#1e3a5f 0%,#2a5298 50%,#0ea5e9 100%)',
    'linear-gradient(135deg,#134e4a 0%,#059669 50%,#34d399 100%)',
    'linear-gradient(135deg,#7c2d12 0%,#ea580c 50%,#fb923c 100%)',
    'linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#a78bfa 100%)',
    'linear-gradient(135deg,#1e293b 0%,#334155 50%,#64748b 100%)',
];

// أيقونات خدمات
$serviceIcons = ['🔒', '👥', '📊', '📋', '⚡', '🛡'];
?>

<!-- ============================
     محتوى الصفحة الرئيسية
============================  -->
<div class="home-layout">

    <!-- ============================
         العمود الأيمن: تسجيل الدخول
    ============================  -->
    <aside class="home-login-col">
        <div class="login-widget">

            <!-- شعار البلوك -->
            <div class="login-widget-header">
                <div class="login-widget-icon">🔑</div>
                <h2>تسجيل الدخول</h2>
                <p>ادخل إلى لوحة التحكم</p>
            </div>

            <!-- رسالة خطأ / نجاح -->
            <?php if (!empty($loginError)): ?>
            <div class="login-widget-error">
                <span>⚠</span> <?= clean($loginError) ?>
            </div>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
            <!-- المستخدم مسجّل دخول -->
            <div class="login-widget-logged">
                <div class="logged-avatar"><?= mb_substr(currentUser()['full_name'] ?? 'م', 0, 1) ?></div>
                <p class="logged-name"><?= clean(currentUser()['full_name'] ?? '') ?></p>
                <a href="<?= APP_URL ?>/admin/dashboard.php" class="login-widget-btn">
                    ⚙ الانتقال للوحة التحكم
                </a>
                <a href="<?= APP_URL ?>/logout.php" class="login-widget-link">تسجيل الخروج</a>
            </div>

            <?php else: ?>
            <!-- نموذج تسجيل الدخول -->
            <form method="POST" action="" class="login-widget-form" autocomplete="on">
                <?= csrfField() ?>
                <input type="hidden" name="home_login" value="1">

                <div class="lw-field">
                    <label for="hw-username">👤 اسم المستخدم</label>
                    <input type="text" id="hw-username" name="username" class="lw-input"
                           placeholder="أدخل اسم المستخدم" autocomplete="username"
                           value="<?= clean(post('username')) ?>" required>
                </div>

                <div class="lw-field">
                    <label for="hw-password">🔒 كلمة المرور</label>
                    <div class="lw-pass-wrap">
                        <input type="password" id="hw-password" name="password" class="lw-input"
                               placeholder="أدخل كلمة المرور" autocomplete="current-password" required>
                        <button type="button" class="lw-eye" id="hwEyeBtn" title="إظهار/إخفاء">👁</button>
                    </div>
                </div>

                <button type="submit" class="login-widget-btn">
                    🚀 دخول
                </button>

                <a href="<?= APP_URL ?>/login.php" class="login-widget-link">
                    🔗 صفحة الدخول الكاملة
                </a>
            </form>
            <?php endif; ?>

            <!-- تفاصيل النظام -->
            <div class="login-widget-footer">
                <div class="lw-feature"><span>✅</span> تسجيل دخول آمن</div>
                <div class="lw-feature"><span>✅</span> تشفير البيانات</div>
                <div class="lw-feature"><span>✅</span> حماية CSRF</div>
            </div>

        </div>
    </aside>

    <!-- ============================
         العمود الأيسر/الأوسط: السلايدر
    ============================  -->
    <section class="home-slider-col">

        <!-- السلايدر الرئيسي -->
        <div class="home-slider" id="homeSlider" role="region" aria-label="عرض الخدمات">

            <!-- الشرائح -->
            <div class="slider-track" id="sliderTrack">
            <?php foreach ($sliders as $i => $slide): ?>
            <div class="slider-slide <?= $i === 0 ? 'active' : '' ?>"
                 aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>"
                 data-index="<?= $i ?>">

                <!-- خلفية: صورة أو تدرج -->
                <?php if (!empty($slide['image']) && file_exists(UPLOAD_PATH . '/sliders/' . $slide['image'])): ?>
                <div class="slide-bg"
                     style="background-image:url('<?= UPLOAD_URL ?>/sliders/<?= clean($slide['image']) ?>')">
                </div>
                <?php else: ?>
                <div class="slide-bg" style="background:<?= $gradients[$i % count($gradients)] ?>"></div>
                <?php endif; ?>

                <!-- طبقة التعتيم -->
                <div class="slide-overlay"></div>

                <!-- محتوى الشريحة -->
                <div class="slide-content">
                    <div class="slide-icon"><?= $serviceIcons[$i % count($serviceIcons)] ?></div>
                    <h2 class="slide-title"><?= clean($slide['title']) ?></h2>
                    <?php if (!empty($slide['description'])): ?>
                    <p class="slide-desc"><?= clean($slide['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($slide['link_url'])): ?>
                    <a href="<?= clean($slide['link_url']) ?>" class="slide-cta">اكتشف المزيد ←</a>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
            </div>

            <!-- أسهم التنقل -->
            <?php if (count($sliders) > 1): ?>
            <button class="slider-arrow slider-prev" id="sliderPrev" aria-label="السابق">&#8250;</button>
            <button class="slider-arrow slider-next" id="sliderNext" aria-label="التالي">&#8249;</button>

            <!-- نقاط التنقل -->
            <div class="slider-dots" id="sliderDots" role="tablist">
                <?php foreach ($sliders as $i => $slide): ?>
                <button class="slider-dot <?= $i === 0 ? 'active' : '' ?>"
                        data-index="<?= $i ?>"
                        role="tab"
                        aria-label="الشريحة <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- عداد الشرائح -->
            <div class="slider-counter" id="sliderCounter">
                <span id="slideCurrentNum">1</span> / <span><?= count($sliders) ?></span>
            </div>
        </div>

        <!-- معلومات الخدمات -->
        <div class="home-features">
            <div class="home-feature-item">
                <span class="feature-icon">🔐</span>
                <div>
                    <h4>أمان عالي</h4>
                    <p>تشفير كامل للبيانات وحماية من الثغرات</p>
                </div>
            </div>
            <div class="home-feature-item">
                <span class="feature-icon">📱</span>
                <div>
                    <h4>متجاوب مع الأجهزة</h4>
                    <p>يعمل على الهاتف والتابلت والحاسوب</p>
                </div>
            </div>
            <div class="home-feature-item">
                <span class="feature-icon">⚡</span>
                <div>
                    <h4>سريع وموثوق</h4>
                    <p>استجابة فورية وأداء مُحسَّن</p>
                </div>
            </div>
        </div>

    </section>

</div><!-- /home-layout -->

<!-- ============================
     JavaScript السلايدر
============================  -->
<script>
(function () {
    'use strict';

    const track     = document.getElementById('sliderTrack');
    if (!track) return;

    const slides    = track.querySelectorAll('.slider-slide');
    const dots      = document.querySelectorAll('.slider-dot');
    const prevBtn   = document.getElementById('sliderPrev');
    const nextBtn   = document.getElementById('sliderNext');
    const counter   = document.getElementById('slideCurrentNum');
    const total     = slides.length;
    let   current   = 0;
    let   autoTimer = null;
    const INTERVAL  = 5000; // 5 ثوان

    if (total <= 1) return;

    function goTo(index) {
        slides[current].classList.remove('active');
        slides[current].setAttribute('aria-hidden', 'true');
        dots[current]?.classList.remove('active');

        current = (index + total) % total;

        slides[current].classList.add('active');
        slides[current].setAttribute('aria-hidden', 'false');
        dots[current]?.classList.add('active');
        if (counter) counter.textContent = current + 1;
    }

    function startAuto() {
        stopAuto();
        autoTimer = setInterval(() => goTo(current + 1), INTERVAL);
    }

    function stopAuto() {
        if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    }

    // أسهم
    prevBtn?.addEventListener('click', function () { goTo(current - 1); startAuto(); });
    nextBtn?.addEventListener('click', function () { goTo(current + 1); startAuto(); });

    // نقاط
    dots.forEach(dot => {
        dot.addEventListener('click', function () {
            goTo(parseInt(this.dataset.index));
            startAuto();
        });
    });

    // لمس (swipe)
    let startX = 0;
    track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', e => {
        const diff = startX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
            diff > 0 ? goTo(current + 1) : goTo(current - 1);
            startAuto();
        }
    });

    // إيقاف عند hover
    track.addEventListener('mouseenter', stopAuto);
    track.addEventListener('mouseleave', startAuto);

    // لوحة المفاتيح
    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft')  { goTo(current + 1); startAuto(); }
        if (e.key === 'ArrowRight') { goTo(current - 1); startAuto(); }
    });

    // تشغيل
    startAuto();

    // إظهار/إخفاء كلمة المرور
    const eyeBtn = document.getElementById('hwEyeBtn');
    const passInput = document.getElementById('hw-password');
    if (eyeBtn && passInput) {
        eyeBtn.addEventListener('click', function () {
            const isPass = passInput.type === 'password';
            passInput.type = isPass ? 'text' : 'password';
            this.textContent = isPass ? '🙈' : '👁';
        });
    }

})();
</script>

<?php require_once __DIR__ . '/includes/home_footer.php'; ?>
