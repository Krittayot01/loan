<?php
// admin.php - Admin Login and Dashboard
require_once __DIR__ . '/db.php';
session_start();

// Demo Authentication Bypass
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = 'ผู้บริหารระบบ (Demo)';
    } else {
        $login_error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    unset($_SESSION['admin_user']);
    header('Location: admin.php');
    exit;
}

$is_logged = $_SESSION['admin_logged'] ?? false;

// If not logged in, show login screen
if (!$is_logged):
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบสำหรับเจ้าหน้าที่ | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-box {
            max-width: 400px;
            margin: 5rem auto;
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
                <li><a href="index.php">หน้าแรก</a></li>
                <li><a href="apply.php">ยื่นคำขอกู้เงิน</a></li>
                <li><a href="check.php">ติดตามสถานะ</a></li>
                <li><a href="admin.php" class="active">สำหรับเจ้าหน้าที่</a></li>
            </ul>
        </nav>
    </div>
</header>
<main>
    <div class="login-box">
        <div class="card gold-border">
            <h3 style="text-align: center; margin-bottom: 1.5rem;">🔒 เข้าสู่ระบบผู้พิจารณาอนุมัติ</h3>
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger"><?= $login_error ?></div>
            <?php endif; ?>
            <form action="admin.php" method="POST">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" id="username" name="username" class="form-control" value="admin" required>
                </div>
                <div class="form-group">
                    <label for="password">รหัสผ่าน (Password)</label>
                    <input type="password" id="password" name="password" class="form-control" value="admin123" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">เข้าสู่ระบบ</button>
            </form>
            <div style="margin-top: 1rem; font-size: 0.8rem; text-align: center; color: var(--text-secondary);">
                * สำหรับทดสอบ: กรอก admin / admin123
            </div>
        </div>
    </div>
</main>
<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>
</body>
</html>
<?php 
exit; 
endif; 

// If logged in, display admin dashboard
// Get metrics
$total_loans = $db->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$pending_docs = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'ยื่นคำขอแล้ว' OR status = 'ตรวจสอบเอกสาร'")->fetchColumn();
$approved_loans = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'อนุมัติ'")->fetchColumn();
$rejected_loans = $db->query("SELECT COUNT(*) FROM loans WHERE status = 'ไม่อนุมัติ'")->fetchColumn();

// Get status filter
$filter = $_GET['status'] ?? 'all';
$query = "SELECT * FROM loans";
$params = [];

if ($filter === 'pending') {
    $query .= " WHERE status NOT IN ('อนุมัติ', 'ไม่อนุมัติ')";
} elseif ($filter === 'approved') {
    $query .= " WHERE status = 'อนุมัติ'";
} elseif ($filter === 'rejected') {
    $query .= " WHERE status = 'ไม่อนุมัติ'";
}

$query .= " ORDER BY id DESC";
$loans = $db->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดเจ้าหน้าที่สหกรณ์ | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                <path d="M30 45 L35 60 L40 45 Z" fill="none" stroke="#d4af37" stroke-width="1.5"/>
                <path d="M60 45 L65 60 L70 45 Z" fill="none" stroke="#d4af37" stroke-width="1.5"/>
            </svg>
            <div class="logo-text">
                <h1>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</h1>
                <span>แดชบอร์ดเจ้าหน้าที่ควบคุมระบบ</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">หน้าหลักเว็บ</a></li>
                <li><a href="admin.php" class="active">แดชบอร์ดอนุมัติ</a></li>
                <li><a href="admin.php?logout=1" style="color: #fca5a5;">ออกจากระบบ 🚪</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2>📊 รายการคำขอกู้เงินสามัญทั่วไป</h2>
            <p style="color: var(--text-secondary);">ยินดีต้อนรับคุณ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></p>
        </div>
        <span style="font-size: 0.9rem; color: var(--text-secondary);">วันที่ปัจจุบัน: <?= date('d/m/Y') ?></span>
    </div>

    <!-- METRIC CARDS -->
    <div class="metrics-grid">
        <a href="admin.php?status=all" class="metric-card border-blue" style="text-decoration: none; color: inherit;">
            <div class="metric-info">
                <h4>คำขอกู้ทั้งหมด</h4>
                <div class="metric-value"><?= $total_loans ?></div>
            </div>
            <div class="metric-icon">📁</div>
        </a>
        <a href="admin.php?status=pending" class="metric-card border-yellow" style="text-decoration: none; color: inherit;">
            <div class="metric-info">
                <h4>อยู่ระหว่างพิจารณา</h4>
                <div class="metric-value"><?= $pending_docs ?></div>
            </div>
            <div class="metric-icon">⏳</div>
        </a>
        <a href="admin.php?status=approved" class="metric-card border-green" style="text-decoration: none; color: inherit;">
            <div class="metric-info">
                <h4>อนุมัติแล้ว</h4>
                <div class="metric-value"><?= $approved_loans ?></div>
            </div>
            <div class="metric-icon">✓</div>
        </a>
        <a href="admin.php?status=rejected" class="metric-card border-red" style="text-decoration: none; color: inherit;">
            <div class="metric-info">
                <h4>ไม่อนุมัติ</h4>
                <div class="metric-value"><?= $rejected_loans ?></div>
            </div>
            <div class="metric-icon">✗</div>
        </a>
    </div>

    <!-- FILTERS BAR -->
    <div style="background-color: #f8fafc; border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <strong>กรองรายการ:</strong>
        <a href="admin.php?status=all" class="btn <?= $filter == 'all' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 0.4rem 1rem; font-size: 0.9rem;">ทั้งหมด</a>
        <a href="admin.php?status=pending" class="btn <?= $filter == 'pending' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 0.4rem 1rem; font-size: 0.9rem;">กำลังพิจารณา (In-Progress)</a>
        <a href="admin.php?status=approved" class="btn <?= $filter == 'approved' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 0.4rem 1rem; font-size: 0.9rem;">อนุมัติแล้ว</a>
        <a href="admin.php?status=rejected" class="btn <?= $filter == 'rejected' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 0.4rem 1rem; font-size: 0.9rem;">ไม่อนุมัติ</a>
    </div>

    <!-- LOANS TABLE -->
    <div class="card" style="padding: 0; overflow: hidden; border-top: 4px solid var(--primary-color);">
        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>เลขที่ใบสมัคร</th>
                        <th>สมาชิกผู้กู้</th>
                        <th>ทะเบียนสมาชิก</th>
                        <th>วงเงินกู้ (บาท)</th>
                        <th>งวดชำระ</th>
                        <th>วันที่ส่งคำขอ</th>
                        <th>ขั้นตอน/สถานะ</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($loans)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-secondary); font-style: italic; padding: 2rem;">ไม่พบข้อมูลรายการคำขอกู้เงินในระบบ</td>
                        </tr>
                    <?php else: 
                        foreach ($loans as $l):
                            $status_class = 'pending';
                            if ($l['status'] == 'อนุมัติ') $status_class = 'approved';
                            elseif ($l['status'] == 'ไม่อนุมัติ') $status_class = 'rejected';
                            elseif ($l['status'] != 'ยื่นคำขอแล้ว') $status_class = 'review';
                    ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary-color);"><?= htmlspecialchars($l['token']) ?></td>
                            <td><?= htmlspecialchars($l['title'] . $l['name']) ?></td>
                            <td><?= htmlspecialchars($l['member_no']) ?></td>
                            <td style="font-weight: 600;"><?= number_format($l['loan_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($l['repayment_installments']) ?> งวด</td>
                            <td><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                            <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($l['status']) ?></span></td>
                            <td class="table-actions">
                                <a href="admin_view.php?id=<?= $l['id'] ?>" class="btn btn-primary btn-xs">🔍 ตรวจสอบอนุมัติ</a>
                                <a href="print_form.php?token=<?= urlencode($l['token']) ?>" target="_blank" class="btn btn-outline btn-xs">🖨️ พิมพ์ฟอร์ม</a>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>

</body>
</html>
