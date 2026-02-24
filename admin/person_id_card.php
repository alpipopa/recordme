<?php
/**
 * توليد بطاقة تعريفية - ID Card Generator
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int)get('id');
$person = getPersonById($id);

if (!$person) {
    die("الشخص غير موجود.");
}

// إنشاء رابط الـ QR Code لملف الشخص
$qrData = APP_URL . "/admin/person_view.php?id=" . $id;
$qrUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qrData);

// جلب الشعار من الإعدادات
$logoPath = getSetting('logo_path');
$siteName = getSetting('site_name', 'نظام تسجيل البيانات');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة تعريفية - <?= clean($person['full_name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }

        .id-card {
            width: 85.6mm;
            height: 53.98mm;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            background-image: 
                linear-gradient(135deg, rgba(44, 62, 80, 0.05) 25%, transparent 25%), 
                linear-gradient(225deg, rgba(44, 62, 80, 0.05) 25%, transparent 25%), 
                linear-gradient(45deg, rgba(44, 62, 80, 0.05) 25%, transparent 25%), 
                linear-gradient(315deg, rgba(44, 62, 80, 0.05) 25%, transparent 25%);
            background-position: 10px 0, 10px 0, 0 0, 0 0;
            background-size: 20px 20px;
            background-repeat: repeat;
        }

        /* رأس البطاقة */
        .card-header {
            height: 18mm;
            background: var(--primary, #2c3e50);
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 10px;
            position: relative;
        }
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 5px;
            background: #f1c40f;
        }
        .logo { width: 35px; height: 35px; background: #fff; border-radius: 5px; padding: 2px; margin-left: 10px; object-fit: contain; }
        .site-name { font-size: 10pt; font-weight: 700; line-height: 1.2; }

        /* محتوى البطاقة */
        .card-body {
            flex: 1;
            display: flex;
            padding: 8px 10px;
            gap: 10px;
        }

        .photo-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        .person-photo {
            width: 22mm;
            height: 28mm;
            border: 2px solid #2c3e50;
            border-radius: 5px;
            object-fit: cover;
            background: #eee;
        }
        .qr-code {
            width: 12mm;
            height: 12mm;
            object-fit: contain;
        }

        .data-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .info-row { margin-bottom: 3px; }
        .label { font-size: 7pt; color: #7f8c8d; display: block; margin-bottom: -2px; }
        .value { font-size: 9pt; font-weight: 700; color: #2c3e50; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .value.name { font-size: 10.5pt; color: #c0392b; margin-bottom: 2px; }

        /* تذييل البطاقة */
        .card-footer {
            height: 6mm;
            background: #2c3e50;
            color: #fff;
            font-size: 6pt;
            display: flex;
            justify-content: center;
            align-items: center;
            letter-spacing: 0.5px;
        }

        @media print {
            body { background: none; padding: 0; display: block; }
            .no-print { display: none !important; }
            .id-card { box-shadow: none; border: 1px solid #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        .no-print-btns {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
        }
        .btn-print { background: #27ae60; color: #fff; }
        .btn-back { background: #34495e; color: #fff; }
    </style>
</head>
<body>

    <div class="no-print-btns no-print">
        <button onclick="window.print()" class="btn btn-print">🖨 طباعة البطاقة</button>
        <a href="person_view.php?id=<?= $id ?>" class="btn btn-back">🔙 العودة للملف</a>
    </div>

    <div class="id-card">
        <div class="card-header">
            <?php if ($logoPath && file_exists(UPLOAD_PATH . '/' . $logoPath)): ?>
                <img src="<?= UPLOAD_URL ?>/<?= clean($logoPath) ?>" class="logo">
            <?php else: ?>
                <div class="logo" style="display: flex; align-items: center; justify-content: center; font-size: 15pt; color: #2c3e50;">📋</div>
            <?php endif; ?>
            <div class="site-name"><?= clean($siteName) ?></div>
        </div>

        <div class="card-body">
            <div class="photo-side">
                <?php 
                $photoPath = '';
                if ($person['personal_photo'] && file_exists(UPLOAD_PATH . '/' . $person['personal_photo'])) {
                    $photoPath = UPLOAD_URL . '/' . clean($person['personal_photo']);
                } elseif ($person['id_image'] && file_exists(UPLOAD_PATH . '/' . $person['id_image'])) {
                    $photoPath = UPLOAD_URL . '/' . clean($person['id_image']);
                } else {
                    $photoPath = APP_URL . '/assets/img/default-avatar.png';
                }
                ?>
                <img src="<?= $photoPath ?>" class="person-photo" onerror="this.src='<?= APP_URL ?>/assets/img/default-avatar.png'">
                <img src="<?= $qrUrl ?>" class="qr-code">
            </div>
            <div class="data-side">
                <div class="info-row">
                    <span class="label">الاسم الكامل</span>
                    <span class="value name"><?= clean($person['full_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">رقم الهوية</span>
                    <span class="value"><?= clean($person['id_number']) ?: '—' ?></span>
                </div>
                <div class="info-row">
                    <span class="label">المسمى الوظيفي</span>
                    <span class="value"><?= clean($person['job_title']) ?: '—' ?></span>
                </div>
                <div class="info-row">
                    <span class="label">المحافظة</span>
                    <span class="value"><?= clean($person['governorate']) ?: '—' ?></span>
                </div>
            </div>
        </div>

        <div class="card-footer">
            هذه البطاقة رسمية وتستخدم لأغراض التعريف بالنظام فقط
        </div>
    </div>

</body>
</html>
