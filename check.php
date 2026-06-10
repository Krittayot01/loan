<?php
// check.php - Track Loan Application Status
require_once __DIR__ . '/db.php';
session_start();

$error = '';
$loan = null;
$reviews = [];
$guarantors = [];

if (isset($_POST['search'])) {
    $citizen_id = trim($_POST['citizen_id'] ?? '');
    $member_no = trim($_POST['member_no'] ?? '');
    
    if (empty($citizen_id) || empty($member_no)) {
        $error = 'กรุณากรอกเลขประจำตัวประชาชนและเลขทะเบียนสมาชิกให้ครบถ้วน';
    } else {
        try {
            // Find application
            $stmt = $db->prepare("SELECT * FROM loans WHERE citizen_id = ? AND member_no = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$citizen_id, $member_no]);
            $loan = $stmt->fetch();
            
            if (!$loan) {
                $error = 'ไม่พบข้อมูลคำขอกู้เงินที่ตรงกับเงื่อนไขที่ระบุ โปรดตรวจสอบความถูกต้อง';
            } else {
                // Get review history
                $stmt = $db->prepare("SELECT * FROM loan_reviews WHERE loan_id = ? ORDER BY id ASC");
                $stmt->execute([$loan['id']]);
                $reviews = $stmt->fetchAll();
                
                // Get guarantors
                $stmt = $db->prepare("SELECT name, member_no, guarantee_amount FROM guarantors WHERE loan_id = ?");
                $stmt->execute([$loan['id']]);
                $guarantors = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในการเชื่อมต่อข้อมูล: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะคำขอกู้เงิน | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
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
                <span>ระบบยื่นคำขอกู้เงินสามัญทั่วไปออนไลน์</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">หน้าแรก</a></li>
                <li><a href="apply.php">ยื่นคำขอกู้เงิน</a></li>
                <li><a href="check.php" class="active">ติดตามสถานะ</a></li>
                <li><a href="admin.php">สำหรับเจ้าหน้าที่</a></li>
                <?php if (isset($_SESSION['member_no'])): ?>
                    <li><a href="login.php?logout=1" style="color: #fca5a5;">ออกจากระบบ (<?= htmlspecialchars($_SESSION['member_no']) ?>) 🚪</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="status-container">
        <div class="card">
            <h3 style="text-align: center; margin-bottom: 1.5rem;">🔎 ติดตามสถานะคำขอกู้เงิน</h3>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="check.php" method="POST">
                <div class="form-group">
                    <label for="member_no">เลขทะเบียนสมาชิก</label>
                    <input type="text" id="member_no" name="member_no" class="form-control" required placeholder="ตัวอย่าง: 00009" value="<?= htmlspecialchars($_POST['member_no'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="citizen_id">เลขประจำตัวประชาชน (13 หลัก)</label>
                    <input type="text" id="citizen_id" name="citizen_id" class="form-control" required placeholder="กรอกเลขบัตรประชาชน 13 หลัก" maxlength="13" value="<?= htmlspecialchars($_POST['citizen_id'] ?? '') ?>">
                </div>
                
                <button type="submit" name="search" class="btn btn-primary" style="width: 100%;">ค้นหาข้อมูลคำขอ</button>
            </form>
        </div>
    </div>

    <?php if ($loan): ?>
        <div class="card gold-border">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="margin-bottom: 0.25rem;">ข้อมูลคำขอกู้เงินเลขที่: <span style="color: var(--secondary-hover);"><?= htmlspecialchars($loan['token']) ?></span></h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">ยื่นเมื่อวันที่: <?= date('d/m/Y H:i', strtotime($loan['created_at'])) ?> น.</p>
                </div>
                <div>
                    <?php
                    $status_class = 'pending';
                    if ($loan['status'] == 'อนุมัติ') $status_class = 'approved';
                    elseif ($loan['status'] == 'ไม่อนุมัติ') $status_class = 'rejected';
                    elseif ($loan['status'] != 'ยื่นคำขอแล้ว') $status_class = 'review';
                    ?>
                    <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($loan['status']) ?></span>
                </div>
            </div>

            <!-- Workflow Progress tracker UI -->
            <h4 style="margin-bottom: 1rem; font-family: var(--font-heading);">📊 ขั้นตอนความคืบหน้าการพิจารณา</h4>
            <div class="workflow-tracker">
                <?php
                $stages = [
                    'ยื่นคำขอแล้ว' => 'ยื่นคำขอแล้ว',
                    'ตรวจสอบเอกสาร' => 'ตรวจเอกสาร',
                    'เสนอผู้บังคับบัญชา' => 'ผู้บังคับบัญชา',
                    'เสนอฝ่ายสินเชื่อ' => 'ฝ่ายสินเชื่อ',
                    'เสนอผู้จัดการ' => 'ผู้จัดการ',
                    'เสนอคณะกรรมการเงินกู้' => 'กมธ. เงินกู้',
                    'เสนอคณะกรรมการดำเนินการ' => 'กมธ. ดำเนินการ',
                    'อนุมัติ' => 'เสร็จสิ้น'
                ];
                
                // Determine step index
                $stage_keys = array_keys($stages);
                $current_idx = array_search($loan['status'], $stage_keys);
                
                if ($loan['status'] == 'ไม่อนุมัติ') {
                    $current_idx = 7; // Show final step as rejected
                }
                
                $i = 0;
                foreach ($stages as $key => $label):
                    $class = '';
                    if ($loan['status'] == 'ไม่อนุมัติ' && $i == 7) {
                        $class = 'rejected';
                    } elseif ($i < $current_idx) {
                        $class = 'completed';
                    } elseif ($i == $current_idx) {
                        $class = 'active';
                    }
                ?>
                    <div class="workflow-step <?= $class ?>">
                        <div class="workflow-icon"><?= ($class == 'completed') ? '✓' : ($i + 1) ?></div>
                        <div class="workflow-name"><?= $label ?></div>
                    </div>
                <?php 
                    $i++;
                endforeach; 
                ?>
            </div>

            <!-- Borrower Summary -->
            <div style="margin-top: 2rem;">
                <h4 style="margin-bottom: 1rem; font-family: var(--font-heading); border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">📝 รายละเอียดเบื้องต้น</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; font-size: 0.95rem;">
                    <div><strong>ผู้ยื่นคำขอ:</strong> <?= htmlspecialchars($loan['title'] . $loan['name']) ?></div>
                    <div><strong>เลขทะเบียนสมาชิก:</strong> <?= htmlspecialchars($loan['member_no']) ?></div>
                    <div><strong>วงเงินที่ขอกู้:</strong> <span style="font-weight: 700; color: var(--primary-color);"><?= number_format($loan['loan_amount'], 2) ?> บาท</span></div>
                    <div><strong>วัตถุประสงค์เพื่อ:</strong> <?= htmlspecialchars($loan['loan_purpose']) ?></div>
                    <div><strong>จำนวนงวดชำระ:</strong> <?= htmlspecialchars($loan['repayment_installments']) ?> งวด (ผ่อนเดือนละประมาณ <?= number_format($loan['repayment_amount'], 2) ?> บาท)</div>
                    <div>
                        <strong>ผู้ค้ำประกัน (<?= count($guarantors) ?> ท่าน):</strong>
                        <?php if (empty($guarantors)): ?>
                            -
                        <?php else: ?>
                            <ul style="padding-left: 1.25rem; margin-top: 0.25rem;">
                                <?php foreach ($guarantors as $g): ?>
                                    <li><?= htmlspecialchars($g['name']) ?> (ทะเบียน: <?= htmlspecialchars($g['member_no']) ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Review History -->
            <div style="margin-top: 2rem;">
                <h4 style="margin-bottom: 0.5rem; font-family: var(--font-heading); border-bottom: 1px solid var(--border-color); padding-bottom: 0.25rem;">💬 ความคิดเห็นและผลการพิจารณา</h4>
                <?php if (empty($reviews)): ?>
                    <p style="color: var(--text-secondary); font-style: italic; font-size: 0.9rem; margin-top: 0.5rem;">ยังไม่มีบันทึกความคิดเห็นจากเจ้าหน้าที่ในปัจจุบัน</p>
                <?php else: ?>
                    <div class="history-timeline">
                        <?php foreach ($reviews as $r): 
                            $role_name = '';
                            switch($r['reviewer_role']) {
                                case 'credit_officer': $role_name = 'เจ้าหน้าที่สินเชื่อ/ผู้บันทึกคำขอกู้'; break;
                                case 'credit_head': $role_name = 'หัวหน้าฝ่ายสินเชื่อ/ผู้ตรวจสอบคำขอ'; break;
                                case 'assistant_manager': $role_name = 'รองผู้จัดการ/ผู้ตรวจทาน'; break;
                                case 'manager': $role_name = 'ผู้จัดการ'; break;
                                case 'loan_committee': $role_name = 'คณะกรรมการเงินกู้'; break;
                                case 'board': $role_name = 'คณะกรรมการดำเนินการ'; break;
                            }
                            
                            $decision_class = 'approved';
                            $decision_text = 'ผ่านขั้นตอน';
                            if ($r['decision'] == 'rejected') {
                                $decision_class = 'rejected';
                                $decision_text = 'ไม่ผ่าน';
                            }
                        ?>
                            <div class="timeline-item <?= $decision_class ?>">
                                <div class="timeline-header">
                                    <span><?= htmlspecialchars($role_name) ?> (<?= htmlspecialchars($r['reviewer_name']) ?>)</span>
                                    <span class="timeline-time"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?> น.</span>
                                </div>
                                <div class="timeline-content">
                                    <span class="status-badge <?= $decision_class ?>" style="font-size: 0.75rem; padding: 0.1rem 0.5rem;"><?= $decision_text ?></span>
                                    <?php if ($r['approved_amount'] > 0): ?>
                                        <strong style="margin-left: 0.5rem; color: var(--primary-color);">วงเงินที่เห็นควร: <?= number_format($r['approved_amount'], 2) ?> บาท</strong>
                                    <?php endif; ?>
                                    <p style="margin-top: 0.25rem; font-style: italic;">"<?= htmlspecialchars($r['comments'] ? $r['comments'] : 'ไม่มีความเห็นเพิ่มเติม') ?>"</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="print_form.php?token=<?= urlencode($loan['token']) ?>" target="_blank" class="btn btn-secondary">🖨️ พิมพ์แบบฟอร์มกู้เงิน (สม-๐๑ ถึง สม-๐๗)</a>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>

</body>
</html>
