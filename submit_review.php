<?php
// submit_review.php - Process admin review submission
require_once __DIR__ . '/db.php';
session_start();

// Check authentication
if (!($_SESSION['admin_logged'] ?? false)) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$loan_id = intval($_POST['loan_id'] ?? 0);
$reviewer_role = trim($_POST['reviewer_role'] ?? '');
$reviewer_name = trim($_POST['reviewer_name'] ?? '');
$decision = trim($_POST['decision'] ?? '');
$approved_amount = floatval($_POST['approved_amount'] ?? 0);
$comments = trim($_POST['comments'] ?? '');

// Committee meeting parameters
$meeting_set = trim($_POST['meeting_set'] ?? '');
$meeting_no = trim($_POST['meeting_no'] ?? '');
$meeting_date = trim($_POST['meeting_date'] ?? '');

if ($loan_id <= 0 || empty($reviewer_role) || empty($reviewer_name) || empty($decision)) {
    die("ข้อมูลผู้ทบทวนไม่ครบถ้วน กรุณาย้อนกลับและทำรายการใหม่");
}

try {
    $db->beginTransaction();

    // 1. Insert review record
    $sql = "INSERT INTO loan_reviews (
        loan_id, reviewer_role, reviewer_name, decision, approved_amount, comments, 
        meeting_no, meeting_set, meeting_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $loan_id, $reviewer_role, $reviewer_name, $decision, $approved_amount, $comments,
        $meeting_no, $meeting_set, $meeting_date
    ]);

    // 2. Determine and Update Loan Status
    $new_status = 'ยื่นคำขอแล้ว';
    
    if ($decision === 'rejected') {
        $new_status = 'ไม่อนุมัติ';
    } else {
        // Advanced stage mappings
        switch ($reviewer_role) {
            case 'credit_officer':
                $new_status = 'เสนอผู้บังคับบัญชา';
                break;
            case 'credit_head':
                // According to our form workflow:
                // Credit Officer -> Head of Credit -> Assistant Manager -> Manager -> Loan Committee -> Board -> Approved
                $new_status = 'เสนอฝ่ายสินเชื่อ';
                break;
            case 'assistant_manager':
                $new_status = 'เสนอผู้จัดการ';
                break;
            case 'manager':
                $new_status = 'เสนอคณะกรรมการเงินกู้';
                break;
            case 'loan_committee':
                $new_status = 'เสนอคณะกรรมการดำเนินการ';
                break;
            case 'board':
                $new_status = 'อนุมัติ';
                break;
        }
    }

    // Update parent loan
    $update_stmt = $db->prepare("UPDATE loans SET status = ? WHERE id = ?");
    $update_stmt->execute([$new_status, $loan_id]);

    $db->commit();
    
    $_SESSION['review_success'] = "บันทึกผลการพิจารณาตรวจสอบ เรียบร้อยแล้ว (สถานะอัปเดตเป็น: {$new_status})";
    header("Location: admin_view.php?id=" . $loan_id);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    die("เกิดข้อผิดพลาดในการบันทึกความคิดเห็น: " . $e->getMessage());
}
?>
