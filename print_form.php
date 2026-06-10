<?php
// print_form.php - Render printable loan documents (สม-๐๑ ถึง สม-๐๗)
require_once __DIR__ . '/db.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    die("ไม่ระบุรหัสประจำตัวคำขอกู้เงิน");
}

try {
    // 1. Fetch Loan Details
    $stmt = $db->prepare("SELECT * FROM loans WHERE token = ?");
    $stmt->execute([$token]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        die("ไม่พบคำขอกู้เงินรหัส: " . htmlspecialchars($token));
    }
    
    $loan_id = $loan['id'];
    
    // 2. Fetch Guarantors
    $stmt = $db->prepare("SELECT * FROM guarantors WHERE loan_id = ?");
    $stmt->execute([$loan_id]);
    $guarantors = $stmt->fetchAll();
    
    // 3. Fetch Documents
    $stmt = $db->prepare("SELECT * FROM loan_documents WHERE loan_id = ?");
    $stmt->execute([$loan_id]);
    $docs = $stmt->fetchAll();
    
    // Map documents for easy reference
    $doc_map = [];
    foreach ($docs as $d) {
        $doc_map[$d['doc_type']][] = $d['file_path'];
    }
    
    // 4. Fetch Reviews
    $stmt = $db->prepare("SELECT * FROM loan_reviews WHERE loan_id = ? ORDER BY id ASC");
    $stmt->execute([$loan_id]);
    $reviews_raw = $stmt->fetchAll();
    
    // Map reviews by role
    $reviews = [];
    foreach ($reviews_raw as $r) {
        $reviews[$r['reviewer_role']] = $r;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Helper to render dotted lines for printable fields
function print_val($val, $dots = 15) {
    if (empty($val) && $val !== 0 && $val !== '0') {
        return '<span class="fill-line" style="min-width: ' . ($dots * 6) . 'px;">&nbsp;</span>';
    }
    return '<span class="fill-line">' . htmlspecialchars($val) . '</span>';
}

function print_check($checked) {
    return $checked ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์คำขอกู้และสัญญากู้เงิน [<?= htmlspecialchars($loan['token']) ?>]</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/print.css">
</head>
<body>

<div class="print-actions-bar no-print">
    <div>
        <strong>📄 ตัวอย่างและจัดพิมพ์เอกสารแบบคำขอ (สม-๐๑ ถึง สม-๐๗)</strong>
        <p style="font-size: 0.8rem; margin: 0; color: var(--text-secondary);">จัดรูปแบบขนาด A4 พอดีหน้าสำหรับพิมพ์หรือบันทึกเป็น PDF</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="admin.php" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">แดชบอร์ดแอดมิน</a>
        <button onclick="window.print()" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.95rem;">🖨️ พิมพ์เอกสาร (Print)</button>
    </div>
</div>

<div class="print-preview-container">
    
    <!-- SHEET 1: คำขอกู้เงินสามัญทั่วไป (สม-๐๑) -->
    <div class="doc-sheet">
        <div class="doc-header">
            <div class="doc-logo-box" style="justify-content: flex-start;">
                <svg viewBox="0 0 100 100" width="80" height="80" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="#ffffff" stroke="#000000" stroke-width="3"/>
                    <path d="M50 15 L80 35 L80 65 L50 85 L20 65 L20 35 Z" fill="none" stroke="#000000" stroke-width="2"/>
                    <line x1="35" y1="45" x2="65" y2="45" stroke="#000000" stroke-width="3"/>
                    <line x1="50" y1="35" x2="50" y2="70" stroke="#000000" stroke-width="3"/>
                    <line x1="40" y1="70" x2="60" y2="70" stroke="#000000" stroke-width="2"/>
                    <path d="M30 45 L35 60 L40 45 Z" fill="none" stroke="#000000" stroke-width="1.5"/>
                    <path d="M60 45 L65 60 L70 45 Z" fill="none" stroke="#000000" stroke-width="1.5"/>
                </svg>
            </div>
            <div style="text-align: center; flex: 2;">
                <div class="doc-title" style="margin-bottom:0.25rem;">คำขอกู้เงินสามัญทั่วไป</div>
                <div style="font-size: 13pt;">สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</div>
            </div>
            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <table class="doc-meta-table">
                    <tr><td><strong>สม-๐๑ (๒๑ พ.ค.๖๘)</strong></td></tr>
                    <tr><td>คำขอที่: <?= print_val(substr($loan['token'], 3), 15) ?></td></tr>
                    <tr><td>วันที่: <?= print_val(date('d/m/Y', strtotime($loan['created_at'])), 15) ?></td></tr>
                </table>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 1rem;">
            เขียนที่ <?= print_val('ระบบยื่นกู้ออนไลน์ (สำนักงานใหญ่)', 35) ?><br>
            วันที่ <?= print_val(date('j', strtotime($loan['created_at']))) ?> 
            เดือน <?= print_val(date('F', strtotime($loan['created_at']))) ?> 
            พ.ศ. <?= print_val(date('Y', strtotime($loan['created_at'])) + 543) ?>
        </div>

        <div class="doc-body">
            <p><strong>เรียน คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong></p>
            <p style="text-indent: 1.5cm;">
                ข้าพเจ้า <?= print_val($loan['title'] . $loan['name'], 30) ?> สมาชิกเลขทะเบียนที่ <?= print_val($loan['member_no'], 15) ?>
                อายุ <?= print_val($loan['age']) ?> ปี วันเดือนปีเกิด <?= print_val(date('d/m/Y', strtotime($loan['dob'])), 20) ?>
                เลขประจำตัวประชาชน <?= print_val($loan['citizen_id'], 25) ?>
                สถานะการทำงาน 
                <span class="doc-checkbox <?= print_check($loan['work_status']=='ข้าราชการ') ?>">ข้าราชการ</span>
                <span class="doc-checkbox <?= print_check($loan['work_status']=='ข้าราชการบำนาญ') ?>">ข้าราชการบำนาญ</span>
                <span class="doc-checkbox <?= print_check($loan['work_status']=='อื่นๆ') ?>">อื่นๆ</span>
                <?= $loan['work_status_other'] ? print_val($loan['work_status_other'], 15) : '' ?>
                ตำแหน่ง <?= print_val($loan['position'], 20) ?> สังกัด <?= print_val($loan['affiliation'], 25) ?>
                อัตราเงินเดือน/ค่าจ้าง <?= print_val(number_format($loan['salary'], 2), 20) ?> บาท 
                อยู่บ้านเลขที่ <?= print_val($loan['address'], 45) ?> รหัสไปรษณีย์ <?= print_val($loan['postal_code'], 12) ?> 
                โทรศัพท์มือถือ <?= print_val($loan['mobile'], 18) ?> สถานภาพ
                <span class="doc-checkbox <?= print_check($loan['marital_status']=='โสด') ?>">โสด</span>
                <span class="doc-checkbox <?= print_check($loan['marital_status']=='หย่า') ?>">หย่า</span>
                <span class="doc-checkbox <?= print_check($loan['marital_status']=='ม่าย') ?>">ม่าย</span>
                <span class="doc-checkbox <?= print_check($loan['marital_status']=='สมรส') ?>">สมรส</span> 
                คู่สมรสชื่อ <?= print_val($loan['spouse_name'], 30) ?>
            </p>
            
            <p style="text-indent: 1.5cm;">ขอเสนอคำขอกู้เงินสามัญทั่วไปเพื่อพิจารณาดังต่อไปนี้</p>
            <p style="text-indent: 0.75cm;">
                <strong>ข้อ ๑</strong> ข้าพเจ้าขอกู้เงินสามัญทั่วไปจำนวน <?= print_val(number_format($loan['loan_amount'], 2), 25) ?> บาท 
                (ตัวอักษร) <?= print_val($loan['loan_amount_text'], 50) ?> โดยมีวัตถุประสงค์ในการกู้เงินเพื่อ 
                <?= print_val($loan['loan_purpose'], 60) ?>
            </p>
            <p style="text-indent: 0.75cm;">
                <strong>ข้อ ๒</strong> ข้าพเจ้าขอผ่อนชำระเงินกู้จำนวน <?= print_val($loan['repayment_installments']) ?> งวด โดยจะชำระต้นเงินกู้ให้แก่สหกรณ์ฯ เป็นรายเดือน 
                <span class="doc-checkbox <?= print_check($loan['repayment_type']==1) ?>">ต้นเงินพร้อมดอกเบี้ยเท่ากันต่องวด เดือนละ <?= print_val(number_format($loan['repayment_amount'], 2), 15) ?> บาท</span> 
                <span class="doc-checkbox <?= print_check($loan['repayment_type']==2) ?>">ต้นเงินเท่ากัน เดือนละ <?= print_val(number_format($loan['loan_amount']/$loan['repayment_installments'], 2), 15) ?> บาท พร้อมดอกเบี้ยต่างหาก</span>
            </p>
            
            <p style="text-indent: 0.75cm;">
                <strong>ข้อ ๓</strong> ขอเสนอสมาชิกค้ำประกันเงินกู้ดังต่อไปนี้:
                <ol style="margin-left: 2.5cm;">
                    <?php 
                    for ($idx = 0; $idx < 6; $idx++) {
                        $gName = isset($guarantors[$idx]) ? $guarantors[$idx]['name'] : '';
                        $gMem = isset($guarantors[$idx]) ? $guarantors[$idx]['member_no'] : '';
                        echo "<li>ชื่อ-นามสกุล " . print_val($gName, 35) . " สมาชิกทะเบียนที่ " . print_val($gMem, 15) . "</li>";
                    }
                    ?>
                </ol>
            </p>

            <p style="text-indent: 0.75cm;">
                <strong>ข้อ ๔</strong> ข้าพเจ้ายินยอมให้สหกรณ์หักภาระผูกพันตามสัญญาเงินกู้เลขที่เก่า 
                (1) <?= print_val('', 15) ?> (2) <?= print_val('', 15) ?> (3) <?= print_val('', 15) ?>
            </p>

            <p style="text-indent: 0.75cm;">
                <strong>ข้อ ๕</strong> ข้าพเจ้าประสงค์ให้สหกรณ์จ่ายเงินกู้ส่วนที่เหลือเข้าบัญชี 
                <span class="doc-checkbox <?= print_check($loan['receive_account_type'] == 1) ?>">บัญชีเงินฝากสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</span>
                <span class="doc-checkbox <?= print_check($loan['receive_account_type'] == 2) ?>">บัญชีเงินฝากธนาคารกรุงไทย จำกัด (มหาชน)</span>
                ชื่อบัญชี <?= print_val($loan['receive_account_name'], 30) ?> เลขที่บัญชี <?= print_val($loan['receive_account_no'], 25) ?>
            </p>

            <p style="text-indent: 0.75cm;">
                <strong>ข้อ ๖</strong> ข้าพเจ้า <span class="doc-checkbox checked">ไม่อยู่ระหว่างต้องหาคดีอาญา หรือถูกดำเนินคดีล้มละลาย หรือถูกตั้งกรรมการสอบสวนวินัยร้ายแรง</span>
            </p>
        </div>

        <div class="doc-signatures">
            <div class="doc-signature-line">
                (ลงชื่อ) 
                <?php if ($loan['borrower_signature']): ?>
                    <img src="<?= $loan['borrower_signature'] ?>" class="doc-signature-img" alt="ลายมือชื่อผู้กู้">
                <?php else: ?>
                    ............................................................
                <?php endif; ?>
                ผู้ขอกู้
            </div>
            <div style="margin-left: 1.5cm;">
                ( <?= print_val($loan['title'] . $loan['name'], 30) ?> )
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- SHEET 2: หนังสือรับรองผู้บังคับบัญชา และข้อมูลตรวจสอบของเจ้าหน้าที่ (สม-๐๑/๐๒) -->
    <div class="doc-sheet">
        <div class="doc-header">
            <div style="flex:1;">- ๒ -</div>
            <div style="text-align: right; flex: 1;">
                <table class="doc-meta-table" style="margin-left: auto;">
                    <tr><td><strong>สม-๐๑/๐๒ (๒๑ พ.ค.๖๘)</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="doc-subtitle">หนังสือรับรองผู้บังคับบัญชา</div>
        <div style="text-align: center; font-size: 11pt; margin-top:-10px; margin-bottom:1.5rem;">(ผู้บังคับบัญชาระดับผู้กำกับการหรือหัวหน้าสถานีขึ้นไป)</div>

        <div class="doc-body">
            <p style="text-indent: 1.5cm;">
                เขียนที่ <?= print_val('ระบบยืนยันผู้บังคับบัญชาออนไลน์', 35) ?><br>
                วันที่ <?= print_val(date('j', strtotime($loan['created_at']))) ?> 
                เดือน <?= print_val(date('F', strtotime($loan['created_at']))) ?> 
                พ.ศ. <?= print_val(date('Y', strtotime($loan['created_at'])) + 543) ?>
            </p>
            <p style="text-indent: 1.5cm;">
                ข้าพเจ้า <?= print_val('พ.ต.อ. ประวัติ รักสงบ (จำลอง)', 30) ?> ตำแหน่ง <?= print_val('ผู้กำกับการ (ผกก.) สภ.เมืองสงขลา', 30) ?>
                สังกัด <?= print_val('ตำรวจภูธรจังหวัดสงขลา', 25) ?> ขอรับรองว่า <?= print_val($loan['title'] . $loan['name'], 30) ?> ผู้ขอกู้
                <span class="doc-checkbox checked">ไม่อยู่ในระหว่างต้องหาคดีอาญา ถูกฟ้องล้มละลาย หรือถูกตั้งกรรมการสอบสวนวินัยร้ายแรง</span>
                <span class="doc-checkbox">อยู่ระหว่างต้องคดีอาญา/สอบสวนทางวินัย</span>
            </p>
            
            <div style="margin-left: auto; width: 65%; margin-top: 1.5rem; font-size: 12pt;">
                (ลงชื่อ) ............................................................ ผู้รับรอง (ผู้บังคับบัญชา)<br>
                ( พ.ต.อ. ประวัติ รักสงบ )<br>
                ตำแหน่ง ผู้กำกับการ สภ.เมืองสงขลา
            </div>
        </div>

        <!-- OFFICE USE ONLY -->
        <div class="officer-section" style="margin-top: 2rem;">
            <div style="font-weight: bold; font-family: var(--font-heading); margin-bottom: 0.5rem; text-align: center; font-size: 13pt;">สำหรับสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด (การเสนออนุมัติตามลำดับขั้น)</div>
            
            <table class="officer-table">
                <tr>
                    <td class="border-right border-bottom" style="width: 50%;">
                        <strong>1. การตรวจสอบเอกสาร (เจ้าหน้าที่สินเชื่อ)</strong><br>
                        <?php if (isset($reviews['credit_officer'])): ?>
                            สถานะ: <?= print_val($reviews['credit_officer']['decision'] == 'approved' ? 'เอกสารครบถ้วน ผ่านขั้นถัดไป' : 'เอกสารไม่สมบูรณ์', 20) ?><br>
                            ความเห็น: <?= print_val($reviews['credit_officer']['comments'], 30) ?><br>
                            ลงชื่อ: <?= print_val($reviews['credit_officer']['reviewer_name'], 15) ?><br>
                            วันที่: <?= date('d/m/Y', strtotime($reviews['credit_officer']['created_at'])) ?>
                        <?php else: ?>
                            ความเห็น: ....................................................................<br>
                            ลงชื่อ: ................................................. เจ้าหน้าที่สินเชื่อ
                        <?php endif; ?>
                    </td>
                    <td class="border-bottom">
                        <strong>2. หัวหน้าฝ่ายสินเชื่อ / ผู้ตรวจสอบ</strong><br>
                        <?php if (isset($reviews['credit_head'])): ?>
                            ความเห็น: <?= print_val($reviews['credit_head']['comments'], 30) ?><br>
                            เสนอวงเงิน: <?= print_val(number_format($reviews['credit_head']['approved_amount'], 2), 15) ?> บาท<br>
                            ลงชื่อ: <?= print_val($reviews['credit_head']['reviewer_name'], 15) ?><br>
                            วันที่: <?= date('d/m/Y', strtotime($reviews['credit_head']['created_at'])) ?>
                        <?php else: ?>
                            ความเห็น: ....................................................................<br>
                            ลงชื่อ: ............................................. หัวหน้าฝ่ายสินเชื่อ
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="border-right border-bottom">
                        <strong>3. รองผู้จัดการ / ผู้ตรวจทาน</strong><br>
                        <?php if (isset($reviews['assistant_manager'])): ?>
                            ความเห็น: <?= print_val($reviews['assistant_manager']['comments'], 30) ?><br>
                            ลงชื่อ: <?= print_val($reviews['assistant_manager']['reviewer_name'], 15) ?><br>
                            วันที่: <?= date('d/m/Y', strtotime($reviews['assistant_manager']['created_at'])) ?>
                        <?php else: ?>
                            ความเห็น: ....................................................................<br>
                            ลงชื่อ: .................................................. รองผู้จัดการ
                        <?php endif; ?>
                    </td>
                    <td class="border-bottom">
                        <strong>4. ความเห็นผู้จัดการ</strong><br>
                        <?php if (isset($reviews['manager'])): ?>
                            ความเห็น: <?= print_val($reviews['manager']['comments'], 30) ?><br>
                            วงเงินเห็นควร: <?= print_val(number_format($reviews['manager']['approved_amount'], 2), 15) ?> บาท<br>
                            ลงชื่อ: <?= print_val($reviews['manager']['reviewer_name'], 15) ?><br>
                            วันที่: <?= date('d/m/Y', strtotime($reviews['manager']['created_at'])) ?>
                        <?php else: ?>
                            ความเห็น: ....................................................................<br>
                            ลงชื่อ: ....................................................... ผู้จัดการ
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="border-right">
                        <strong>5. มติคณะกรรมการเงินกู้</strong><br>
                        <?php if (isset($reviews['loan_committee'])): ?>
                            มติ: <?= print_val($reviews['loan_committee']['decision'] == 'approved' ? 'อนุมัติ' : 'ไม่อนุมัติ', 10) ?><br>
                            ประชุมครั้งที่: <?= print_val($reviews['loan_committee']['meeting_no'], 10) ?> เมื่อวันที่: <?= print_val($reviews['loan_committee']['meeting_date'], 10) ?><br>
                            ลงชื่อ: <?= print_val($reviews['loan_committee']['reviewer_name'], 15) ?> ประธาน/เลขานุการ
                        <?php else: ?>
                            มติ: <span class="doc-checkbox">อนุมัติ</span> <span class="doc-checkbox">ไม่อนุมัติ</span><br>
                            ประชุมครั้งที่: .............................. เมื่อวันที่: .......................<br>
                            ลงชื่อ: .......................................... ประธานคณะกรรมการ
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong>6. มติคณะกรรมการดำเนินการ (สิ้นสุด)</strong><br>
                        <?php if (isset($reviews['board'])): ?>
                            มติอนุมัติ: <?= print_val($reviews['board']['decision'] == 'approved' ? 'อนุมัติ' : 'ไม่อนุมัติ', 10) ?> วงเงินอนุมัติ: <?= print_val(number_format($reviews['board']['approved_amount'], 2), 15) ?> บาท<br>
                            ประชุมครั้งที่: <?= print_val($reviews['board']['meeting_no'], 10) ?> เมื่อวันที่: <?= print_val($reviews['board']['meeting_date'], 10) ?><br>
                            ลงชื่อ: <?= print_val($reviews['board']['reviewer_name'], 15) ?> ประธานดำเนินการ
                        <?php else: ?>
                            มติอนุมัติ: <span class="doc-checkbox">อนุมัติ</span> <span class="doc-checkbox">ไม่อนุมัติ</span><br>
                            วงเงินอนุมัติสุทธิ: ................................................. บาท<br>
                            ลงชื่อ: ......................................... ประธานกรรมการสหกรณ์
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- SHEET 3: หนังสือยินยอมให้ส่วนราชการหักเงินชำระหนี้ (สม-๐๒) -->
    <div class="doc-sheet">
        <div class="doc-header">
            <div class="doc-logo-box" style="justify-content: flex-start;">
                <svg viewBox="0 0 100 100" width="60" height="60" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="#ffffff" stroke="#000000" stroke-width="3"/>
                    <path d="M50 15 L80 35 L80 65 L50 85 L20 65 L20 35 Z" fill="none" stroke="#000000" stroke-width="2"/>
                    <line x1="35" y1="45" x2="65" y2="45" stroke="#000000" stroke-width="3"/>
                    <line x1="50" y1="35" x2="50" y2="70" stroke="#000000" stroke-width="3"/>
                </svg>
            </div>
            <div style="text-align: center; flex: 2; margin-top: 10px;">
                <div style="font-size: 15pt; font-weight: 700; line-height: 1.3;">หนังสือยินยอมให้ส่วนราชการหักเงินชำระหนี้สหกรณ์ออมทรัพย์</div>
            </div>
            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <table class="doc-meta-table">
                    <tr><td><strong>สม-๐๒ (๒๑ พ.ค.๖๘)</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="doc-body" style="font-size: 13pt;">
            <p style="text-align: right;">
                เขียนที่ <?= print_val('สหกรณ์ตำรวจสงขลา', 20) ?><br>
                วันที่ <?= print_val(date('j', strtotime($loan['created_at']))) ?> 
                เดือน <?= print_val(date('F', strtotime($loan['created_at']))) ?> 
                พ.ศ. <?= print_val(date('Y', strtotime($loan['created_at'])) + 543) ?>
            </p>
            <p style="text-indent: 1.5cm;">
                ข้าพเจ้า <?= print_val($loan['title'] . $loan['name'], 30) ?> อายุ <?= print_val($loan['age']) ?> ปี 
                ปัจจุบันอยู่บ้านเลขที่ <?= print_val($loan['address'], 45) ?>
                สังกัด <?= print_val($loan['affiliation'], 25) ?> ตำแหน่ง <?= print_val($loan['position'], 25) ?> 
                สมาชิกเลขทะเบียนที่ <?= print_val($loan['member_no'], 15) ?>
                มีความประสงค์ยินยอมให้ เจ้าหน้าที่ผู้จ่ายเงินเดือนและค่าจ้างของส่วนราชการ/หน่วยงานที่ข้าพเจ้าสังกัดอยู่ 
                ดำเนินการหักเงินเดือน ค่างวด หรือผลประโยชน์อื่นใดที่ข้าพเจ้าพึงได้รับ ส่งชำระหนี้สะสม หนี้เงินกู้ หรือหุ้นประจำเดือน 
                ให้แก่สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด ตามจำนวนที่สหกรณ์ได้แจ้งเรียกร้องในแต่ละเดือนจนกว่าจะเสร็จสิ้นภาระผูกพัน
            </p>
            
            <div style="margin-top: 3rem; display: flex; justify-content: flex-end; flex-direction: column; width: 60%; margin-left: auto;">
                <div class="doc-signature-line">
                    (ลงชื่อ) 
                    <?php if ($loan['borrower_signature']): ?>
                        <img src="<?= $loan['borrower_signature'] ?>" class="doc-signature-img" alt="ลายมือชื่อผู้กู้">
                    <?php else: ?>
                        ............................................................
                    <?php endif; ?>
                    ผู้ให้คำยินยอม
                </div>
                <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( <?= htmlspecialchars($loan['title'] . $loan['name']) ?> )</div>
                
                <div class="doc-signature-line">(ลงชื่อ) ............................................................ พยาน (เจ้าหน้าที่การเงินต้นสังกัด)</div>
                <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( ............................................................ )</div>
                
                <div class="doc-signature-line">(ลงชื่อ) ............................................................ พยาน (สมาชิกสหกรณ์)</div>
                <div style="margin-left: 1.5cm;">( ............................................................ )</div>
            </div>
        </div>
    </div>

    <!-- Additional sheets only rendered dynamically as required -->
    <?php if ($loan['shares_buy_amount'] > 0): ?>
        <div class="page-break"></div>
        <!-- SHEET 4: หนังสือขอซื้อหุ้นเพื่อกู้ (สม-๐๔) -->
        <div class="doc-sheet">
            <div class="doc-header">
                <div class="doc-logo-box" style="justify-content: flex-start;">
                    <svg viewBox="0 0 100 100" width="60" height="60" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#ffffff" stroke="#000000" stroke-width="3"/>
                        <path d="M50 15 L80 35 L80 65 L50 85 L20 65 L20 35 Z" fill="none" stroke="#000000" stroke-width="2"/>
                    </svg>
                </div>
                <div style="text-align: center; flex: 2; margin-top: 10px;">
                    <div style="font-size: 16pt; font-weight: 700;">หนังสือขอซื้อหุ้นเพื่อกู้</div>
                    <div style="font-size: 11pt; font-weight: normal;">(ใช้เฉพาะกรณีขอซื้อหุ้นเพื่อขอกู้เงินเพิ่ม)</div>
                </div>
                <div style="flex: 1; display: flex; justify-content: flex-end;">
                    <table class="doc-meta-table">
                        <tr><td><strong>สม-๐๔ (๒๑ พ.ค.๖๘)</strong></td></tr>
                    </table>
                </div>
            </div>

            <div class="doc-body" style="margin-top: 2rem;">
                <p style="text-align: right;">
                    เขียนที่ <?= print_val('สำนักงานสหกรณ์ตำรวจสงขลา', 25) ?><br>
                    วันที่ <?= print_val(date('j', strtotime($loan['created_at']))) ?> 
                    เดือน <?= print_val(date('F', strtotime($loan['created_at']))) ?> 
                    พ.ศ. <?= print_val(date('Y', strtotime($loan['created_at'])) + 543) ?>
                </p>
                <p style="text-indent: 1.5cm;">
                    ข้าพเจ้า <?= print_val($loan['title'] . $loan['name'], 30) ?> สมาชิกทะเบียนที่ <?= print_val($loan['member_no'], 15) ?>
                    ตำแหน่ง <?= print_val($loan['position'], 25) ?> สังกัด <?= print_val($loan['affiliation'], 25) ?>
                    ปัจจุบันมีหุ้นสะสมในสหกรณ์ฯ จำนวน <?= print_val('ระบุตามประวัติ', 20) ?> บาท 
                    มีความประสงค์ขอซื้อหุ้นเพิ่มเติมเพื่อประกอบการขอกู้เงินในครั้งนี้ จำนวน <?= print_val(number_format($loan['shares_buy_amount'], 2), 20) ?> บาท 
                    โดยยินยอมให้ทางสหกรณ์ฯ หักเงินค่าซื้อหุ้นดังกล่าวจากวงเงินกู้สามัญทั่วไปที่ได้รับอนุมัติในครั้งนี้
                </p>
                
                <div style="margin-top: 4rem; display: flex; justify-content: flex-end; flex-direction: column; width: 60%; margin-left: auto;">
                    <div class="doc-signature-line">
                        (ลงชื่อ) 
                        <?php if ($loan['borrower_signature']): ?>
                            <img src="<?= $loan['borrower_signature'] ?>" class="doc-signature-img" alt="ลายมือชื่อผู้กู้">
                        <?php else: ?>
                            ............................................................
                        <?php endif; ?>
                        ผู้ยื่นขอกู้
                    </div>
                    <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( <?= htmlspecialchars($loan['title'] . $loan['name']) ?> )</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="page-break"></div>

    <!-- SHEET 5: หนังสือยินยอมให้หักเงินบำเหน็จค้ำประกัน (สม-๐๓) -->
    <div class="doc-sheet">
        <div class="doc-header">
            <div class="doc-logo-box" style="justify-content: flex-start;">
                <svg viewBox="0 0 100 100" width="60" height="60" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="#ffffff" stroke="#000000" stroke-width="3"/>
                </svg>
            </div>
            <div style="text-align: center; flex: 2; margin-top: 10px;">
                <div style="font-size: 14pt; font-weight: 700; line-height: 1.3;">หนังสือยินยอมให้หน่วยงานหักเงินให้สหกรณ์เป็นลำดับก่อนเงินบำเหน็จค้ำประกัน</div>
            </div>
            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <table class="doc-meta-table">
                    <tr><td><strong>สม-๐๓ (๒๑ พ.ค.๖๘)</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="doc-body" style="font-size: 12pt; margin-top: 1.5rem;">
            <p style="text-align: right;">
                เขียนที่ <?= print_val('สำนักงานสหกรณ์ตำรวจสงขลา', 20) ?><br>
                วันที่ <?= print_val(date('j', strtotime($loan['created_at']))) ?> 
                เดือน <?= print_val(date('F', strtotime($loan['created_at']))) ?> 
                พ.ศ. <?= print_val(date('Y', strtotime($loan['created_at'])) + 543) ?>
            </p>
            <p style="text-indent: 1.5cm;">
                ข้าพเจ้า <?= print_val($loan['title'] . $loan['name'], 30) ?> สมาชิกเลขทะเบียนที่ <?= print_val($loan['member_no'], 15) ?>
                อายุ <?= print_val($loan['age']) ?> ปี เลขประจำตัวประชาชน <?= print_val($loan['citizen_id'], 25) ?>
                อยู่บ้านเลขที่ <?= print_val($loan['address'], 45) ?> มือถือ <?= print_val($loan['mobile'], 18) ?>
                ได้กู้เงินสามัญทั่วไปจากสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด เป็นจำนวนเงิน <?= print_val(number_format($loan['loan_amount'], 2), 20) ?> บาท 
                ยินยอมให้กองการเงิน สำนักงานตำรวจแห่งชาติ หรือหน่วยงานการเงินต้นสังกัด ดำเนินการหักเงินบำเหน็จตกทอด บำเหน็จดำรงชีพ หรือผลประโยชน์ตอบแทนใดๆ 
                เพื่อส่งชำระหนี้คงค้างให้แก่สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด เป็นลำดับแรกก่อนโอนเงินส่วนที่เหลือให้แก่สถาบันการเงินอื่น
            </p>
            
            <div style="margin-top: 3rem; display: flex; justify-content: flex-end; flex-direction: column; width: 60%; margin-left: auto;">
                <div class="doc-signature-line">
                    (ลงชื่อ) 
                    <?php if ($loan['borrower_signature']): ?>
                        <img src="<?= $loan['borrower_signature'] ?>" class="doc-signature-img" alt="ลายมือชื่อผู้กู้">
                    <?php else: ?>
                        ............................................................
                    <?php endif; ?>
                    ผู้ให้คำยินยอม
                </div>
                <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( <?= htmlspecialchars($loan['title'] . $loan['name']) ?> )</div>
                
                <div class="doc-signature-line">(ลงชื่อ) ............................................................ พยาน (สมาชิกสหกรณ์)</div>
                <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( ............................................................ )</div>
                
                <div class="doc-signature-line">(ลงชื่อ) ............................................................ พยาน (สมาชิกสหกรณ์)</div>
                <div style="margin-left: 1.5cm;">( ............................................................ )</div>
            </div>
        </div>
    </div>

    <!-- SHEET 6: รูปภาพแนบท้ายสัญญากู้ยืมเงิน (สม-๐๕/๐๒) -->
    <?php if (isset($doc_map['photo_sign_1']) || isset($doc_map['photo_sign_2'])): ?>
        <div class="page-break"></div>
        <div class="doc-sheet">
            <div class="doc-header">
                <div style="font-weight: bold;">ภาพถ่ายประกอบลายมือชื่อในสัญญากู้ยืมเงิน</div>
                <div style="text-align: right;">
                    <table class="doc-meta-table" style="margin-left: auto;">
                        <tr><td><strong>สม-๐๕/๐๒ (๒๑ พ.ค.๖๘)</strong></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="photo-sheets-grid">
                <div class="photo-holder">
                    <?php if (isset($doc_map['photo_sign_1'][0])): ?>
                        <img src="<?= htmlspecialchars($doc_map['photo_sign_1'][0]) ?>" alt="รูปภาพที่ 1">
                    <?php endif; ?>
                    <span>รูปภาพที่ 1 (ภาพถ่ายขณะผู้กู้ลงลายมือชื่อ)</span>
                </div>
                
                <div class="photo-holder">
                    <?php if (isset($doc_map['photo_sign_2'][0])): ?>
                        <img src="<?= htmlspecialchars($doc_map['photo_sign_2'][0]) ?>" alt="รูปภาพที่ 2">
                    <?php endif; ?>
                    <span>รูปภาพที่ 2 (ภาพถ่ายแสดงสัญญากู้ที่มีลายเซ็นชัดเจน)</span>
                </div>
            </div>
            
            <div class="doc-body" style="font-size: 12pt; text-align: center; margin-top: 3rem;">
                ข้าพเจ้า <?= print_val($loan['title'] . $loan['name'], 30) ?> สมาชิกทะเบียนที่ <?= print_val($loan['member_no'], 15) ?><br>
                ขอรับรองว่าลายมือชื่อดิจิทัลและเอกสารประกอบข้างต้นเป็นลายเซ็นและภาพถ่ายของข้าพเจ้าจริงทุกประการ
                
                <div style="margin-top: 1.5rem; display: flex; justify-content: center; flex-direction: column; width: 50%; margin-left: auto; margin-right: auto; text-align: left;">
                    <div class="doc-signature-line" style="justify-content: center;">
                        (ลงชื่อ) 
                        <?php if ($loan['borrower_signature']): ?>
                            <img src="<?= $loan['borrower_signature'] ?>" class="doc-signature-img" style="max-height: 40px;" alt="ลายเซ็น">
                        <?php else: ?>
                            ................................................
                        <?php endif; ?>
                        ผู้กู้
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- SHEET 7: หนังสือสัญญาค้ำประกันแยกรายบุคคล (สม-๐๗) -->
    <?php if (!empty($guarantors)): 
        foreach ($guarantors as $gIndex => $g):
    ?>
        <div class="page-break"></div>
        <div class="doc-sheet">
            <div class="doc-header">
                <div class="doc-logo-box" style="justify-content: flex-start;">
                    <svg viewBox="0 0 100 100" width="70" height="70" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#ffffff" stroke="#000000" stroke-width="3"/>
                        <path d="M50 15 L80 35 L80 65 L50 85 L20 65 L20 35 Z" fill="none" stroke="#000000" stroke-width="2"/>
                    </svg>
                </div>
                <div style="text-align: center; flex: 2; margin-top: 10px;">
                    <div class="doc-title" style="font-size:16pt; margin-bottom: 0;">หนังสือสัญญาค้ำประกัน</div>
                    <div style="font-size: 11pt; font-weight: normal;">สำหรับเงินกู้สามัญทั่วไป (รายที่ <?= $gIndex+1 ?>)</div>
                </div>
                <div style="flex: 1; display: flex; justify-content: flex-end;">
                    <table class="doc-meta-table">
                        <tr><td><strong>สม-๐๗ (๒๑ พ.ค.๖๘)</strong></td></tr>
                        <tr><td style="font-size: 8pt;">สัญญากู้ที่: <?= htmlspecialchars($loan['token']) ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="doc-body" style="font-size: 12pt; line-height: 1.6;">
                <p style="text-align: right;">
                    เขียนที่ <?= print_val('ระบบค้ำประกันออนไลน์', 20) ?><br>
                    วันที่ <?= print_val(date('j', strtotime($loan['created_at']))) ?> 
                    เดือน <?= print_val(date('F', strtotime($loan['created_at']))) ?> 
                    พ.ศ. <?= print_val(date('Y', strtotime($loan['created_at'])) + 543) ?>
                </p>
                <p style="text-indent: 1.5cm;">
                    ข้าพเจ้า <?= print_val($g['name'], 30) ?> สมาชิกทะเบียนที่ <?= print_val($g['member_no'], 15) ?>
                    อายุ <?= print_val($g['age']) ?> ปี เลขประจำตัวประชาชน <?= print_val($g['citizen_id'], 25) ?>
                    ตำแหน่ง <?= print_val($g['position'], 25) ?> สังกัด <?= print_val($g['affiliation'], 25) ?>
                    เงินเดือน <?= print_val(number_format($g['salary'], 2), 20) ?> บาท ที่อยู่ <?= print_val($g['address'], 45) ?>
                    เบอร์มือถือ <?= print_val($g['mobile'], 18) ?> สถานภาพ <?= print_val($g['marital_status'], 12) ?> 
                    ซึ่งต่อไปในสัญญานี้เรียกว่า "ผู้ค้ำประกัน" ตกลงทำหนังสือค้ำประกันฉบับนี้ไว้ต่อสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด ดังนี้
                </p>
                <p style="text-indent: 0.75cm;">
                    <strong>ข้อ ๑</strong> ตามที่ <?= print_val($loan['title'] . $loan['name'], 30) ?> (ซึ่งเรียกว่า "ลูกหนี้") 
                    ได้กู้เงินจากสหกรณ์ฯ เป็นจำนวนเงิน <?= print_val(number_format($loan['loan_amount'], 2), 20) ?> บาท 
                    ข้าพเจ้าผู้ค้ำประกันตกลงยินยอมค้ำประกันหนี้ดังกล่าวในวงเงินจำกัดจำนวน <?= print_val(number_format($g['guarantee_amount'], 2), 20) ?> บาท 
                    หากลูกหนี้ผิดนัดชำระหนี้ ข้าพเจ้ายินยอมชำระหนี้แทนลูกหนี้แก่สหกรณ์โดยสิ้นเชิง
                </p>
                <p style="text-indent: 0.75cm;">
                    <strong>ข้อ ๒</strong> ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินเดือน ดำเนินการหักเงินเดือน เงินบำนาญ หรือผลประโยชน์อื่นใดของข้าพเจ้าเพื่อส่งชำระหนี้แก่สหกรณ์ฯ แทนลูกหนี้ทันทีตามที่ได้รับการแจ้งเตือนเรียกร้อง
                </p>

                <div style="margin-top: 3rem; display: flex; justify-content: flex-end; flex-direction: column; width: 60%; margin-left: auto;">
                    <div class="doc-signature-line">
                        (ลงชื่อ) 
                        <?php if ($g['signature_data']): ?>
                            <img src="<?= $g['signature_data'] ?>" class="doc-signature-img" alt="ลายเซ็นผู้ค้ำ">
                        <?php else: ?>
                            ............................................................
                        <?php endif; ?>
                        ผู้ค้ำประกัน
                    </div>
                    <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( <?= htmlspecialchars($g['name']) ?> )</div>
                    
                    <div class="doc-signature-line">(ลงชื่อ) ............................................................ พยาน (สมาชิกสหกรณ์)</div>
                    <div style="margin-left: 1.5cm; margin-bottom: 1rem;">( ............................................................ )</div>
                    
                    <div class="doc-signature-line">(ลงชื่อ) ............................................................ พยาน (สมาชิกสหกรณ์)</div>
                    <div style="margin-left: 1.5cm;">( ............................................................ )</div>
                </div>
            </div>
        </div>
    <?php 
        endforeach;
    endif; 
    ?>

</div>

</body>
</html>
