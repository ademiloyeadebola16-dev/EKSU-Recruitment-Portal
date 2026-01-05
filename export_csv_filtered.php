<?php
// export_csv_filtered.php (DB VERSION)
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

/* ============================================================
   1) SELECTED ROWS (GET → application IDs)
   ============================================================ */
$rows = isset($_GET['rows']) ? json_decode($_GET['rows'], true) : null;
$applications = [];

if (is_array($rows) && count($rows)) {

    $placeholders = implode(',', array_fill(0, count($rows), '?'));

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
        WHERE a.id IN ($placeholders)
        ORDER BY a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($rows);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

    /* ============================================================
       2) FILTERING VIA GET
       ============================================================ */
    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $job    = trim($_GET['job'] ?? '');

    $conditions = [];
    $params = [];

    if ($search !== '') {
        $conditions[] = "
            (a.first_name LIKE ? OR
             a.middle_name LIKE ? OR
             a.last_name LIKE ? OR
             a.email LIKE ? OR
             j.position LIKE ?)
        ";
        $params = array_merge($params, array_fill(0, 5, "%$search%"));
    }

    if ($status !== '') {
        $conditions[] = "a.status = ?";
        $params[] = $status;
    }

    if ($job !== '') {
        $conditions[] = "j.position = ?";
        $params[] = $job;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

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
        $where
        ORDER BY a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
   CSV OUTPUT
   ============================================================ */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=filtered_applicants.csv');

$out = fopen('php://output', 'w');

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
