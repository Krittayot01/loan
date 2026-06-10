<?php
// db.php - Database connection and automatic setup for SQLite

$db_file = __DIR__ . '/database.sqlite';
$upload_dir = __DIR__ . '/uploads';

// Create uploads directory if not exists
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    // Connect to SQLite database
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $db->exec("PRAGMA foreign_keys = ON;");
    
    // Create Members table
    $db->exec("CREATE TABLE IF NOT EXISTS members (
        member_no TEXT PRIMARY KEY,
        title TEXT,
        name TEXT NOT NULL,
        age INTEGER,
        dob TEXT,
        citizen_id TEXT NOT NULL,
        work_status TEXT,
        position TEXT,
        affiliation TEXT,
        salary REAL,
        address TEXT,
        postal_code TEXT,
        mobile TEXT,
        marital_status TEXT,
        spouse_name TEXT
    );");

    // Seed mock members if table is empty
    $count = $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO members VALUES 
        ('00009', 'นาย', 'วีรยุทธ สุวรรณโมสิ', 38, '1988-06-15', '1900114981390', 'ข้าราชการ', 'สารวัตรป้องกันปราบปราม', 'สภ.เมืองสงขลา', 35000.00, '123/45 ถนนราชดำเนิน ตำบลบ่อยาง อำเภอเมือง จังหวัดสงขลา', '90000', '0812345678', 'สมรส', 'นางสาวรวีวรรณ สุวรรณโมสิ'),
        ('00006', 'นางสาว', 'จีราพร ปิยะพงศ์', 32, '1994-03-20', '1900114981320', 'ข้าราชการ', 'รองสารวัตรฝ่ายอำนวยการ', 'ตำรวจภูธรจังหวัดสงขลา', 28000.00, '99 หมู่ 3 ตำบลเขารูปช้าง อำเภอเมือง จังหวัดสงขลา', '90000', '0898765432', 'โสด', '');");
    }
    
    // Create Loans table
    $db->exec("CREATE TABLE IF NOT EXISTS loans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT UNIQUE NOT NULL,
        member_no TEXT NOT NULL,
        title TEXT,
        name TEXT NOT NULL,
        age INTEGER,
        dob TEXT,
        citizen_id TEXT NOT NULL,
        work_status TEXT,
        work_status_other TEXT,
        position TEXT,
        affiliation TEXT,
        salary REAL,
        address TEXT,
        postal_code TEXT,
        mobile TEXT,
        marital_status TEXT,
        spouse_name TEXT,
        loan_amount REAL NOT NULL,
        loan_amount_text TEXT,
        loan_purpose TEXT,
        repayment_installments INTEGER,
        repayment_type INTEGER, -- 1 = ต้นเงินพร้อมดอกเบี้ยเท่ากัน, 2 = ต้นเงินเท่ากันพร้อมดอกเบี้ย
        repayment_amount REAL,
        receive_account_type INTEGER, -- 1 = สหกรณ์, 2 = ธนาคารกรุงไทย
        receive_account_name TEXT,
        receive_account_no TEXT,
        shares_buy_amount REAL DEFAULT 0, -- สม-๐๔
        spouse_consent_date TEXT, -- สม-๐๖
        borrower_signature TEXT, -- Digital Signature (Base64 or image path)
        status TEXT DEFAULT 'ยื่นคำขอแล้ว', -- ยื่นคำขอแล้ว, ตรวจสอบเอกสาร, เสนอผู้บังคับบัญชา, เสนอฝ่ายสินเชื่อ, เสนอผู้จัดการ, เสนอคณะกรรมการเงินกู้, เสนอคณะกรรมการดำเนินการ, อนุมัติ, ไม่อนุมัติ
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");
    
    // Create Guarantors table (up to 6 per loan)
    $db->exec("CREATE TABLE IF NOT EXISTS guarantors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        loan_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        member_no TEXT,
        age INTEGER,
        citizen_id TEXT,
        work_status TEXT,
        position TEXT,
        affiliation TEXT,
        salary REAL,
        address TEXT,
        postal_code TEXT,
        mobile TEXT,
        marital_status TEXT,
        spouse_name TEXT,
        guarantee_amount REAL,
        signature_data TEXT, -- Base64 digital signature
        FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
    );");
    
    // Create Loan Documents table
    $db->exec("CREATE TABLE IF NOT EXISTS loan_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        loan_id INTEGER NOT NULL,
        doc_type TEXT NOT NULL, -- id_card, house_reg, marriage_cert, passbook, payslip, ncb, photo_sign_1, photo_sign_2, spouse_consent
        file_path TEXT NOT NULL,
        file_name TEXT NOT NULL,
        FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
    );");
    
    // Create Loan Reviews table (approvals workflow)
    $db->exec("CREATE TABLE IF NOT EXISTS loan_reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        loan_id INTEGER NOT NULL,
        reviewer_role TEXT NOT NULL, -- credit_officer, credit_head, assistant_manager, manager, loan_committee, board
        reviewer_name TEXT,
        decision TEXT NOT NULL, -- approved, rejected, deferred
        approved_amount REAL,
        comments TEXT,
        meeting_no TEXT, -- สำหรับกรรมการ
        meeting_set TEXT, -- สำหรับกรรมการ
        meeting_date TEXT, -- สำหรับกรรมการ
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
    );");
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
