<?php
// admin_view.php - Detailed Application View & Review
require_once __DIR__ . '/db.php';
session_start();

// Check authentication
if (!($_SESSION['admin_logged'] ?? false)) {
    header('Location: admin.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ไม่ระบุหมายเลขคำขอกู้เงิน");
}

try {
    // 1. Fetch Loan Details
    $stmt = $db->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$id]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        die("ไม่พบคำขอกู้เงินหมายเลขที่ระบุ");
    }
    
    // 2. Fetch Guarantors
    $stmt = $db->prepare("SELECT * FROM guarantors WHERE loan_id = ?");
    $stmt->execute([$id]);
    $guarantors = $stmt->fetchAll();
    
    // 3. Fetch Documents
    $stmt = $db->prepare("SELECT * FROM loan_documents WHERE loan_id = ?");
    $stmt->execute([$id]);
    $docs = $stmt->fetchAll();
    
    // 4. Fetch Reviews
    $stmt = $db->prepare("SELECT * FROM loan_reviews WHERE loan_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $reviews = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Success message
$msg = $_SESSION['review_success'] ?? '';
unset($_SESSION['review_success']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบคำขอกู้ยืม [<?= htmlspecialchars($loan['token']) ?>] | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .grid-detail-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            border-bottom: 1px solid #f1f5f9;
            padding: 8px 0;
            font-size: 0.95rem;
        }
        .grid-detail-label {
            font-weight: 600;
            color: var(--primary-color);
            font-family: var(--font-heading);
        }
        .doc-thumbnail {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        .sig-img {
            max-height: 80px;
            max-width: 250px;
            border-bottom: 1px dashed #cbd5e1;
            padding: 5px;
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
                <span>ระบบอนุมัติคำขอกู้สามัญออนไลน์</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="admin.php">กลับหน้าแดชบอร์ด 📊</a></li>
                <li><a href="print_form.php?token=<?= urlencode($loan['token']) ?>" target="_blank">🖨️ พิมพ์เอกสารทั้งหมด</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2>ตรวจสอบรายละเอียดคำขอกู้เงิน: <?= htmlspecialchars($loan['token']) ?></h2>
            <p>ยื่นคำขอเมื่อ: <?= date('d/m/Y H:i', strtotime($loan['created_at'])) ?> น.</p>
        </div>
        <div>
            <?php
            $status_class = 'pending';
            if ($loan['status'] == 'อนุมัติ') $status_class = 'approved';
            elseif ($loan['status'] == 'ไม่อนุมัติ') $status_class = 'rejected';
            elseif ($loan['status'] != 'ยื่นคำขอแล้ว') $status_class = 'review';
            ?>
            <span class="status-badge <?= $status_class ?>" style="font-size: 1.1rem; padding: 0.5rem 1.25rem;"><?= htmlspecialchars($loan['status']) ?></span>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Workflow Progress tracker UI -->
    <div class="card" style="border-top: 4px solid var(--secondary-color);">
        <h4 style="margin-bottom: 1rem; font-family: var(--font-heading);">📊 ลำดับขั้นตอนการพิจารณา (Workflow Status)</h4>
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
                'อนุมัติ' => 'อนุมัติเสร็จสิ้น'
            ];
            
            $stage_keys = array_keys($stages);
            $current_idx = array_search($loan['status'], $stage_keys);
            
            if ($loan['status'] == 'ไม่อนุมัติ') {
                $current_idx = 7;
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
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- LEFT: Detail Content -->
        <div>
            <!-- Section 1: ข้อมูลผู้กู้ -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.25rem;">👤 ข้อมูลผู้สมัครขอกู้</h3>
                <div class="grid-detail-row"><span class="grid-detail-label">ชื่อ-นามสกุล</span><span><?= htmlspecialchars($loan['title'] . $loan['name']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">ทะเบียนสมาชิก</span><span><?= htmlspecialchars($loan['member_no']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">เลขบัตรประชาชน</span><span><?= htmlspecialchars($loan['citizen_id']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">อายุ (ปี) / วันเกิด</span><span><?= htmlspecialchars($loan['age']) ?> ปี (วันเกิด: <?= date('d/m/Y', strtotime($loan['dob'])) ?>)</span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">สถานะการทำงาน</span><span><?= htmlspecialchars($loan['work_status']) ?> <?= $loan['work_status_other'] ? '('.htmlspecialchars($loan['work_status_other']).')' : '' ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">ตำแหน่ง / สังกัด</span><span><?= htmlspecialchars($loan['position'] ? $loan['position'] : '-') ?> / <?= htmlspecialchars($loan['affiliation'] ? $loan['affiliation'] : '-') ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">อัตราเงินเดือน</span><span><?= number_format($loan['salary'], 2) ?> บาท</span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">ที่อยู่ปัจจุบัน</span><span><?= htmlspecialchars($loan['address']) ?> รหัสไปรษณีย์ <?= htmlspecialchars($loan['postal_code']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">เบอร์โทรศัพท์</span><span><?= htmlspecialchars($loan['mobile']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">สถานภาพการสมรส</span><span><?= htmlspecialchars($loan['marital_status']) ?> <?= $loan['spouse_name'] ? '(คู่สมรส: '.htmlspecialchars($loan['spouse_name']).')' : '' ?></span></div>
            </div>

            <!-- Section 2: ข้อมูลวงเงินกู้ -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.25rem;">💰 รายละเอียดวงเงินกู้ที่เสนอ</h3>
                <div class="grid-detail-row"><span class="grid-detail-label">จำนวนเงินขอกู้</span><span style="font-weight: 700; font-size: 1.15rem; color: var(--primary-color);"><?= number_format($loan['loan_amount'], 2) ?> บาท</span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">จำนวนเงินตัวเขียน</span><span><?= htmlspecialchars($loan['loan_amount_text']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">วัตถุประสงค์เพื่อ</span><span><?= htmlspecialchars($loan['loan_purpose']) ?></span></div>
                <div class="grid-detail-row"><span class="grid-detail-label">ระยะเวลาผ่อนชำระ</span><span><?= htmlspecialchars($loan['repayment_installments']) ?> งวด</span></div>
                <div class="grid-detail-row">
                    <span class="grid-detail-label">วิธีการผ่อนชำระ</span>
                    <span><?= ($loan['repayment_type'] == 1) ? 'ต้นเงินพร้อมดอกเบี้ยเท่ากันต่องวด' : 'ต้นเงินเท่ากันต่องวด บวกดอกเบี้ยยอดคงเหลือ' ?></span>
                </div>
                <div class="grid-detail-row"><span class="grid-detail-label">ค่างวดชำระรายเดือน</span><span style="font-weight: 700;"><?= number_format($loan['repayment_amount'], 2) ?> บาท/เดือน</span></div>
                <div class="grid-detail-row">
                    <span class="grid-detail-label">บัญชีรับเงินกู้</span>
                    <span><?= ($loan['receive_account_type'] == 1) ? 'บัญชีสหกรณ์ตำรวจสงขลา' : 'บัญชีธนาคารกรุงไทย' ?> (ชื่อบัญชี: <?= htmlspecialchars($loan['receive_account_name']) ?>, เลขบัญชี: <?= htmlspecialchars($loan['receive_account_no']) ?>)</span>
                </div>
                <div class="grid-detail-row"><span class="grid-detail-label">ขอซื้อหุ้นเพื่อกู้เพิ่ม</span><span><?= number_format($loan['shares_buy_amount'], 2) ?> บาท (สม-๐๔)</span></div>
                <div class="grid-detail-row">
                    <span class="grid-detail-label">ลายเซ็นดิจิทัลผู้กู้</span>
                    <span>
                        <?php if ($loan['borrower_signature']): ?>
                            <img src="<?= $loan['borrower_signature'] ?>" class="sig-img" alt="ลายมือชื่อผู้กู้">
                        <?php else: ?>
                            <span style="color: var(--danger-color); font-style: italic;">ไม่ได้ลงนามดิจิทัล</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Section 3: ข้อมูลผู้ค้ำประกัน -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.25rem;">👥 ข้อมูลผู้ค้ำประกัน (Guarantors)</h3>
                <?php if (empty($guarantors)): ?>
                    <p style="color: var(--text-secondary); font-style: italic; text-align: center; padding: 1rem;">ไม่มีรายชื่อผู้ค้ำประกันสำหรับคำขอนี้</p>
                <?php else: 
                    foreach ($guarantors as $index => $g):
                ?>
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; <?= ($index < count($guarantors)-1) ? 'border-bottom: 1px dashed var(--border-color);' : '' ?>">
                        <h4 style="color: var(--secondary-hover); font-size: 1rem; margin-bottom: 0.5rem;">👤 ผู้ค้ำประกันลำดับที่ <?= $index+1 ?>: <?= htmlspecialchars($g['name']) ?></h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px 20px; font-size: 0.9rem; margin-left: 10px;">
                            <div><strong>เลขทะเบียนสมาชิก:</strong> <?= htmlspecialchars($g['member_no'] ? $g['member_no'] : '-') ?></div>
                            <div><strong>เลขประจำตัวประชาชน:</strong> <?= htmlspecialchars($g['citizen_id'] ? $g['citizen_id'] : '-') ?></div>
                            <div><strong>อายุ:</strong> <?= htmlspecialchars($g['age'] ? $g['age'] : '-') ?> ปี</div>
                            <div><strong>เบอร์โทรศัพท์:</strong> <?= htmlspecialchars($g['mobile'] ? $g['mobile'] : '-') ?></div>
                            <div><strong>ตำแหน่ง / สังกัด:</strong> <?= htmlspecialchars($g['position'] ? $g['position'] : '-') ?> / <?= htmlspecialchars($g['affiliation'] ? $g['affiliation'] : '-') ?></div>
                            <div><strong>เงินเดือน / วงเงินค้ำ:</strong> <?= number_format($g['salary'], 2) ?> / <?= number_format($g['guarantee_amount'], 2) ?> บาท</div>
                            <div style="grid-column: span 2; margin-top: 5px;">
                                <strong>ลายเซ็นผู้ค้ำ:</strong> 
                                <?php if ($g['signature_data']): ?>
                                    <img src="<?= $g['signature_data'] ?>" class="sig-img" style="max-height: 50px;" alt="ลายเซ็นผู้ค้ำ">
                                <?php else: ?>
                                    <span style="color: var(--danger-color); font-style: italic; font-size: 0.85rem;">ไม่ได้ลงนาม</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>

        <!-- RIGHT: Documents & Reviews -->
        <div>
            <!-- Section 4: เอกสารและหลักฐานภาพถ่าย -->
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.25rem;">📁 เอกสารหลักฐานประกอบ</h3>
                <div class="document-grid" style="grid-template-columns: 1fr;">
                    <?php if (empty($docs)): ?>
                        <p style="color: var(--text-secondary); font-style: italic; text-align: center;">ไม่มีไฟล์เอกสารแนบ</p>
                    <?php else: 
                        foreach ($docs as $d):
                            $title_name = '';
                            switch($d['doc_type']) {
                                case 'id_card': $title_name = 'สำเนาบัตรประชาชนผู้กู้'; break;
                                case 'house_reg': $title_name = 'สำเนาทะเบียนบ้านผู้กู้'; break;
                                case 'payslip': $title_name = 'เอกสารหลักฐานเงินเดือน'; break;
                                case 'ncb_file': case 'ncb': $title_name = 'ผลตรวจบูโร (NCB)'; break;
                                case 'passbook': $title_name = 'หน้าสมุดบัญชีธนาคาร'; break;
                                case 'photo_sign_1': $title_name = 'รูปผู้กู้ขณะลงลายมือชื่อ'; break;
                                case 'photo_sign_2': $title_name = 'รูปผู้กู้คู่กับสัญญา'; break;
                                case 'spouse_consent': $title_name = 'ใบยินยอมคู่สมรส (สม-๐๖)'; break;
                                default: $title_name = 'เอกสารอื่นๆ / แนบเพิ่ม'; break;
                            }
                            
                            $is_image = preg_match('/\.(jpg|jpeg|png|gif)$/i', $d['file_path']);
                    ?>
                        <div class="document-card" style="text-align: left; padding: 0.75rem; margin-bottom: 0.5rem;">
                            <div class="doc-name" title="<?= htmlspecialchars($d['file_name']) ?>"><?= $title_name ?></div>
                            <?php if ($is_image): ?>
                                <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($d['file_path']) ?>" class="doc-thumbnail" alt="<?= $title_name ?>">
                                </a>
                            <?php else: ?>
                                <div style="font-size: 0.8rem; color: var(--text-secondary); background: #e2e8f0; padding: 15px; text-align: center; border-radius: var(--radius-sm); margin: 5px 0;">
                                    📎 ไฟล์เอกสาร PDF
                                </div>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="doc-link">👁️ เปิดดูไฟล์เต็มรูปแบบ</a>
                        </div>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            </div>

            <!-- Section 5: ประวัติการประเมิน -->
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); border-bottom: 2px solid var(--primary-color); padding-bottom: 0.25rem;">💬 ความเห็นของผู้ตรวจทานก่อนหน้า</h3>
                <?php if (empty($reviews)): ?>
                    <p style="color: var(--text-secondary); font-style: italic; font-size: 0.9rem;">ยังไม่มีผู้ประเมินบันทึกความเห็น</p>
                <?php else: ?>
                    <div class="history-timeline" style="margin-top: 0;">
                        <?php foreach ($reviews as $r): 
                            $role_name = '';
                            switch($r['reviewer_role']) {
                                case 'credit_officer': $role_name = 'เจ้าหน้าที่สินเชื่อ'; break;
                                case 'credit_head': $role_name = 'หัวหน้าฝ่ายสินเชื่อ'; break;
                                case 'assistant_manager': $role_name = 'รองผู้จัดการ'; break;
                                case 'manager': $role_name = 'ผู้จัดการ'; break;
                                case 'loan_committee': $role_name = 'กมธ. เงินกู้'; break;
                                case 'board': $role_name = 'กมธ. ดำเนินการ'; break;
                            }
                            $decision_class = ($r['decision'] == 'approved') ? 'approved' : 'rejected';
                        ?>
                            <div class="timeline-item <?= $decision_class ?>" style="margin-bottom: 1rem;">
                                <div class="timeline-header">
                                    <strong><?= $role_name ?></strong>
                                    <span class="timeline-time" style="font-size: 0.75rem;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                    ผู้บันทึก: <?= htmlspecialchars($r['reviewer_name']) ?><br>
                                    ความเห็น: "<?= htmlspecialchars($r['comments'] ? $r['comments'] : 'ไม่มีความเห็นเพิ่มเติม') ?>"
                                    <?php if ($r['approved_amount'] > 0): ?>
                                        <br><strong>วงเงิน: <?= number_format($r['approved_amount'], 2) ?> บาท</strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 6: แบบฟอร์มอนุมัติสำหรับเจ้าหน้าที่ (Review Submission Form) -->
            <?php if ($loan['status'] !== 'อนุมัติ' && $loan['status'] !== 'ไม่อนุมัติ'): ?>
            <div class="card" style="padding: 1.5rem; border-top: 4px solid var(--primary-color);">
                <h3 style="margin-bottom: 1rem; font-family: var(--font-heading);">ลงนามพิจารณาและส่งต่อ</h3>
                
                <form action="submit_review.php" method="POST">
                    <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                    
                    <div class="form-group">
                        <label for="reviewer_role">บทบาทเจ้าหน้าที่ของคุณ <span style="color:var(--danger-color)">*</span></label>
                        <select id="reviewer_role" name="reviewer_role" class="form-control" required>
                            <!-- Auto select recommendation based on status -->
                            <option value="credit_officer" <?= ($loan['status'] == 'ยื่นคำขอแล้ว' || $loan['status'] == 'ตรวจสอบเอกสาร') ? 'selected' : '' ?>>1. เจ้าหน้าที่สินเชื่อ (ผู้บันทึกคำกู้)</option>
                            <option value="credit_head" <?= ($loan['status'] == 'เสนอผู้บังคับบัญชา') ? 'selected' : '' ?>>2. หัวหน้าฝ่ายสินเชื่อ (ผู้ตรวจสอบ)</option>
                            <option value="assistant_manager" <?= ($loan['status'] == 'เสนอฝ่ายสินเชื่อ') ? 'selected' : '' ?>>3. รองผู้จัดการ (ผู้ตรวจทาน)</option>
                            <option value="manager" <?= ($loan['status'] == 'เสนอผู้จัดการ') ? 'selected' : '' ?>>4. ผู้จัดการ (ผู้เสนอความเห็น)</option>
                            <option value="loan_committee" <?= ($loan['status'] == 'เสนอคณะกรรมการเงินกู้') ? 'selected' : '' ?>>5. คณะกรรมการเงินกู้ (ผู้ร่วมอนุมัติ)</option>
                            <option value="board" <?= ($loan['status'] == 'เสนอคณะกรรมการดำเนินการ') ? 'selected' : '' ?>>6. คณะกรรมการดำเนินการ (ผู้อนุมัติสิ้นสุด)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reviewer_name">ชื่อ-นามสกุล ผู้ตรวจสอบอนุมัติ <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="reviewer_name" name="reviewer_name" class="form-control" required placeholder="เช่น นายประจักษ์ ใจดี">
                    </div>

                    <div class="form-group">
                        <label>ผลการพิจารณา <span style="color:var(--danger-color)">*</span></label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            <label><input type="radio" name="decision" value="approved" checked> เห็นควรอนุมัติ / ผ่านขั้นตอนถัดไป</label>
                            <label><input type="radio" name="decision" value="rejected"> ไม่อนุมัติ / ยกเลิกคำขอเงินกู้</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="approved_amount">เสนอวงเงินอนุมัติ (บาท)</label>
                        <input type="number" id="approved_amount" name="approved_amount" class="form-control" value="<?= $loan['loan_amount'] ?>" placeholder="เสนอวงเงิน">
                    </div>

                    <!-- Display meeting notes ONLY for Committees -->
                    <div id="committeeMeetingFields" style="display:none; border: 1px dashed var(--secondary-color); padding: 10px; border-radius: var(--radius-sm); margin-bottom: 1rem; background-color: #fcfbf7;">
                        <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--secondary-hover);">📂 บันทึกมติการประชุม</h4>
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label style="font-size: 0.8rem;">มติชุดคณะกรรมการชุดที่</label>
                            <input type="text" name="meeting_set" class="form-control" style="padding: 4px 8px; font-size: 0.9rem;" placeholder="เช่น 15">
                        </div>
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label style="font-size: 0.8rem;">การประชุมครั้งที่</label>
                            <input type="text" name="meeting_no" class="form-control" style="padding: 4px 8px; font-size: 0.9rem;" placeholder="เช่น 3/2569">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem;">การประชุมเมื่อวันที่</label>
                            <input type="date" name="meeting_date" class="form-control" style="padding: 4px 8px; font-size: 0.9rem;">
                        </div>
                    </div>

                    <script>
                        const roleSelect = document.getElementById('reviewer_role');
                        const meetingFields = document.getElementById('committeeMeetingFields');
                        
                        function toggleMeetingFields() {
                            const val = roleSelect.value;
                            if (val === 'loan_committee' || val === 'board') {
                                meetingFields.style.display = 'block';
                            } else {
                                meetingFields.style.display = 'none';
                            }
                        }
                        
                        roleSelect.addEventListener('change', toggleMeetingFields);
                        toggleMeetingFields(); // initial check
                    </script>

                    <div class="form-group">
                        <label for="comments">ความคิดเห็นประกอบการพิจารณา</label>
                        <textarea id="comments" name="comments" class="form-control" rows="3" placeholder="ระบุเหตุผล ข้อสังเกต หรือหมายเหตุประกอบ"></textarea>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width: 100%;">บันทึกความคิดเห็น & ส่งต่อขั้นตอน</button>
                </form>
            </div>
            <?php else: ?>
                <div class="alert alert-info" style="text-align: center; font-weight: bold;">
                    🔒 คำขอกู้เงินนี้อยู่ในสถานะสิ้นสุดการพิจารณาแล้ว ไม่สามารถบันทึกเพิ่มความคิดเห็นได้
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>

</body>
</html>
