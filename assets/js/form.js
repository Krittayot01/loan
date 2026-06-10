// assets/js/form.js - Multi-step Wizard Controller

document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 6;
    const form = document.getElementById('loanForm');
    
    // Check if on apply page
    if (!form) return;

    // Initialize Stepper
    updateStepper();
    
    // Initialize first Borrower signature pad
    const borrowerCanvas = document.getElementById('borrowerCanvas');
    const borrowerSigInput = document.getElementById('borrower_signature');
    let borrowerSigPad = null;
    if (borrowerCanvas && borrowerSigInput) {
        borrowerSigPad = new SignaturePad(borrowerCanvas, borrowerSigInput);
        
        document.getElementById('clearBorrowerSig').addEventListener('click', function() {
            borrowerSigPad.clear();
        });
    }

    // Step Navigation
    document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                    updateStepper();
                    
                    // Specific step entry hooks
                    if (currentStep === 5) {
                        // Init signature pads inside guarantors on step 5
                        initGuarantorSignaturePads();
                    }
                    if (currentStep === 6) {
                        generateSummary();
                    }
                }
            }
        });
    });

    document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                updateStepper();
            }
        });
    });

    function showStep(stepNum) {
        document.querySelectorAll('.form-step').forEach(step => {
            step.classList.remove('active');
        });
        document.getElementById('step' + stepNum).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateStepper() {
        // Update circles
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed');
            
            if (stepNum === currentStep) {
                item.classList.add('active');
            } else if (stepNum < currentStep) {
                item.classList.add('completed');
            }
        });
        
        // Update line progress bar
        const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
        const progressBar = document.querySelector('.stepper-progress');
        if (progressBar) {
            progressBar.style.width = progressPercent + '%';
        }
    }

    // Live Calculations for Loan & Repayment
    const loanAmountInput = document.getElementById('loan_amount');
    const loanAmountTextInput = document.getElementById('loan_amount_text');
    const installmentsInput = document.getElementById('repayment_installments');
    const repaymentTypeInputs = document.querySelectorAll('input[name="repayment_type"]');
    const repaymentAmountInput = document.getElementById('repayment_amount');
    const repaymentAmountDisplay = document.getElementById('repayment_amount_display');
    const interestRate = 5.5; // 5.5% annual rate

    if (loanAmountInput && installmentsInput) {
        // Change events
        loanAmountInput.addEventListener('input', calculateRepayment);
        installmentsInput.addEventListener('input', calculateRepayment);
        repaymentTypeInputs.forEach(radio => radio.addEventListener('change', calculateRepayment));
        
        // Auto convert number to Thai Baht text
        loanAmountInput.addEventListener('input', function() {
            const val = parseFloat(this.value);
            if (!isNaN(val)) {
                loanAmountTextInput.value = thaiBahtText(val);
            } else {
                loanAmountTextInput.value = '';
            }
        });
    }

    function calculateRepayment() {
        const principal = parseFloat(loanAmountInput.value);
        const months = parseInt(installmentsInput.value);
        let repaymentType = 1; // Default
        
        repaymentTypeInputs.forEach(radio => {
            if (radio.checked) repaymentType = parseInt(radio.value);
        });

        if (isNaN(principal) || principal <= 0 || isNaN(months) || months <= 0) {
            repaymentAmountInput.value = '';
            if (repaymentAmountDisplay) repaymentAmountDisplay.innerText = '-';
            return;
        }

        const monthlyRate = (interestRate / 12) / 100;
        let monthlyPayment = 0;

        if (repaymentType === 1) {
            // Method 1: Principal + Interest Equal Monthly (Amortized)
            // A = P * (r(1+r)^n) / ((1+r)^n - 1)
            const x = Math.pow(1 + monthlyRate, months);
            monthlyPayment = (principal * monthlyRate * x) / (x - 1);
        } else {
            // Method 2: Equal Principal Monthly + Interest on Balance
            // Initial month has max payment: P/n + P*r
            const principalMonthly = principal / months;
            const interestInitial = principal * monthlyRate;
            monthlyPayment = principalMonthly + interestInitial;
        }

        const roundedPayment = Math.round(monthlyPayment * 100) / 100;
        repaymentAmountInput.value = roundedPayment.toFixed(2);
        if (repaymentAmountDisplay) {
            repaymentAmountDisplay.innerText = roundedPayment.toLocaleString('th-TH', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }) + ' บาท' + (repaymentType === 2 ? ' (สูงสุดในงวดแรก)' : '');
        }
    }

    // Dynamic Guarantors management (max 6)
    const addGuarantorBtn = document.getElementById('addGuarantor');
    const guarantorsContainer = document.getElementById('guarantorsContainer');
    let guarantorCount = 0;
    const maxGuarantors = 6;
    let guarantorSigPads = {};

    if (addGuarantorBtn && guarantorsContainer) {
        addGuarantorBtn.addEventListener('click', function() {
            if (guarantorCount >= maxGuarantors) {
                alert('สามารถระบุผู้ค้ำประกันได้สูงสุด 6 ท่าน');
                return;
            }
            guarantorCount++;
            addGuarantorCard(guarantorCount);
            toggleAddGuarantorButton();
        });
    }

    function addGuarantorCard(index) {
        const cardHtml = `
            <div class="guarantor-box" id="guarantorBox_${index}">
                <div class="guarantor-header">
                    <span class="guarantor-num">👤 ผู้ค้ำประกันท่านที่ ${index}</span>
                    <button type="button" class="remove-guarantor" data-index="${index}">❌ ลบผู้ค้ำประกัน</button>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล ผู้ค้ำประกัน <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="g_name[]" class="form-control" required placeholder="นาย/นาง/นางสาว">
                    </div>
                    <div class="form-group">
                        <label>เลขทะเบียนสมาชิก <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="g_member_no[]" class="form-control" required placeholder="เลขทะเบียน">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label>อายุ (ปี)</label>
                        <input type="number" name="g_age[]" class="form-control" placeholder="อายุ">
                    </div>
                    <div class="form-group">
                        <label>เลขประจำตัวประชาชน (13 หลัก)</label>
                        <input type="text" name="g_citizen_id[]" class="form-control" maxlength="13" placeholder="เลข 13 หลัก">
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์มือถือ</label>
                        <input type="text" name="g_mobile[]" class="form-control" placeholder="เบอร์โทร">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label>ตำแหน่งงาน</label>
                        <input type="text" name="g_position[]" class="form-control" placeholder="ตำแหน่ง">
                    </div>
                    <div class="form-group">
                        <label>สังกัด/หน่วยงาน</label>
                        <input type="text" name="g_affiliation[]" class="form-control" placeholder="สังกัด">
                    </div>
                    <div class="form-group">
                        <label>เงินเดือน/ค่าจ้าง (บาท)</label>
                        <input type="number" name="g_salary[]" class="form-control" placeholder="เงินเดือน">
                    </div>
                </div>
                <div class="form-group">
                    <label>ที่อยู่ปัจจุบัน</label>
                    <input type="text" name="g_address[]" class="form-control" placeholder="บ้านเลขที่ หมู่ ซอย ถนน ตำบล อำเภอ จังหวัด">
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label>รหัสไปรษณีย์</label>
                        <input type="text" name="g_postal_code[]" class="form-control" placeholder="รหัสไปรษณีย์">
                    </div>
                    <div class="form-group">
                        <label>สถานภาพ</label>
                        <select name="g_marital_status[]" class="form-control">
                            <option value="โสด">โสด</option>
                            <option value="สมรส">สมรส</option>
                            <option value="หย่า">หย่า</option>
                            <option value="ม่าย">ม่าย</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>วงเงินค้ำประกัน (บาท)</label>
                        <input type="number" name="g_guarantee_amount[]" class="form-control" placeholder="ระบุจำนวนเงินค้ำประกัน">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label>ลายเซ็นดิจิทัล ผู้ค้ำประกัน</label>
                    <div class="signature-container">
                        <div class="signature-pad-wrapper">
                            <canvas id="gCanvas_${index}"></canvas>
                        </div>
                        <div class="signature-actions">
                            <button type="button" class="btn-sig-clear" id="clearG_${index}">ล้างค่า</button>
                        </div>
                        <input type="hidden" name="g_signature[]" id="gSigVal_${index}">
                    </div>
                </div>
            </div>
        `;
        
        guarantorsContainer.insertAdjacentHTML('beforeend', cardHtml);
        
        // Bind remove event
        const removeBtn = guarantorsContainer.querySelector(`#guarantorBox_${index} .remove-guarantor`);
        removeBtn.addEventListener('click', function() {
            const idx = parseInt(this.getAttribute('data-index'));
            removeGuarantorCard(idx);
        });
        
        // Re-number
        reIndexGuarantors();
    }

    function removeGuarantorCard(index) {
        const box = document.getElementById(`guarantorBox_${index}`);
        if (box) {
            // Remove signature pad ref
            if (guarantorSigPads[index]) {
                delete guarantorSigPads[index];
            }
            box.remove();
            guarantorCount--;
            reIndexGuarantors();
            toggleAddGuarantorButton();
        }
    }

    function reIndexGuarantors() {
        const boxes = guarantorsContainer.querySelectorAll('.guarantor-box');
        guarantorCount = boxes.length;
        boxes.forEach((box, i) => {
            const newIndex = i + 1;
            const oldId = box.id;
            
            // Update box id
            box.id = `guarantorBox_${newIndex}`;
            
            // Update header title
            const headerNum = box.querySelector('.guarantor-num');
            headerNum.innerText = `👤 ผู้ค้ำประกันท่านที่ ${newIndex}`;
            
            // Update remove button attribute
            const removeBtn = box.querySelector('.remove-guarantor');
            removeBtn.setAttribute('data-index', newIndex);
            
            // Update canvas, clear btn and input ids
            const canvas = box.querySelector('canvas');
            canvas.id = `gCanvas_${newIndex}`;
            
            const clearBtn = box.querySelector('.btn-sig-clear');
            clearBtn.id = `clearG_${newIndex}`;
            
            const hiddenInput = box.querySelector('input[type="hidden"]');
            hiddenInput.id = `gSigVal_${newIndex}`;
        });
    }

    function toggleAddGuarantorButton() {
        if (guarantorCount >= maxGuarantors) {
            addGuarantorBtn.style.display = 'none';
        } else {
            addGuarantorBtn.style.display = 'inline-flex';
        }
    }

    function initGuarantorSignaturePads() {
        // Initialize or re-init pads for each guarantor on Step 5 page entry
        for (let i = 1; i <= guarantorCount; i++) {
            const canvas = document.getElementById(`gCanvas_${i}`);
            const input = document.getElementById(`gSigVal_${i}`);
            const clearBtn = document.getElementById(`clearG_${i}`);
            
            if (canvas && input && !guarantorSigPads[i]) {
                const pad = new SignaturePad(canvas, input);
                guarantorSigPads[i] = pad;
                
                clearBtn.replaceWith(clearBtn.cloneNode(true)); // Clear previous listeners if any
                document.getElementById(`clearG_${i}`).addEventListener('click', function() {
                    pad.clear();
                });
            } else if (canvas && guarantorSigPads[i]) {
                guarantorSigPads[i].handleResize(); // recalculate size
            }
        }
    }

    // Step-by-Step Validation
    function validateStep(stepNum) {
        const stepContainer = document.getElementById('step' + stepNum);
        if (!stepContainer) return true;
        
        const requiredInputs = stepContainer.querySelectorAll('[required]');
        let valid = true;
        
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('error');
                // Create or show error message
                let errorMsg = input.parentNode.querySelector('.error-message');
                if (!errorMsg) {
                    errorMsg = document.createElement('span');
                    errorMsg.className = 'error-message';
                    errorMsg.style.color = 'var(--danger-color)';
                    errorMsg.style.fontSize = '0.75rem';
                    errorMsg.innerText = 'กรุณากรอกข้อมูลนี้';
                    input.parentNode.appendChild(errorMsg);
                }
                valid = false;
            } else {
                input.classList.remove('error');
                const errorMsg = input.parentNode.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
            }
        });

        // Specific Custom validations
        if (stepNum === 1) {
            const citizen = document.getElementById('citizen_id');
            if (citizen && citizen.value.length !== 13) {
                alert('เลขประจำตัวประชาชนต้องเป็นตัวเลข 13 หลัก');
                valid = false;
            }
        }
        
        if (stepNum === 4) {
            // Check if at least some files are selected for required uploads
            const idCardFile = document.getElementById('id_card_file');
            const houseRegFile = document.getElementById('house_reg_file');
            
            if (idCardFile && !idCardFile.value) {
                alert('กรุณาอัปโหลดสำเนาบัตรประจำตัวประชาชน');
                valid = false;
            }
            if (houseRegFile && !houseRegFile.value) {
                alert('กรุณาอัปโหลดสำเนาทะเบียนบ้าน');
                valid = false;
            }
        }

        if (stepNum === 5) {
            // Check digital signature of borrower
            if (borrowerSigInput && !borrowerSigInput.value) {
                alert('กรุณาลงลายมือชื่อดิจิทัลของผู้ขอกู้ก่อนไปยังหน้าถัดไป');
                valid = false;
            }
        }

        return valid;
    }

    // Update File uploads display
    document.querySelectorAll('.file-upload-box input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const box = this.closest('.file-upload-box');
            const preview = box.querySelector('.file-preview');
            const text = box.querySelector('p');
            
            if (this.files && this.files.length > 0) {
                const fileName = this.files[0].name;
                text.innerHTML = `ไฟล์ที่เลือก: <strong>${fileName}</strong>`;
                preview.style.display = 'block';
                preview.innerText = 'อัปโหลดเรียบร้อย ✓';
                box.style.borderColor = 'var(--success-color)';
            } else {
                text.innerHTML = `ลากไฟล์มาวางที่นี่ หรือ <strong>คลิกเพื่อเลือกไฟล์</strong>`;
                preview.style.display = 'none';
                box.style.borderColor = '#cbd5e1';
            }
        });
    });

    // Generate Final Summary on Step 6
    function generateSummary() {
        const summaryDiv = document.getElementById('summaryReview');
        if (!summaryDiv) return;

        // Fetch inputs
        const name = (document.getElementById('title').value || '') + (document.getElementById('name').value || '');
        const member_no = document.getElementById('member_no').value || '';
        const citizen_id = document.getElementById('citizen_id').value || '';
        const loan_amount = parseFloat(document.getElementById('loan_amount').value || 0).toLocaleString('th-TH', {minimumFractionDigits: 2});
        const installments = document.getElementById('repayment_installments').value || '';
        const purpose = document.getElementById('loan_purpose').value || '';
        
        let repaymentTypeStr = 'ต้นเงินพร้อมดอกเบี้ยเท่ากันต่องวด';
        const repaymentTypeVal = document.querySelector('input[name="repayment_type"]:checked').value;
        if (repaymentTypeVal === '2') {
            repaymentTypeStr = 'ต้นเงินเท่ากันต่องวด พร้อมดอกเบี้ย';
        }
        
        const repayment_amount = parseFloat(repaymentAmountInput.value || 0).toLocaleString('th-TH', {minimumFractionDigits: 2});

        // Set up summary HTML
        let html = `
            <div class="summary-section">
                <h4>👤 ข้อมูลผู้ขอกู้เงิน</h4>
                <div class="summary-grid">
                    <span class="summary-label">ชื่อ-นามสกุล:</span>
                    <span class="summary-value">${name}</span>
                    
                    <span class="summary-label">ทะเบียนสมาชิก:</span>
                    <span class="summary-value">${member_no}</span>
                    
                    <span class="summary-label">เลขประจำตัวประชาชน:</span>
                    <span class="summary-value">${citizen_id}</span>
                </div>
            </div>
            
            <div class="summary-section">
                <h4>💰 รายละเอียดคำขอกู้เงิน</h4>
                <div class="summary-grid">
                    <span class="summary-label">วงเงินขอกู้:</span>
                    <span class="summary-value" style="font-weight: 700; color: var(--primary-color);">${loan_amount} บาท (${loanAmountTextInput.value})</span>
                    
                    <span class="summary-label">วัตถุประสงค์:</span>
                    <span class="summary-value">${purpose}</span>
                    
                    <span class="summary-label">ระยะเวลาชำระ:</span>
                    <span class="summary-value">${installments} งวด</span>
                    
                    <span class="summary-label">รูปแบบการผ่อน:</span>
                    <span class="summary-value">${repaymentTypeStr}</span>
                    
                    <span class="summary-label">ผ่อนชำระต่องวด:</span>
                    <span class="summary-value" style="font-weight: 700;">ประมาณ ${repayment_amount} บาท/เดือน</span>
                </div>
            </div>
            
            <div class="summary-section">
                <h4>👥 ข้อมูลผู้ค้ำประกัน (ทั้งหมด ${guarantorCount} ท่าน)</h4>
        `;

        if (guarantorCount === 0) {
            html += `<p style="color: var(--text-secondary); font-style: italic;">ไม่มีผู้ค้ำประกัน</p>`;
        } else {
            const gNames = document.getElementsByName('g_name[]');
            const gMemberNos = document.getElementsByName('g_member_no[]');
            const gAmounts = document.getElementsByName('g_guarantee_amount[]');
            
            for (let i = 0; i < guarantorCount; i++) {
                const gName = gNames[i] ? gNames[i].value : '';
                const gMember = gMemberNos[i] ? gMemberNos[i].value : '';
                const gAmount = gAmounts[i] && gAmounts[i].value ? parseFloat(gAmounts[i].value).toLocaleString('th-TH') + ' บาท' : 'ระบุภายหลัง';
                
                html += `
                    <div class="summary-grid" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1;">
                        <span class="summary-label">ท่านที่ ${i+1}:</span>
                        <span class="summary-value">${gName} (ทะเบียน: ${gMember})</span>
                        
                        <span class="summary-label">วงเงินค้ำประกัน:</span>
                        <span class="summary-value">${gAmount}</span>
                    </div>
                `;
            }
        }
        
        html += `</div>`;
        summaryDiv.innerHTML = html;
    }

    // Thai Baht Text translation algorithm
    function thaiBahtText(num) {
        num = parseFloat(num);
        if (isNaN(num)) return '';
        if (num === 0) return 'ศูนย์บาทถ้วน';
        
        const thText = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
        const thUnit = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
        
        let str = num.toFixed(2);
        let [baht, satang] = str.split('.');
        
        let bahtText = '';
        
        if (parseInt(baht) === 0) {
            bahtText = 'ศูนย์';
        } else {
            let len = baht.length;
            for (let i = 0; i < len; i++) {
                let digit = parseInt(baht.charAt(i));
                let pos = len - i - 1;
                
                if (digit !== 0) {
                    let unitIdx = pos % 6;
                    let unit = thUnit[unitIdx];
                    
                    let text = thText[digit];
                    if (unitIdx === 1) { // Ten
                        if (digit === 1) text = '';
                        else if (digit === 2) text = 'ยี่';
                    }
                    
                    if (unitIdx === 0 && pos > 0 && digit === 1) {
                        if (parseInt(baht.charAt(i - 1)) !== 0) {
                            text = 'เอ็ด';
                        }
                    }
                    
                    if (pos > 0 && pos % 6 === 0) {
                        unit = 'ล้าน';
                    }
                    
                    bahtText += text + unit;
                } else {
                    if (pos > 0 && pos % 6 === 0 && bahtText !== '') {
                        bahtText += 'ล้าน';
                    }
                }
            }
        }
        
        bahtText += 'บาท';
        
        if (parseInt(satang) === 0) {
            bahtText += 'ถ้วน';
        } else {
            let len = satang.length;
            let satangText = '';
            for (let i = 0; i < len; i++) {
                let digit = parseInt(satang.charAt(i));
                let pos = len - i - 1;
                
                if (digit !== 0) {
                    let unit = pos === 1 ? 'สิบ' : '';
                    let text = thText[digit];
                    
                    if (pos === 1) {
                        if (digit === 1) text = '';
                        else if (digit === 2) text = 'ยี่';
                    }
                    
                    if (pos === 0 && digit === 1 && parseInt(satang.charAt(0)) !== 0) {
                        text = 'เอ็ด';
                    }
                    
                    satangText += text + unit;
                }
            }
            bahtText += satangText + 'สตางค์';
        }
        
        return bahtText;
    }
});
