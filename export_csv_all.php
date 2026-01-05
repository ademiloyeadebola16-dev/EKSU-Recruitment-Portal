<?php
// export_csv_all.php (DB VERSION)
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

/* ---------------- FETCH FROM DATABASE ---------------- */
$sql = "
    SELECT 
        a.first_name,
        a.middle_name,
        a.last_name,
        a.email,
        a.phone,
        j.position AS job_title,
        a.department,
        a.academic_qualification,
        a.internal_status,
        a.status,
        a.reason,
        a.created_at
    FROM applications a
    LEFT JOIN jobs j ON j.id = a.job_id
    ORDER BY a.created_at DESC
";

$stmt = $pdo->query($sql);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- CSV HEADERS ---------------- */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=all_applicants.csv');

$out = fopen('php://output', 'w');

/* ---------------- CSV COLUMN TITLES ---------------- */
fputcsv($out, [
    '#',
    'First Name',
    'Middle Name',
    'Last Name',
    'Email',
    'Phone',
    'Job Title',
    'Department',
    'Qualification',
    'Internal Status',
    'Status',
    'Reason',
    'Date'
]);

/* ---------------- CSV ROWS ---------------- */
foreach ($applications as $i => $a) {
    fputcsv($out, [
        $i + 1,
        $a['first_name'] ?? '',
        $a['middle_name'] ?? '',
        $a['last_name'] ?? '',
        $a['email'] ?? '',
        $a['phone'] ?? '',
        $a['job_title'] ?? '',
        $a['department'] ?? '',
        $a['academic_qualification'] ?? '',
        $a['internal_status'] ?? '',
        $a['status'] ?? '',
        str_replace(["\r", "\n"], [' ', ' '], $a['reason'] ?? ''),
        $a['created_at'] ?? ''
    ]);
}

fclose($out);
exit;
