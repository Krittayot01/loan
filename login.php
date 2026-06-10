<?php
// login.php - Member Login Page
require_once __DIR__ . '/db.php';
session_start();

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['member_no']);
    unset($_SESSION['member_data']);
    header('Location: login.php');
    exit;
}

$error = '';

// Check if already logged in, redirect to apply.php
if (isset($_SESSION['member_no'])) {
    header('Location: apply.php');
    exit;
}

if (isset($_POST['login'])) {
    $member_no = trim($_POST['member_no'] ?? '');
    
    if (empty($member_no)) {
        $error = 'กรุณากรอกเลขทะเบียนสมาชิกของคุณ';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM members WHERE member_no = ?");
            $stmt->execute([$member_no]);
            $member = $stmt->fetch();
            
            if ($member) {
                $_SESSION['member_no'] = $member['member_no'];
                $_SESSION['member_data'] = $member;
                
                header('Location: apply.php');
                exit;
            } else {
                $error = 'ไม่พบเลขทะเบียนสมาชิกนี้ในระบบ กรุณาตรวจสอบอีกครั้ง (ทดลองพิมพ์: 00009 หรือ 00006)';
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบสมาชิก | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 4rem auto;
        }
    </style>
</head>
<body>

<header>
    <div class="nav-container">
        <div class="logo-section">
            <svg class="logo-img" viewBox="0 0 100 100" width="50" height="50" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="45" fill="#0d233a" stroke="#d4af37" stroke-width="3"/>
                <path d="M50 15 L80 35 L80 65 L50 85 L20 65 L20 35 Z" fill="#163656" stroke="#d4af37" stroke-width="2"/>
                <line x1="35" y1="45" x2="65" y2="45" stroke="#d4af37" stroke-width="3"/>
                <line x1="50" y1="35" x2="50" y2="70" stroke="#d4af37" stroke-width="3"/>
                <line x1="40" y1="70" x2="60" y2="70" stroke="#d4af37" stroke-width="2"/>
            </svg>
            <div class="logo-text">
                <h1>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</h1>
                <span>ระบบยื่นคำขอกู้เงินสามัญทั่วไปออนไลน์</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">หน้าแรก</a></li>
                <li><a href="apply.php">ยื่นคำขอกู้เงิน</a></li>
                <li><a href="check.php">ติดตามสถานะ</a></li>
                <li><a href="admin.php">สำหรับเจ้าหน้าที่</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="login-container">
        <div class="card gold-border">
            <h3 style="text-align: center; margin-bottom: 1rem; font-family: var(--font-heading);">🔓 เข้าสู่ระบบสมาชิก</h3>
            <p style="text-align: center; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                กรอกเลขทะเบียนสมาชิกของคุณเพื่อความสะดวกรวดเร็ว ระบบจะทำการดึงข้อมูลส่วนตัวและฐานเงินเดือนของคุณมาใส่ในคำขอกู้เงินโดยอัตโนมัติ
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="font-size: 0.85rem; padding: 0.75rem 1rem;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="member_no">เลขทะเบียนสมาชิก</label>
                    <input type="text" id="member_no" name="member_no" class="form-control" placeholder="เช่น 00009 หรือ 00006" required autofocus>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">เข้าสู่ระบบสมาชิก</button>
            </form>

            <div style="margin-top: 1.5rem; background-color: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm);">
                <strong style="font-size: 0.85rem; color: var(--primary-color);">💡 ข้อมูลสำหรับทดสอบ (Demo Members):</strong>
                <ul style="font-size: 0.8rem; color: var(--text-secondary); padding-left: 1.25rem; margin-top: 5px; line-height: 1.6;">
                    <li>พิมพ์รหัส <strong>00009</strong>: นายวีรยุทธ สุวรรณโมสิ (สภ.เมืองสงขลา)</li>
                    <li>พิมพ์รหัส <strong>00006</strong>: นางสาวจีราพร ปิยะพงศ์ (ภ.จว.สงขลา)</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>

</body>
</html>
