<?php
// index.php - Welcome Page for Loan System
require_once __DIR__ . '/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยื่นคำขอกู้เงินออนไลน์ | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
    <div class="nav-container">
        <div class="logo-section">
            <!-- SVG Logo for Cooperative (Police + scales of justice / path theme) -->
            <svg class="logo-img" viewBox="0 0 100 100" width="50" height="50" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="45" fill="#0d233a" stroke="#d4af37" stroke-width="3"/>
                <path d="M50 15 L80 35 L80 65 L50 85 L20 65 L20 35 Z" fill="#163656" stroke="#d4af37" stroke-width="2"/>
                <!-- Scale of justice -->
                <line x1="35" y1="45" x2="65" y2="45" stroke="#d4af37" stroke-width="3"/>
                <line x1="50" y1="35" x2="50" y2="70" stroke="#d4af37" stroke-width="3"/>
                <line x1="40" y1="70" x2="60" y2="70" stroke="#d4af37" stroke-width="2"/>
                <path d="M30 45 L35 60 L40 45 Z" fill="none" stroke="#d4af37" stroke-width="1.5"/>
                <path d="M60 45 L65 60 L70 45 Z" fill="none" stroke="#d4af37" stroke-width="1.5"/>
            </svg>
            <div class="logo-text">
                <h1>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</h1>
                <span>ระบบยื่นคำขอกู้เงินสามัญทั่วไปออนไลน์</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="active">หน้าแรก</a></li>
                <li><a href="apply.php">ยื่นคำขอกู้เงิน</a></li>
                <li><a href="check.php">ติดตามสถานะ</a></li>
                <li><a href="admin.php">สำหรับเจ้าหน้าที่</a></li>
                <?php if (isset($_SESSION['member_no'])): ?>
                    <li><a href="login.php?logout=1" style="color: #fca5a5;">ออกจากระบบ (<?= htmlspecialchars($_SESSION['member_no']) ?>) 🚪</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="welcome-hero">
        <?php if (isset($_SESSION['member_no'])): ?>
            <h2>สวัสดีคุณ <?= htmlspecialchars($_SESSION['member_data']['name']) ?> (สมาชิกเลขที่ <?= htmlspecialchars($_SESSION['member_no']) ?>)</h2>
            <p>ระบบพร้อมดึงข้อมูลส่วนตัวและอัตราเงินเดือนของคุณเรียบร้อยแล้ว สามารถกดปุ่มด้านล่างเพื่อเข้าสู่ขั้นตอนการยื่นขอกู้เงินได้ทันที</p>
            <div class="cta-group">
                <a href="apply.php" class="btn btn-secondary">📝 ไปยังฟอร์มยื่นคำขอกู้เงิน</a>
                <a href="check.php" class="btn btn-outline">ติดตามสถานะการขอกู้</a>
            </div>
        <?php else: ?>
            <h2>ยินดีต้อนรับสู่ระบบยื่นคำขอกู้เงินออนไลน์</h2>
            <p>บริการยื่นคำขอกู้เงินสามัญทั่วไปของสมาชิก สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด สะดวด รวดเร็ว ปลอดภัย และสามารถติดตามขั้นตอนการดำเนินงานได้ตลอด 24 ชั่วโมง</p>
            <div class="cta-group">
                <a href="login.php" class="btn btn-secondary">เข้าสู่ระบบสมาชิกเพื่อยื่นกู้</a>
                <a href="check.php" class="btn btn-outline">ติดตามสถานะการขอกู้</a>
            </div>
        <?php endif; ?>
    </div>

    <h2 style="text-align: center; margin-bottom: 2rem;">ขั้นตอนการยื่นคำขอกู้เงินสามัญออนไลน์</h2>
    
    <div class="features-grid">
        <div class="feature-box">
            <div class="feature-icon">1</div>
            <h3>กรอกข้อมูลส่วนตัว & ข้อมูลเงินกู้</h3>
            <p>บันทึกประวัติการทำงาน สังกัด อัตราเงินเดือน จำนวนเงินกู้ที่ต้องการ และคำนวณเงินงวดชำระเบื้องต้น</p>
        </div>
        
        <div class="feature-box">
            <div class="feature-icon">2</div>
            <h3>ระบุผู้ค้ำประกัน</h3>
            <p>กรอกข้อมูลสมาชิกผู้ค้ำประกัน (ค้ำประกันได้สูงสุด 6 ท่านตามเกณฑ์ที่สหกรณ์กำหนด)</p>
        </div>
        
        <div class="feature-box">
            <div class="feature-icon">3</div>
            <h3>อัปโหลดเอกสาร & เซ็นชื่อ</h3>
            <p>แนบเอกสารหลักฐาน เช่น บัตรประชาชน ทะเบียนบ้าน สลิปเงินเดือน และวาดลายเซ็นดิจิทัลผ่านหน้าเว็บ</p>
        </div>
        
        <div class="feature-box">
            <div class="feature-icon">4</div>
            <h3>พิมพ์เอกสารประกอบ</h3>
            <p>หลังจากเจ้าหน้าที่ตรวจสอบและอนุมัติ สามารถจัดพิมพ์แบบฟอร์มทางการ (สม-๐๑ ถึง สม-๐๗) ออกมาเสนอลงนามจริงได้</p>
        </div>
    </div>

    <div class="card gold-border" style="margin-top: 3rem; background-color: #fcfbf7;">
        <h3 style="margin-bottom: 1rem; color: #b28e20;">📢 คำแนะนำและข้อกำหนดการยื่นกู้</h3>
        <ul style="padding-left: 1.5rem; line-height: 1.8; color: var(--text-secondary);">
            <li><strong>กำหนดเวลาการยื่นคำขอ:</strong> ยื่นภายในวันที่ 7 ของเดือน จะได้รับการพิจารณาจ่ายเงินภายในวันที่ 15 หรือยื่นภายในวันที่ 20 เพื่อรับเงินก่อนสิ้นเดือนอย่างน้อย 2 วันทำการ</li>
            <li><strong>วงเงินกู้ไม่เกิน 2,000,000 บาท:</strong> สามารถยื่นผ่านระบบและแนบรูปถ่ายการเซ็นสัญญากู้ที่บ้านได้</li>
            <li><strong>วงเงินกู้เกิน 2,000,000 บาท:</strong> ผู้กู้ต้องทำหนังสือสัญญาเงินกู้ต่อหน้าเจ้าหน้าที่ ณ สำนักงานสหกรณ์ตำรวจสงขลา จำกัด</li>
            <li><strong>เอกสารที่ต้องเตรียมสแกน/ถ่ายรูป:</strong> บัตรประชาชนผู้กู้/คู่สมรส, ทะเบียนบ้าน, ทะเบียนสมรส, สลิปเงินเดือน 3 เดือนล่าสุด, และผลตรวจเครดิตบูโร (NCB)</li>
        </ul>
    </div>
</main>

<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>

</body>
</html>
