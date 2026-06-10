<?php
// apply.php - Multi-step Loan Application Form
require_once __DIR__ . '/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['member_no'])) {
    header('Location: login.php');
    exit;
}

$member = $_SESSION['member_data'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยื่นคำขอกู้เงินสามัญทั่วไปออนไลน์ | สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/form.css">
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
                <li><a href="apply.php" class="active">ยื่นคำขอกู้เงิน</a></li>
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
    <!-- Stepper Navigation -->
    <div class="stepper">
        <div class="stepper-progress"></div>
        <div class="step-item active">
            <div class="step-circle">1</div>
            <div class="step-label">ข้อมูลผู้กู้</div>
        </div>
        <div class="step-item">
            <div class="step-circle">2</div>
            <div class="step-label">ข้อมูลเงินกู้</div>
        </div>
        <div class="step-item">
            <div class="step-circle">3</div>
            <div class="step-label">ผู้ค้ำประกัน</div>
        </div>
        <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-label">แนบหลักฐาน</div>
        </div>
        <div class="step-item">
            <div class="step-circle">5</div>
            <div class="step-label">ข้อตกลง & ลงนาม</div>
        </div>
        <div class="step-item">
            <div class="step-circle">6</div>
            <div class="step-label">ส่งคำขอ</div>
        </div>
    </div>

    <div class="card">
        <form id="loanForm" action="submit_application.php" method="POST" enctype="multipart/form-data">
            
            <!-- STEP 1: ข้อมูลผู้กู้ (Borrower Info) -->
            <div class="form-step active" id="step1">
                <div class="step-title">
                    <h3>👤 ข้อมูลผู้ขอกู้เงิน (Borrower Personal Details)</h3>
                    <span>ขั้นตอนที่ 1 จาก 6</span>
                </div>
                
                <div class="grid-4">
                    <div class="form-group">
                        <label for="title">คำนำหน้า <span style="color:var(--danger-color)">*</span></label>
                        <select id="title" name="title" class="form-control" required>
                            <option value="นาย" <?= ($member['title']=='นาย') ? 'selected' : '' ?>>นาย</option>
                            <option value="นาง" <?= ($member['title']=='นาง') ? 'selected' : '' ?>>นาง</option>
                            <option value="นางสาว" <?= ($member['title']=='นางสาว') ? 'selected' : '' ?>>นางสาว</option>
                            <option value="ร.ต.ต." <?= ($member['title']=='ร.ต.ต.') ? 'selected' : '' ?>>ร.ต.ต.</option>
                            <option value="ร.ต.ท." <?= ($member['title']=='ร.ต.ท.') ? 'selected' : '' ?>>ร.ต.ท.</option>
                            <option value="ร.ต.อ." <?= ($member['title']=='ร.ต.อ.') ? 'selected' : '' ?>>ร.ต.อ.</option>
                            <option value="พ.ต.ต." <?= ($member['title']=='พ.ต.ต.') ? 'selected' : '' ?>>พ.ต.ต.</option>
                            <option value="พ.ต.ท." <?= ($member['title']=='พ.ต.ท.') ? 'selected' : '' ?>>พ.ต.ท.</option>
                            <option value="พ.ต.อ." <?= ($member['title']=='พ.ต.อ.') ? 'selected' : '' ?>>พ.ต.อ.</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="name">ชื่อ-นามสกุล <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="กรอกชื่อและนามสกุล" value="<?= htmlspecialchars($member['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="member_no">เลขทะเบียนสมาชิก <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="member_no" name="member_no" class="form-control" required placeholder="ตัวอย่าง: 00009" value="<?= htmlspecialchars($member['member_no']) ?>" readonly>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="age">อายุ (ปี) <span style="color:var(--danger-color)">*</span></label>
                        <input type="number" id="age" name="age" class="form-control" required placeholder="อายุเฉพาะตัวเลข" value="<?= htmlspecialchars($member['age']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob">วันเดือนปีเกิด <span style="color:var(--danger-color)">*</span></label>
                        <input type="date" id="dob" name="dob" class="form-control" required value="<?= htmlspecialchars($member['dob']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="citizen_id">เลขประจำตัวประชาชน <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="citizen_id" name="citizen_id" class="form-control" required placeholder="เลข 13 หลัก" maxlength="13" value="<?= htmlspecialchars($member['citizen_id']) ?>" readonly>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>สถานะการทำงาน <span style="color:var(--danger-color)">*</span></label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <label><input type="radio" name="work_status" value="ข้าราชการ" <?= ($member['work_status']=='ข้าราชการ') ? 'checked' : '' ?>> ข้าราชการ</label>
                            <label><input type="radio" name="work_status" value="ข้าราชการบำนาญ" <?= ($member['work_status']=='ข้าราชการบำนาญ') ? 'checked' : '' ?>> ข้าราชการบำนาญ</label>
                            <label><input type="radio" name="work_status" value="อื่นๆ" <?= (!in_array($member['work_status'], ['ข้าราชการ', 'ข้าราชการบำนาญ'])) ? 'checked' : '' ?>> อื่นๆ</label>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="work_status_other">กรณีเลือกทำงานอื่นๆ (โปรดระบุ)</label>
                        <input type="text" id="work_status_other" name="work_status_other" class="form-control" placeholder="ระบุอาชีพหรือสถานะ" value="<?= (!in_array($member['work_status'], ['ข้าราชการ', 'ข้าราชการบำนาญ'])) ? htmlspecialchars($member['work_status']) : '' ?>">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="position">ตำแหน่งหน้าที่งาน</label>
                        <input type="text" id="position" name="position" class="form-control" placeholder="ตำแหน่งปัจจุบัน" value="<?= htmlspecialchars($member['position']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="affiliation">สังกัด/หน่วยงานที่ปฏิบัติการ</label>
                        <input type="text" id="affiliation" name="affiliation" class="form-control" placeholder="สังกัดหรือโรงพัก" value="<?= htmlspecialchars($member['affiliation']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="salary">เงินเดือน/เงินบำนาญ (บาท) <span style="color:var(--danger-color)">*</span></label>
                        <input type="number" id="salary" name="salary" class="form-control" required placeholder="เงินเดือนสุทธิ" value="<?= htmlspecialchars($member['salary']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">ที่อยู่ปัจจุบันตามทะเบียนบ้าน <span style="color:var(--danger-color)">*</span></label>
                    <input type="text" id="address" name="address" class="form-control" required placeholder="บ้านเลขที่ หมู่ ซอย ถนน ตำบล อำเภอ จังหวัด" value="<?= htmlspecialchars($member['address']) ?>">
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="postal_code">รหัสไปรษณีย์ <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" required placeholder="รหัสไปรษณีย์" value="<?= htmlspecialchars($member['postal_code']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="mobile">โทรศัพท์มือถือ <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="mobile" name="mobile" class="form-control" required placeholder="เบอร์โทรศัพท์ที่ติดต่อได้" value="<?= htmlspecialchars($member['mobile']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="marital_status">สถานภาพ <span style="color:var(--danger-color)">*</span></label>
                        <select id="marital_status" name="marital_status" class="form-control" required>
                            <option value="โสด" <?= ($member['marital_status']=='โสด') ? 'selected' : '' ?>>โสด</option>
                            <option value="สมรส" <?= ($member['marital_status']=='สมรส') ? 'selected' : '' ?>>สมรส</option>
                            <option value="หย่า" <?= ($member['marital_status']=='หย่า') ? 'selected' : '' ?>>หย่า</option>
                            <option value="ม่าย" <?= ($member['marital_status']=='ม่าย') ? 'selected' : '' ?>>ม่าย</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="spouseNameGroup" style="<?= ($member['marital_status']=='สมรส') ? 'display:block;' : 'display:none;' ?>">
                    <label for="spouse_name">ชื่อ-นามสกุล คู่สมรส (กรณีสมรส)</label>
                    <input type="text" id="spouse_name" name="spouse_name" class="form-control" placeholder="ชื่อคู่สมรส" value="<?= htmlspecialchars($member['spouse_name']) ?>">
                </div>
                
                <script>
                    document.getElementById('marital_status').addEventListener('change', function() {
                        const spouseGroup = document.getElementById('spouseNameGroup');
                        if (this.value === 'สมรส') {
                            spouseGroup.style.display = 'block';
                        } else {
                            spouseGroup.style.display = 'none';
                        }
                    });
                </script>

                <div class="wizard-actions">
                    <div></div>
                    <button type="button" class="btn btn-primary btn-next">ถัดไป 👉</button>
                </div>
            </div>

            <!-- STEP 2: ข้อมูลคำขอกู้เงิน (Loan Request) -->
            <div class="form-step" id="step2">
                <div class="step-title">
                    <h3>💰 ข้อมูลความต้องการขอกู้เงิน (Loan Amount & Repayment)</h3>
                    <span>ขั้นตอนที่ 2 จาก 6</span>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="loan_amount">จำนวนเงินที่ขอกู้ (บาท) <span style="color:var(--danger-color)">*</span></label>
                        <input type="number" id="loan_amount" name="loan_amount" class="form-control" required placeholder="ระบุจำนวนเงินที่ต้องการกู้ เช่น 500000">
                    </div>
                    <div class="form-group">
                        <label for="loan_amount_text">จำนวนเงินตัวอักษร (คำนวณอัตโนมัติ)</label>
                        <input type="text" id="loan_amount_text" name="loan_amount_text" class="form-control" readonly placeholder="จำนวนเงินตัวเขียน">
                    </div>
                </div>

                <div class="form-group">
                    <label for="loan_purpose">วัตถุประสงค์ในการกู้เงินเพื่อ <span style="color:var(--danger-color)">*</span></label>
                    <input type="text" id="loan_purpose" name="loan_purpose" class="form-control" required placeholder="ระบุวัตถุประสงค์ เช่น ซื้อบ้าน, เพื่อการบริโภค, การศึกษาบุตร">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="repayment_installments">จำนวนงวดผ่อนชำระ (เดือน) <span style="color:var(--danger-color)">*</span></label>
                        <input type="number" id="repayment_installments" name="repayment_installments" class="form-control" required placeholder="ระบุจำนวนงวดชำระ เช่น 120">
                    </div>
                    <div class="form-group">
                        <label>อัตราดอกเบี้ยที่คาดการณ์</label>
                        <input type="text" class="form-control" value="5.50 % ต่อปี (ดอกเบี้ยประกาศสหกรณ์ฯ)" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label>ประเภทวิธีผ่อนชำระหนี้ส่งเงินกู้ <span style="color:var(--danger-color)">*</span></label>
                    <div class="selector-cards">
                        <div class="selector-card selected" onclick="document.getElementById('rep_type_1').click();">
                            <input type="radio" id="rep_type_1" name="repayment_type" value="1" checked>
                            <div class="selector-card-label">
                                <span class="selector-card-title">ผ่อนชำระค่างวดเท่ากันทุกเดือน</span>
                                <span class="selector-card-desc">ต้นเงินพร้อมดอกเบี้ยเท่ากันในแต่ละเดือน</span>
                            </div>
                        </div>
                        <div class="selector-card" onclick="document.getElementById('rep_type_2').click();">
                            <input type="radio" id="rep_type_2" name="repayment_type" value="2">
                            <div class="selector-card-label">
                                <span class="selector-card-title">ผ่อนชำระต้นเงินเท่ากันทุกเดือน</span>
                                <span class="selector-card-desc">ต้นเงินคงที่ + ดอกเบี้ยตามยอดคงเหลือ (ค่างวดลดลงเรื่อยๆ)</span>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.querySelectorAll('.selector-card').forEach(card => {
                            card.addEventListener('click', function() {
                                this.parentNode.querySelectorAll('.selector-card').forEach(c => c.classList.remove('selected'));
                                this.classList.add('selected');
                            });
                        });
                    </script>
                </div>

                <!-- Repayment calculation display -->
                <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>ยอดชำระผ่อนจ่ายรายเดือนโดยประมาณ:</strong>
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);" id="repayment_amount_display">-</div>
                        <input type="hidden" id="repayment_amount" name="repayment_amount">
                    </div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">* ดำเนินการหักจากสลิปเงินเดือนโดยตรง</span>
                </div>

                <div class="grid-3" style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem;">
                    <div class="form-group" style="grid-column: span 3;">
                        <label>บัญชีรับเงินกู้ (ประสงค์ให้สหกรณ์จ่ายเงินกู้ส่วนที่เหลือเข้าบัญชี) <span style="color:var(--danger-color)">*</span></label>
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 0.75rem;">
                            <label><input type="radio" name="receive_account_type" value="1" checked> บัญชีสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</label>
                            <label><input type="radio" name="receive_account_type" value="2"> บัญชีธนาคารกรุงไทย จำกัด (มหาชน)</label>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="receive_account_name">ชื่อบัญชีรับเงิน <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="receive_account_name" name="receive_account_name" class="form-control" required placeholder="กรอกชื่อบัญชี เช่น นายสมชาย มั่งมี">
                    </div>
                    <div class="form-group">
                        <label for="receive_account_no">เลขที่บัญชี <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="receive_account_no" name="receive_account_no" class="form-control" required placeholder="ระบุเลขที่บัญชี">
                    </div>
                </div>

                <!-- Add shares purchase section (สม-๐๔) -->
                <div class="form-group" style="border-top: 1px dashed var(--border-color); padding-top: 1.5rem;">
                    <label for="shares_buy_amount">ความประสงค์ขอซื้อหุ้นเพื่อกู้เพิ่ม (สม-๐๔) (หากไม่ซื้อให้กรอก 0)</label>
                    <input type="number" id="shares_buy_amount" name="shares_buy_amount" class="form-control" value="0" placeholder="จำนวนเงินซื้อหุ้น">
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">* สมาชิกตั้งแต่ 1 ปีขึ้นไปขอซื้อหุ้นเพื่อกู้ได้ไม่เกิน 200,000 บาท โดยจะหักจากวงเงินกู้ที่ได้รับอนุมัติ</span>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline btn-prev">👈 ย้อนกลับ</button>
                    <button type="button" class="btn btn-primary btn-next">ถัดไป 👉</button>
                </div>
            </div>

            <!-- STEP 3: ผู้ค้ำประกัน (Guarantors) -->
            <div class="form-step" id="step3">
                <div class="step-title">
                    <h3>👥 ข้อมูลผู้ค้ำประกัน (Guarantors Details)</h3>
                    <span>ขั้นตอนที่ 3 จาก 6</span>
                </div>

                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">ตามเกณฑ์ของสหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด สัญญากู้ยืมเงินสามัญทั่วไปจำเป็นต้องมีผู้ค้ำประกันที่เป็นสมาชิกสหกรณ์ (ระบุค้ำประกันได้สูงสุด 6 ท่าน)</p>
                
                <div id="guarantorsContainer">
                    <!-- Guarantor boxes will be added dynamically by Javascript -->
                </div>

                <div style="text-align: center; margin-bottom: 2rem;">
                    <button type="button" id="addGuarantor" class="btn btn-outline" style="border-style: dashed; border-width: 2px;">➕ เพิ่มข้อมูลผู้ค้ำประกัน</button>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline btn-prev">👈 ย้อนกลับ</button>
                    <button type="button" class="btn btn-primary btn-next">ถัดไป 👉</button>
                </div>
            </div>

            <!-- STEP 4: แนบหลักฐานเอกสาร (Upload Documents) -->
            <div class="form-step" id="step4">
                <div class="step-title">
                    <h3>📁 แนบเอกสารหลักฐานประกอบ (Required Documents)</h3>
                    <span>ขั้นตอนที่ 4 จาก 6</span>
                </div>

                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">อัปโหลดเอกสารหลักฐานประกอบการพิจารณาคำขอกู้เงิน โปรดเตรียมไฟล์สแกนหรือรูปถ่ายเอกสารที่ชัดเจนในรูปแบบ PDF, JPG, หรือ PNG</p>

                <div class="grid-2">
                    <div class="file-upload-wrapper">
                        <label>1. สำเนาบัตรประจำตัวประชาชนผู้กู้ <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📁</span>
                            <p>ลากไฟล์มาวางที่นี่ หรือ <strong>คลิกเพื่อเลือกไฟล์</strong></p>
                            <input type="file" id="id_card_file" name="id_card_file" accept=".pdf,image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>

                    <div class="file-upload-wrapper">
                        <label>2. สำเนาทะเบียนบ้านผู้กู้ <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📁</span>
                            <p>ลากไฟล์มาวางที่นี่ หรือ <strong>คลิกเพื่อเลือกไฟล์</strong></p>
                            <input type="file" id="house_reg_file" name="house_reg_file" accept=".pdf,image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="file-upload-wrapper">
                        <label>3. สลิปเงินเดือน/ใบรับรองเงินเดือนล่าสุด (เดือนที่ 1) <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📁</span>
                            <p>สลิปเงินเดือนย้อนหลังเดือนที่ 1</p>
                            <input type="file" id="payslip_1" name="payslip_file_1" accept=".pdf,image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>

                    <div class="file-upload-wrapper">
                        <label>4. สลิปเงินเดือนย้อนหลัง (เดือนที่ 2 และ 3)</label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📁</span>
                            <p>สลิปเงินเดือนเดือนที่ 2 และ 3 (รวมกันเป็น 1 ไฟล์)</p>
                            <input type="file" name="payslip_file_2" accept=".pdf,image/*">
                            <div class="file-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="file-upload-wrapper">
                        <label>5. รายงานผลตรวจเครดิตบูโร (NCB) <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📁</span>
                            <p>ลากไฟล์มาวางที่นี่ หรือ <strong>คลิกเพื่อเลือกไฟล์</strong></p>
                            <input type="file" id="ncb_file" name="ncb_file" accept=".pdf,image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>

                    <div class="file-upload-wrapper">
                        <label>6. สำเนาหน้าสมุดบัญชีรับเงินกู้ (สหกรณ์/กรุงไทย) <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📁</span>
                            <p>หน้าสมุดบัญชีธนาคารสำหรับโอนเงินกู้เข้า</p>
                            <input type="file" id="passbook_file" name="passbook_file" accept=".pdf,image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="grid-2" style="border-top: 1px dashed var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem;">
                    <div class="file-upload-wrapper">
                        <label>7. ภาพถ่ายขณะผู้กู้ลงลายมือชื่อสัญญา (รูปที่ 1) <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📷</span>
                            <p>ภาพถ่ายเซลฟี่หรือถ่ายให้เห็นขณะเซ็นสัญญากับหน้าจอ</p>
                            <input type="file" id="photo_sign_1" name="photo_sign_1" accept="image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>

                    <div class="file-upload-wrapper">
                        <label>8. ภาพถ่ายคู่กับใบคำขอสัญญากู้ที่เซ็นชื่อแล้ว (รูปที่ 2) <span style="color:var(--danger-color)">*</span></label>
                        <div class="file-upload-box">
                            <span class="file-upload-icon">📷</span>
                            <p>ภาพถือใบสัญญากู้หน้า 2 โชว์ลายเซ็นให้กล้องชัดเจน</p>
                            <input type="file" id="photo_sign_2" name="photo_sign_2" accept="image/*" required>
                            <div class="file-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>9. เอกสารอื่นๆ (เช่น ทะเบียนสมรส/เอกสารยินยอมคู่สมรส)</label>
                    <div class="file-upload-box" style="padding: 1rem;">
                        <span class="file-upload-icon">📁</span>
                        <p>แนบใบยินยอมคู่สมรส (สม-๐๖) หรือเอกสารเพิ่มเติม</p>
                        <input type="file" name="other_doc" accept=".pdf,image/*">
                        <div class="file-preview"></div>
                    </div>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline btn-prev">👈 ย้อนกลับ</button>
                    <button type="button" class="btn btn-primary btn-next">ถัดไป 👉</button>
                </div>
            </div>

            <!-- STEP 5: ข้อตกลง และลายมือชื่อผู้กู้ (Consent & Signature) -->
            <div class="form-step" id="step5">
                <div class="step-title">
                    <h3>✍️ สัญญาการกู้ยืมและลงลายมือชื่อ (Agreement & Signatures)</h3>
                    <span>ขั้นตอนที่ 5 จาก 6</span>
                </div>

                <h4 style="margin-bottom: 0.5rem; font-family: var(--font-heading);">หนังสือสัญญากู้ยืมเงิน และหนังสือยินยอมหักเงินชำระหนี้</h4>
                <div class="legal-scroll">
                    <p><strong>ข้อ ๑</strong> ข้าพเจ้าสัญญาว่าจะจ่ายส่งเงินกู้คืนให้แก่สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด ตามระเบียบข้อบังคับของสหกรณ์ฯ และตามประเภทค่างวดที่ระบุไว้</p>
                    <p><strong>ข้อ ๒</strong> ข้าพเจ้ายินยอมให้หัวหน้างาน/ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินเดือนและเงินบำนาญ ดำเนินการหักเงินเดือน เงินประจำตำแหน่ง หรือเงินได้อื่นใดของข้าพเจ้าเพื่อชำระหนี้ส่งให้แก่สหกรณ์ฯ เป็นลำดับแรกก่อนชำระหนี้แก่เจ้าหนี้อื่น</p>
                    <p><strong>ข้อ ๓</strong> ข้าพเจ้ารับรองว่าไม่อยู่ในระหว่างต้องหาคดีอาญา ถูกฟ้องร้องล้มละลาย หรือถูกตั้งกรรมการสอบสวนทางวินัยอย่างร้ายแรง และรับทราบข้อบังคับ ประกาศ คำสั่งของสหกรณ์ทุกประการ</p>
                    <p><strong>ข้อ ๔</strong> สัญญากู้ยืมเงินฉบับนี้จัดทำขึ้นโดยความสมัครใจและถูกต้องตรงตามเจตนารมณ์ของข้าพเจ้าทุกประการ</p>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" id="accept_terms" required> <strong>ข้าพเจ้ายอมรับข้อตกลง สัญญาเงินกู้ และยินยอมหักเงินเดือนเพื่อชำระหนี้ ตามรายละเอียดข้างต้น</strong> <span style="color:var(--danger-color)">*</span></label>
                </div>

                <div class="form-group" style="margin-top: 2rem;">
                    <label>ลงลายมือชื่อดิจิทัล ผู้ขอกู้เงิน (Digital Signature of Borrower) <span style="color:var(--danger-color)">*</span></label>
                    <div class="signature-container">
                        <div class="signature-pad-wrapper">
                            <canvas id="borrowerCanvas"></canvas>
                        </div>
                        <div class="signature-actions">
                            <button type="button" class="btn-sig-clear" id="clearBorrowerSig">ล้างค่า</button>
                        </div>
                        <!-- Base64 string from signature pad will reside here -->
                        <input type="hidden" id="borrower_signature" name="borrower_signature" required>
                    </div>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline btn-prev">👈 ย้อนกลับ</button>
                    <button type="button" class="btn btn-primary btn-next">ถัดไป 👉</button>
                </div>
            </div>

            <!-- STEP 6: ตรวจสอบข้อมูลก่อนส่ง (Review & Submit) -->
            <div class="form-step" id="step6">
                <div class="step-title">
                    <h3>🚀 ตรวจสอบข้อมูลการสมัครกู้เงิน (Review details)</h3>
                    <span>ขั้นตอนที่ 6 จาก 6</span>
                </div>

                <p style="color: var(--text-secondary); margin-bottom: 2rem;">โปรดตรวจสอบข้อมูลทั้งหมดด้านล่างให้ถูกต้องถี่ถ้วนก่อนกดปุ่ม "ส่งคำขอกู้เงิน" เมื่อส่งแล้ว ข้อมูลจะไม่สามารถแก้ไขได้จนกว่าจะผ่านการพิจารณาตรวจสอบเอกสารจากเจ้าหน้าที่</p>

                <!-- This section is populated dynamically by assets/js/form.js -->
                <div id="summaryReview"></div>

                <div class="alert alert-warning">
                    ⚠️ <strong>การส่งข้อมูลเท็จ:</strong> การกรอกข้อมูลที่เป็นเท็จหรือปลอมแปลงลายมือชื่อของผู้อื่นมีความผิดตามกฎหมายอาญาและวินัยข้าราชการตำรวจขั้นร้ายแรง
                </div>

                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline btn-prev">👈 ย้อนกลับ</button>
                    <button type="submit" class="btn btn-secondary">🚀 ส่งคำขอกู้เงินออนไลน์</button>
                </div>
            </div>

        </form>
    </div>
</main>

<footer>
    <p>© 2026 <strong>สหกรณ์ออมทรัพย์ตำรวจสงขลา จำกัด</strong>. สงวนลิขสิทธิ์.</p>
</footer>

<script src="assets/js/signature.js"></script>
<script src="assets/js/form.js"></script>

</body>
</html>
