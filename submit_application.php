<?php
// submit_application.php - Handle loan form submission
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$errors = [];
$db->beginTransaction();

try {
    // 1. Generate Unique Tracking Token
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $prefix = "LN-" . $year . $month . $day . "-";
    
    // Query last loan for today
    $stmt = $db->prepare("SELECT token FROM loans WHERE token LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . "%"]);
    $last_loan = $stmt->fetch();
    
    $seq = 1;
    if ($last_loan) {
        $last_token = $last_loan['token'];
        $last_seq = intval(substr($last_token, strrpos($last_token, '-') + 1));
        $seq = $last_seq + 1;
    }
    $token = $prefix . sprintf('%04d', $seq);

    // 2. Fetch Borrower Data
    $title = trim($_POST['title'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $member_no = trim($_POST['member_no'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $dob = trim($_POST['dob'] ?? '');
    $citizen_id = trim($_POST['citizen_id'] ?? '');
    $work_status = trim($_POST['work_status'] ?? '');
    $work_status_other = trim($_POST['work_status_other'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $affiliation = trim($_POST['affiliation'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $marital_status = trim($_POST['marital_status'] ?? '');
    $spouse_name = trim($_POST['spouse_name'] ?? '');
    
    $loan_amount = floatval($_POST['loan_amount'] ?? 0);
    $loan_amount_text = trim($_POST['loan_amount_text'] ?? '');
    $loan_purpose = trim($_POST['loan_purpose'] ?? '');
    $repayment_installments = intval($_POST['repayment_installments'] ?? 0);
    $repayment_type = intval($_POST['repayment_type'] ?? 1);
    $repayment_amount = floatval($_POST['repayment_amount'] ?? 0);
    
    $receive_account_type = intval($_POST['receive_account_type'] ?? 1);
    $receive_account_name = trim($_POST['receive_account_name'] ?? '');
    $receive_account_no = trim($_POST['receive_account_no'] ?? '');
    
    $shares_buy_amount = floatval($_POST['shares_buy_amount'] ?? 0);
    $borrower_signature = $_POST['borrower_signature'] ?? ''; // Base64 string

    // Insert borrower loan
    $sql = "INSERT INTO loans (
        token, member_no, title, name, age, dob, citizen_id, work_status, work_status_other, 
        position, affiliation, salary, address, postal_code, mobile, marital_status, spouse_name,
        loan_amount, loan_amount_text, loan_purpose, repayment_installments, repayment_type, 
        repayment_amount, receive_account_type, receive_account_name, receive_account_no, 
        shares_buy_amount, borrower_signature
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?
    )";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $token, $member_no, $title, $name, $age, $dob, $citizen_id, $work_status, $work_status_other,
        $position, $affiliation, $salary, $address, $postal_code, $mobile, $marital_status, $spouse_name,
        $loan_amount, $loan_amount_text, $loan_purpose, $repayment_installments, $repayment_type,
        $repayment_amount, $receive_account_type, $receive_account_name, $receive_account_no,
        $shares_buy_amount, $borrower_signature
    ]);
    
    $loan_id = $db->lastInsertId();

    // 3. Process Guarantors
    if (isset($_POST['g_name']) && is_array($_POST['g_name'])) {
        $g_names = $_POST['g_name'];
        $g_member_nos = $_POST['g_member_no'] ?? [];
        $g_ages = $_POST['g_age'] ?? [];
        $g_citizen_ids = $_POST['g_citizen_id'] ?? [];
        $g_mobiles = $_POST['g_mobile'] ?? [];
        $g_positions = $_POST['g_position'] ?? [];
        $g_affiliations = $_POST['g_affiliation'] ?? [];
        $g_salaries = $_POST['g_salary'] ?? [];
        $g_addresses = $_POST['g_address'] ?? [];
        $g_postal_codes = $_POST['g_postal_code'] ?? [];
        $g_marital_statuses = $_POST['g_marital_status'] ?? [];
        $g_guarantee_amounts = $_POST['g_guarantee_amount'] ?? [];
        $g_signatures = $_POST['g_signature'] ?? [];

        for ($i = 0; $i < count($g_names); $i++) {
            if (empty(trim($g_names[$i]))) continue; // Skip empty rows

            $g_name = trim($g_names[$i]);
            $g_member = trim($g_member_nos[$i] ?? '');
            $g_age = intval($g_ages[$i] ?? 0);
            $g_citizen = trim($g_citizen_ids[$i] ?? '');
            $g_mobile = trim($g_mobiles[$i] ?? '');
            $g_pos = trim($g_positions[$i] ?? '');
            $g_aff = trim($g_affiliations[$i] ?? '');
            $g_sal = floatval($g_salaries[$i] ?? 0);
            $g_addr = trim($g_addresses[$i] ?? '');
            $g_post = trim($g_postal_codes[$i] ?? '');
            $g_marital = trim($g_marital_statuses[$i] ?? 'โสด');
            $g_amount = floatval($g_guarantee_amounts[$i] ?? 0);
            $g_sig = $g_signatures[$i] ?? ''; // Base64 signature image

            $g_sql = "INSERT INTO guarantors (
                loan_id, name, member_no, age, citizen_id, mobile, position, affiliation, 
                salary, address, postal_code, marital_status, guarantee_amount, signature_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $g_stmt = $db->prepare($g_sql);
            $g_stmt->execute([
                $loan_id, $g_name, $g_member, $g_age, $g_citizen, $g_mobile, $g_pos, $g_aff,
                $g_sal, $g_addr, $g_post, $g_marital, $g_amount, $g_sig
            ]);
        }
    }

    // 4. Handle File Uploads
    $file_keys = [
        'id_card_file' => 'id_card',
        'house_reg_file' => 'house_reg',
        'payslip_file_1' => 'payslip',
        'payslip_file_2' => 'payslip',
        'ncb_file' => 'ncb',
        'passbook_file' => 'passbook',
        'photo_sign_1' => 'photo_sign_1',
        'photo_sign_2' => 'photo_sign_2',
        'other_doc' => 'other'
    ];

    foreach ($file_keys as $post_key => $doc_type) {
        if (isset($_FILES[$post_key]) && $_FILES[$post_key]['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES[$post_key]['tmp_name'];
            $fileName = $_FILES[$post_key]['name'];
            $fileSize = $_FILES[$post_key]['size'];
            $fileType = $_FILES[$post_key]['type'];
            
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Clean filename
            $newFileName = $token . '_' . $post_key . '_' . time() . '.' . $fileExtension;
            $dest_path = __DIR__ . '/uploads/' . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $doc_stmt = $db->prepare("INSERT INTO loan_documents (loan_id, doc_type, file_path, file_name) VALUES (?, ?, ?, ?)");
                $doc_stmt->execute([$loan_id, $doc_type, 'uploads/' . $newFileName, $fileName]);
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึกไฟล์: " . $fileName);
            }
        }
    }

    // Commit changes
    $db->commit();

    // Redirect to check status page with success alert
    session_start();
    $_SESSION['success_message'] = "ส่งคำขอกู้เงินของคุณสำเร็จแล้ว! เลขคำขอกู้เงินของคุณคือ: <strong>{$token}</strong> โปรดเก็บเลขนี้ไว้ใช้ติดตามสถานะ";
    
    // Redirect
    header("Location: check.php?token=" . urlencode($token));
    exit;

} catch (Exception $e) {
    $db->rollBack();
    die("เกิดข้อผิดพลาดร้ายแรงขณะบันทึกข้อมูล: " . $e->getMessage());
}
?>
